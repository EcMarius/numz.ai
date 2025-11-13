<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log model creation
     */
    public function logCreated(Model $model, ?User $user = null): AuditLog
    {
        return $this->log('created', $model, null, $model->getAttributes(), $user);
    }

    /**
     * Log model update
     */
    public function logUpdated(Model $model, array $oldValues, ?User $user = null): AuditLog
    {
        $newValues = $model->getAttributes();

        // Filter out unchanged values
        $changes = array_filter($newValues, function ($value, $key) use ($oldValues) {
            return !isset($oldValues[$key]) || $oldValues[$key] !== $value;
        }, ARRAY_FILTER_USE_BOTH);

        return $this->log('updated', $model, $oldValues, $changes, $user);
    }

    /**
     * Log model deletion
     */
    public function logDeleted(Model $model, ?User $user = null): AuditLog
    {
        return $this->log('deleted', $model, $model->getAttributes(), null, $user);
    }

    /**
     * Log model restoration
     */
    public function logRestored(Model $model, ?User $user = null): AuditLog
    {
        return $this->log('restored', $model, null, $model->getAttributes(), $user);
    }

    /**
     * Log custom event
     */
    public function logCustom(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $user = null
    ): AuditLog {
        return $this->log($event, $model, $oldValues, $newValues, $user);
    }

    /**
     * Log audit
     */
    protected function log(
        string $event,
        Model $model,
        ?array $oldValues,
        ?array $newValues,
        ?User $user = null
    ): AuditLog {
        $user = $user ?? Auth::user();
        $request = request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $this->filterSensitiveData($oldValues),
            'new_values' => $this->filterSensitiveData($newValues),
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'tags' => $this->generateTags($event, $model),
        ]);
    }

    /**
     * Filter sensitive data from values
     */
    protected function filterSensitiveData(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        $sensitiveFields = [
            'password',
            'password_confirmation',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'remember_token',
            'api_token',
            'api_secret',
            'access_token',
            'refresh_token',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Generate tags for audit
     */
    protected function generateTags(string $event, Model $model): array
    {
        $tags = [
            'event:' . $event,
            'model:' . class_basename($model),
        ];

        // Add additional context tags
        if ($model instanceof User) {
            $tags[] = 'user_management';
        }

        return $tags;
    }

    /**
     * Get audit trail for model
     */
    public function getAuditTrail(Model $model, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->getKey())
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audits by user
     */
    public function getAuditsByUser(User $user, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::where('user_id', $user->id)
            ->with('auditable')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent audits
     */
    public function getRecentAudits(int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::with(['user', 'auditable'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search audits
     */
    public function search(array $filters): \Illuminate\Database\Eloquent\Collection
    {
        $query = AuditLog::query()->with(['user', 'auditable']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (isset($filters['model_type'])) {
            $query->where('auditable_type', $filters['model_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 100)
            ->get();
    }

    /**
     * Export audits
     */
    public function export(array $filters = []): array
    {
        $audits = $this->search($filters);

        return $audits->map(function ($audit) {
            return $audit->toExport();
        })->toArray();
    }

    /**
     * Clean old audits
     */
    public function cleanOldAudits(int $daysToKeep = 365): int
    {
        $audits = AuditLog::where('created_at', '<', now()->subDays($daysToKeep))->get();

        $count = 0;
        foreach ($audits as $audit) {
            if ($audit->forceDeleteAudit()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get audit statistics
     */
    public function getStatistics(int $days = 30): array
    {
        $audits = AuditLog::where('created_at', '>', now()->subDays($days))->get();

        return [
            'total' => $audits->count(),
            'by_event' => $audits->groupBy('event')->map->count(),
            'by_user' => $audits->groupBy('user_id')->map->count(),
            'by_model' => $audits->groupBy('auditable_type')->map->count(),
            'unique_users' => $audits->pluck('user_id')->unique()->count(),
            'unique_ips' => $audits->pluck('ip_address')->unique()->count(),
        ];
    }
}
