<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'product_id', 'server_id', 'domain', 'username', 'password',
        'billing_cycle', 'amount', 'status', 'registration_date', 'next_due_date',
        'next_invoice_date', 'termination_date', 'auto_terminate',
        'override_auto_suspend', 'override_suspend_until', 'dedicated_ip',
        'assigned_ip', 'disk_limit', 'disk_usage', 'bandwidth_limit',
        'bandwidth_usage', 'last_updated', 'configoptions', 'notes', 'subscription_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'registration_date' => 'date',
        'next_due_date' => 'date',
        'next_invoice_date' => 'date',
        'termination_date' => 'date',
        'last_updated' => 'date',
        'auto_terminate' => 'boolean',
        'override_auto_suspend' => 'boolean',
        'dedicated_ip' => 'boolean',
        'configoptions' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
