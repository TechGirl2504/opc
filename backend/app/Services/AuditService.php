<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Log an action to audit trail
     */
    public function log(
        ?User $user,
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log model creation
     */
    public function logCreate(User $user, string $modelType, int $modelId, array $newValues): AuditLog
    {
        return $this->log($user, $this->getActionName('created', $modelType), $modelType, $modelId, null, $newValues);
    }

    /**
     * Log model update
     */
    public function logUpdate(User $user, string $modelType, int $modelId, array $oldValues, array $newValues): AuditLog
    {
        return $this->log($user, $this->getActionName('updated', $modelType), $modelType, $modelId, $oldValues, $newValues);
    }

    /**
     * Log model deletion
     */
    public function logDelete(User $user, string $modelType, int $modelId, array $oldValues): AuditLog
    {
        return $this->log($user, $this->getActionName('deleted', $modelType), $modelType, $modelId, $oldValues, null);
    }

    /**
     * Log user login
     */
    public function logLogin(User $user): AuditLog
    {
        return $this->log($user, 'user_login', User::class, $user->id);
    }

    /**
     * Log user logout
     */
    public function logLogout(User $user): AuditLog
    {
        return $this->log($user, 'user_logout', User::class, $user->id);
    }

    /**
     * Log password change
     */
    public function logPasswordChange(User $user): AuditLog
    {
        return $this->log($user, 'password_changed', User::class, $user->id);
    }

    /**
     * Log status change
     */
    public function logStatusChange(User $user, string $modelType, int $modelId, $oldStatusId, $newStatusId): AuditLog
    {
        $oldStatus = $oldStatusId ? \App\Models\ApplicationStatus::find($oldStatusId)?->name : null;
        $newStatus = $newStatusId ? \App\Models\ApplicationStatus::find($newStatusId)?->name : null;
        
        return $this->log(
            $user,
            $this->getActionName('status_changed', $modelType),
            $modelType,
            $modelId,
            $oldStatusId ? ['status_id' => $oldStatusId, 'status' => $oldStatus] : null,
            $newStatusId ? ['status_id' => $newStatusId, 'status' => $newStatus] : null
        );
    }

    /**
     * Get action name for model
     */
    private function getActionName(string $action, string $modelType): string
    {
        $modelName = class_basename($modelType);
        $modelName = strtolower($modelName);
        return "{$modelName}_{$action}";
    }

    /**
     * Get audit logs with filters
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50)
    {
        $query = AuditLog::with('user');

        $search = trim((string) ($filters['search'] ?? ''));

        // Filter by user
        if (isset($filters['user_id']) && $filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by action
        if (isset($filters['action']) && $filters['action']) {
            $query->where('action', 'like', "%{$filters['action']}%");
        }

        // Filter by model type
        if (isset($filters['model_type']) && $filters['model_type']) {
            $query->where('model_type', $filters['model_type']);
        }

        // Filter by model ID
        if (isset($filters['model_id']) && $filters['model_id']) {
            $query->where('model_id', $filters['model_id']);
        }

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('action', 'like', $like)
                    ->orWhere('model_type', 'like', $like)
                    ->orWhere('model_id', 'like', $like)
                    ->orWhereHas('user', function ($userQuery) use ($like) {
                        $userQuery->where('username', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        // Date range filters
        if (isset($filters['date_from']) && $filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && $filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }
}
