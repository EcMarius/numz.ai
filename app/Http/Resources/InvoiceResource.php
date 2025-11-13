<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'type' => $this->type,
            'billing_cycle' => $this->billing_cycle,
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'discount' => (float) $this->discount,
            'late_fee' => (float) $this->late_fee,
            'total' => (float) $this->total,
            'amount_paid' => (float) $this->amount_paid,
            'remaining_balance' => (float) $this->remaining_balance,
            'currency' => $this->currency ?? 'USD',
            'due_date' => $this->due_date?->toIso8601String(),
            'paid_date' => $this->paid_date?->toIso8601String(),
            'is_overdue' => $this->isOverdue(),
            'is_paid' => $this->isPaid(),
            'is_partially_paid' => $this->isPartiallyPaid(),
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Include items if loaded
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),

            // Include client data if loaded
            'client' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
        ];
    }
}
