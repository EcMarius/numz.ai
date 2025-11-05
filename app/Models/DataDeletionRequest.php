<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DataDeletionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'facebook_user_id',
        'reason',
        'status',
        'confirmation_code',
        'ip_address',
        'user_agent',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->confirmation_code)) {
                $request->confirmation_code = 'DR-' . strtoupper(Str::random(12));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
