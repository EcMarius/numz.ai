<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnsubscribeRequest extends Model
{
    protected $fillable = [
        'email_address',
        'token',
        'reason',
    ];

    /**
     * Check if an email has unsubscribed
     */
    public static function hasUnsubscribed(string $email): bool
    {
        return static::where('email_address', $email)->exists();
    }

    /**
     * Create unsubscribe request
     */
    public static function createUnsubscribe(string $email, string $token, ?string $reason = null): self
    {
        return static::create([
            'email_address' => $email,
            'token' => $token,
            'reason' => $reason,
        ]);
    }
}
