<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
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
            'description' => $this->description,
            'details' => $this->details,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'amount' => (float) $this->amount,
            'total' => (float) $this->total,
            'item_type' => $this->item_type,
            'item_id' => $this->item_id,
        ];
    }
}
