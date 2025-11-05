<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SmtpConfig extends Model
{
    protected $fillable = [
        'name',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
    ];

    /**
     * Automatically encrypt password when setting
     */
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Automatically decrypt password when getting
     */
    public function getPasswordAttribute($value): ?string
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Creator of this SMTP config
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get config as array for Laravel mailer
     */
    public function toMailConfig(): array
    {
        return [
            'transport' => 'smtp',
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption !== 'none' ? $this->encryption : null,
            'username' => $this->username,
            'password' => $this->password,
            'from' => [
                'address' => $this->from_address,
                'name' => $this->from_name,
            ],
        ];
    }
}
