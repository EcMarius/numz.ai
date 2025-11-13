<?php

namespace App\Traits;

use App\Services\AuditService;

trait Auditable
{
    /**
     * Boot the trait
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            app(AuditService::class)->logCreated($model);
        });

        static::updated(function ($model) {
            if ($model->isDirty() && !$model->wasRecentlyCreated) {
                $oldValues = [];
                foreach ($model->getDirty() as $key => $value) {
                    $oldValues[$key] = $model->getOriginal($key);
                }
                app(AuditService::class)->logUpdated($model, $oldValues);
            }
        });

        static::deleted(function ($model) {
            app(AuditService::class)->logDeleted($model);
        });
    }
}
