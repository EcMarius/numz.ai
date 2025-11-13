<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Activity types
     */
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_CREATE = 'create';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';
    const TYPE_VIEW = 'view';
    const TYPE_EXPORT = 'export';
    const TYPE_IMPORT = 'import';
    const TYPE_DOWNLOAD = 'download';
    const TYPE_UPLOAD = 'upload';
    const TYPE_SETTINGS_CHANGE = 'settings_change';
    const TYPE_PERMISSION_CHANGE = 'permission_change';
    const TYPE_PASSWORD_CHANGE = 'password_change';
    const TYPE_2FA_ENABLE = '2fa_enable';
    const TYPE_2FA_DISABLE = '2fa_disable';
    const TYPE_API_KEY_CREATE = 'api_key_create';
    const TYPE_API_KEY_DELETE = 'api_key_delete';
    const TYPE_FAILED_LOGIN = 'failed_login';
    const TYPE_SUSPICIOUS = 'suspicious';

    /**
     * Log an activity
     */
    public function log(
        string $type,
        string $description,
        ?User $user = null,
        ?string $model = null,
        ?int $modelId = null,
        ?array $properties = null,
        ?Request $request = null
    ): ActivityLog {
        $user = $user ?? Auth::user();
        $request = $request ?? request();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'type' => $type,
            'description' => $description,
            'model_type' => $model,
            'model_id' => $modelId,
            'properties' => $properties,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
        ]);
    }

    /**
     * Log login activity
     */
    public function logLogin(User $user, ?Request $request = null): ActivityLog
    {
        return $this->log(
            self::TYPE_LOGIN,
            "User logged in",
            $user,
            null,
            null,
            ['email' => $user->email],
            $request
        );
    }

    /**
     * Log logout activity
     */
    public function logLogout(?User $user = null, ?Request $request = null): ActivityLog
    {
        return $this->log(
            self::TYPE_LOGOUT,
            "User logged out",
            $user,
            null,
            null,
            null,
            $request
        );
    }

    /**
     * Log failed login attempt
     */
    public function logFailedLogin(string $email, ?Request $request = null): ActivityLog
    {
        return $this->log(
            self::TYPE_FAILED_LOGIN,
            "Failed login attempt for: {$email}",
            null,
            null,
            null,
            ['email' => $email, 'timestamp' => now()->toDateTimeString()],
            $request
        );
    }

    /**
     * Log model creation
     */
    public function logCreate(object $model, string $description = null, ?User $user = null): ActivityLog
    {
        $description = $description ?? "Created " . class_basename($model);

        return $this->log(
            self::TYPE_CREATE,
            $description,
            $user,
            get_class($model),
            $model->id ?? null,
            ['attributes' => $model->getAttributes()]
        );
    }

    /**
     * Log model update
     */
    public function logUpdate(object $model, array $changes = [], string $description = null, ?User $user = null): ActivityLog
    {
        $description = $description ?? "Updated " . class_basename($model);

        return $this->log(
            self::TYPE_UPDATE,
            $description,
            $user,
            get_class($model),
            $model->id ?? null,
            ['changes' => $changes]
        );
    }

    /**
     * Log model deletion
     */
    public function logDelete(object $model, string $description = null, ?User $user = null): ActivityLog
    {
        $description = $description ?? "Deleted " . class_basename($model);

        return $this->log(
            self::TYPE_DELETE,
            $description,
            $user,
            get_class($model),
            $model->id ?? null,
            ['attributes' => $model->getAttributes()]
        );
    }

    /**
     * Log view activity
     */
    public function logView(object $model, string $description = null, ?User $user = null): ActivityLog
    {
        $description = $description ?? "Viewed " . class_basename($model);

        return $this->log(
            self::TYPE_VIEW,
            $description,
            $user,
            get_class($model),
            $model->id ?? null
        );
    }

    /**
     * Log settings change
     */
    public function logSettingsChange(string $key, $oldValue, $newValue, ?User $user = null): ActivityLog
    {
        return $this->log(
            self::TYPE_SETTINGS_CHANGE,
            "Changed setting: {$key}",
            $user,
            null,
            null,
            [
                'key' => $key,
                'old_value' => $oldValue,
                'new_value' => $newValue,
            ]
        );
    }

    /**
     * Log permission change
     */
    public function logPermissionChange(User $targetUser, string $action, $details, ?User $user = null): ActivityLog
    {
        return $this->log(
            self::TYPE_PERMISSION_CHANGE,
            "Permission changed for user: {$targetUser->email}",
            $user,
            User::class,
            $targetUser->id,
            [
                'action' => $action,
                'details' => $details,
            ]
        );
    }

    /**
     * Log password change
     */
    public function logPasswordChange(?User $user = null): ActivityLog
    {
        $user = $user ?? Auth::user();

        return $this->log(
            self::TYPE_PASSWORD_CHANGE,
            "Password changed",
            $user
        );
    }

    /**
     * Log 2FA enable
     */
    public function log2FAEnable(?User $user = null): ActivityLog
    {
        $user = $user ?? Auth::user();

        return $this->log(
            self::TYPE_2FA_ENABLE,
            "Two-factor authentication enabled",
            $user
        );
    }

    /**
     * Log 2FA disable
     */
    public function log2FADisable(?User $user = null): ActivityLog
    {
        $user = $user ?? Auth::user();

        return $this->log(
            self::TYPE_2FA_DISABLE,
            "Two-factor authentication disabled",
            $user
        );
    }

    /**
     * Log suspicious activity
     */
    public function logSuspicious(string $description, ?User $user = null, ?array $properties = null): ActivityLog
    {
        return $this->log(
            self::TYPE_SUSPICIOUS,
            $description,
            $user,
            null,
            null,
            $properties
        );
    }

    /**
     * Get recent activities for user
     */
    public function getRecentActivities(User $user, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities by type
     */
    public function getActivitiesByType(string $type, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::where('type', $type)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities for model
     */
    public function getModelActivities(object $model, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return ActivityLog::where('model_type', get_class($model))
            ->where('model_id', $model->id ?? null)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Detect suspicious activity patterns
     */
    public function detectSuspiciousActivity(User $user): array
    {
        $suspicious = [];

        // Check for multiple failed login attempts
        $failedLogins = ActivityLog::where('type', self::TYPE_FAILED_LOGIN)
            ->where('properties->email', $user->email)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($failedLogins >= 5) {
            $suspicious[] = [
                'type' => 'multiple_failed_logins',
                'count' => $failedLogins,
                'message' => "Multiple failed login attempts detected ({$failedLogins} in last hour)"
            ];
        }

        // Check for logins from multiple IPs
        $uniqueIps = ActivityLog::where('user_id', $user->id)
            ->where('type', self::TYPE_LOGIN)
            ->where('created_at', '>', now()->subDay())
            ->distinct('ip_address')
            ->count('ip_address');

        if ($uniqueIps >= 5) {
            $suspicious[] = [
                'type' => 'multiple_ips',
                'count' => $uniqueIps,
                'message' => "Logins from multiple IP addresses ({$uniqueIps} in last 24 hours)"
            ];
        }

        // Check for unusual activity volume
        $recentActivities = ActivityLog::where('user_id', $user->id)
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recentActivities >= 100) {
            $suspicious[] = [
                'type' => 'high_activity_volume',
                'count' => $recentActivities,
                'message' => "Unusually high activity volume ({$recentActivities} actions in last hour)"
            ];
        }

        return $suspicious;
    }

    /**
     * Export activities to array
     */
    public function exportActivities(User $user): array
    {
        return ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($activity) {
                return [
                    'date' => $activity->created_at->toDateTimeString(),
                    'type' => $activity->type,
                    'description' => $activity->description,
                    'ip_address' => $activity->ip_address,
                    'user_agent' => $activity->user_agent,
                ];
            })
            ->toArray();
    }

    /**
     * Clean old activities
     */
    public function cleanOldActivities(int $daysToKeep = 90): int
    {
        return ActivityLog::where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }
}
