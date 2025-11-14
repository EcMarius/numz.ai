<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostingService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'hosting_product_id', 'hosting_server_id', 'domain',
        'username', 'password', 'billing_cycle', 'price', 'status',
        'next_due_date', 'activated_at', 'suspended_at', 'terminated_at', 'admin_notes'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'next_due_date' => 'date',
        'activated_at' => 'date',
        'suspended_at' => 'date',
        'terminated_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(HostingProduct::class, 'hosting_product_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(HostingServer::class, 'hosting_server_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'item_id')
            ->where('item_type', 'service')
            ->with('invoice');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasUnpaidInvoices(): bool
    {
        return InvoiceItem::where('item_type', 'service')
            ->where('item_id', $this->id)
            ->whereHas('invoice', function ($query) {
                $query->where('status', 'unpaid');
            })
            ->exists();
    }
}
