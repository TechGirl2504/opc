<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebPushService
{
    public function isEnabled(): bool
    {
        return $this->publicKey() !== null
            && $this->privateKeyPem() !== null
            && $this->subject() !== null;
    }

    public function publicKey(): ?string
    {
        $publicKey = trim((string) config('services.web_push.public_key', ''));

        return $publicKey !== '' ? $publicKey : null;
    }

    public function privateKeyPem(): ?string
    {
        $configured = trim((string) config('services.web_push.private_key', ''));

        if ($configured === '') {
            return null;
        }

        $decoded = base64_decode($configured, true);
        if ($decoded !== false && str_contains($decoded, 'BEGIN PRIVATE KEY')) {
            return $decoded;
        }

        return str_contains($configured, 'BEGIN PRIVATE KEY') ? $configured : null;
    }

    public function subject(): ?string
    {
        $subject = trim((string) config('services.web_push.subject', ''));

        return $subject !== '' ? $subject : null;
    }

    public function registerSubscription(User $user, array $payload): PushSubscription
    {
        return PushSubscription::updateOrCreate(
            [
                'endpoint' => $payload['endpoint'],
            ],
            [
                'user_id' => $user->id,
                'p256dh' => $payload['keys']['p256dh'],
                'auth_key' => $payload['keys']['auth'],
                'content_encoding' => $payload['content_encoding'] ?? 'aes128gcm',
                'user_agent' => $payload['user_agent'] ?? null,
                'last_seen_at' => now(),
                'is_active' => true,
            ]
        );
    }

    public function removeSubscription(User $user, string $endpoint): int
    {
        return PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->delete();
    }

    public function subscriptionsForUser(User $user): Collection
    {
        return PushSubscription::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderByDesc('last_seen_at')
            ->get();
    }

    public function sendToUser(User $user, ?Notification $notification = null): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $subscriptions = $this->subscriptionsForUser($user);
        if ($subscriptions->isEmpty()) {
            return;
        }

        foreach ($subscriptions as $subscription) {
            $this->sendToSubscription($subscription, $notification);
        }
    }

    public function sendToSubscription(PushSubscription $subscription, ?Notification $notification = null): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $audience = $this->audienceFromEndpoint($subscription->endpoint);
        if ($audience === null) {
            return;
        }

        $jwt = $this->createVapidJwt($audience);
        if ($jwt === null) {
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'vapid t=' . $jwt . ', k=' . $this->publicKey(),
                'TTL' => '60',
                'Urgency' => 'high',
            ])
                ->withBody('', 'application/octet-stream')
                ->timeout(10)
                ->send('POST', $subscription->endpoint);

            if (in_array($response->status(), [404, 410], true)) {
                $subscription->delete();
                return;
            }

            if (!$response->successful()) {
                Log::warning('Web push delivery failed', [
                    'subscription_id' => $subscription->id,
                    'endpoint' => $subscription->endpoint,
                    'status' => $response->status(),
                    'notification_id' => $notification?->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Web push request threw an exception', [
                'subscription_id' => $subscription->id,
                'endpoint' => $subscription->endpoint,
                'notification_id' => $notification?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function audienceFromEndpoint(string $endpoint): ?string
    {
        $parts = parse_url($endpoint);

        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $audience = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $audience .= ':' . $parts['port'];
        }

        return $audience;
    }

    private function createVapidJwt(string $audience): ?string
    {
        $privateKeyPem = $this->privateKeyPem();
        $subject = $this->subject();

        if ($privateKeyPem === null || $subject === null) {
            return null;
        }

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'ES256',
            'typ' => 'JWT',
        ], JSON_UNESCAPED_SLASHES));

        $payload = $this->base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => now()->addHours(12)->timestamp,
            'sub' => $subject,
        ], JSON_UNESCAPED_SLASHES));

        $signingInput = $header . '.' . $payload;

        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if ($privateKey === false) {
            return null;
        }

        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        $rawSignature = $this->derToRawSignature($signature, 64);
        if ($rawSignature === null) {
            return null;
        }

        return $signingInput . '.' . $this->base64UrlEncode($rawSignature);
    }

    private function derToRawSignature(string $der, int $length): ?string
    {
        $offset = 0;

        if (ord($der[$offset]) !== 0x30) {
            return null;
        }

        $offset++;
        $this->readAsn1Length($der, $offset);

        $r = $this->readAsn1Integer($der, $offset);
        $s = $this->readAsn1Integer($der, $offset);

        if ($r === null || $s === null) {
            return null;
        }

        $halfLength = (int) ($length / 2);

        return str_pad($r, $halfLength, "\0", STR_PAD_LEFT) . str_pad($s, $halfLength, "\0", STR_PAD_LEFT);
    }

    private function readAsn1Integer(string $der, int &$offset): ?string
    {
        if (!isset($der[$offset]) || ord($der[$offset]) !== 0x02) {
            return null;
        }

        $offset++;
        $length = $this->readAsn1Length($der, $offset);
        if ($length === null) {
            return null;
        }

        $value = substr($der, $offset, $length);
        if ($value === false || $value === '') {
            return null;
        }

        $offset += $length;

        while (strlen($value) > 0 && ord($value[0]) === 0x00) {
            $value = substr($value, 1);
        }

        return $value === '' ? "\0" : $value;
    }

    private function readAsn1Length(string $der, int &$offset): ?int
    {
        if (!isset($der[$offset])) {
            return null;
        }

        $lengthByte = ord($der[$offset]);
        $offset++;

        if (($lengthByte & 0x80) === 0) {
            return $lengthByte;
        }

        $byteCount = $lengthByte & 0x7f;
        if ($byteCount === 0 || $byteCount > 4) {
            return null;
        }

        $length = 0;
        for ($i = 0; $i < $byteCount; $i++) {
            if (!isset($der[$offset])) {
                return null;
            }

            $length = ($length << 8) | ord($der[$offset]);
            $offset++;
        }

        return $length;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
