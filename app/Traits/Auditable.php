<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            if (config('numz.audit_enabled', true)) {
                AuditLog::log(
                    'created',
                    $model,
                    [],
                    $model->getAttributes(),
                    ['action' => 'create']
                );
            }
        });

        static::updated(function ($model) {
            if (config('numz.audit_enabled', true)) {
                $changes = $model->getChanges();
                $original = [];

                foreach (array_keys($changes) as $key) {
                    $original[$key] = $model->getOriginal($key);
                }

                AuditLog::log(
                    'updated',
                    $model,
                    $original,
                    $changes,
                    ['action' => 'update']
                );
            }
        });

        static::deleted(function ($model) {
            if (config('numz.audit_enabled', true)) {
                AuditLog::log(
                    'deleted',
                    $model,
                    $model->getAttributes(),
                    [],
                    ['action' => 'delete']
                );
            }
        });
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return AuditLog::where('auditable_type', get_class($this))
            ->where('auditable_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Manually create an audit log
     */
    public function audit(string $event, array $oldValues = [], array $newValues = [], array $tags = [])
    {
        return AuditLog::log($event, $this, $oldValues, $newValues, $tags);
    }
}
