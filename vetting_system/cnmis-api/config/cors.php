<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * ✅ Production:
     * - Set `CORS_ALLOWED_ORIGINS` to your SPA origin(s), comma-separated.
     *   Example: CORS_ALLOWED_ORIGINS=https://cnmis.opc.gov.mw,https://admin.cnmis.opc.gov.mw
     *
     * ✅ Local dev:
     * - Defaults below cover common Vite dev/preview ports.
     *
     * Note: we also include `FRONTEND_URL` and `APP_URL` automatically if you set them,
     * so production can work even with a minimal config.
     */
    'allowed_origins' => (function (): array {
        $parseCsv = static fn (?string $value): array => $value
            ? array_values(array_filter(array_map('trim', explode(',', $value))))
            : [];

        $origins = $parseCsv(env('CORS_ALLOWED_ORIGINS'));

        if (empty($origins)) {
            $origins = [
                // Local dev servers
                'http://localhost:3000',
                'http://localhost:5173',
                'http://localhost:5174',
                'http://localhost:4173',
                'http://localhost:4174',
                'http://127.0.0.1:5173',
                'http://127.0.0.1:5174',
                'http://127.0.0.1:4173',
                'http://127.0.0.1:4174',
            ];
        }

        // Optional convenience for production setups
        foreach (['FRONTEND_URL', 'APP_URL'] as $key) {
            $val = trim((string) env($key, ''));
            if ($val !== '') {
                $origins[] = $val;
            }
        }

        return array_values(array_unique($origins));
    })(),

    /*
     * Optional: allow wildcard subdomains via regex patterns.
     * Example:
     * CORS_ALLOWED_ORIGINS_PATTERNS=#^https://.*\\.cnmis\\.opc\\.gov\\.mw$#
     * Multiple patterns: comma-separated.
     */
    'allowed_origins_patterns' => env('CORS_ALLOWED_ORIGINS_PATTERNS')
        ? array_values(array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS_PATTERNS')))))
        : [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],

    'max_age' => 0,

    'supports_credentials' => true,

];
