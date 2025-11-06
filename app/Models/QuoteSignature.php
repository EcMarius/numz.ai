<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuoteSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'user_id',
        'signature_data',
        'signature_type',
        'signer_name',
        'signer_email',
        'signer_title',
        'signer_company',
        'ip_address',
        'user_agent',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
