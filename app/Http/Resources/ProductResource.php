<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type ?? 'hosting',
            'is_active' => $this->is_active ?? true,
            'pricing' => $this->when(isset($this->pricing), function () {
                return [
                    'monthly' => (float) ($this->monthly_price ?? 0),
                    'quarterly' => (float) ($this->quarterly_price ?? 0),
                    'semi_annually' => (float) ($this->semi_annually_price ?? 0),
                    'annually' => (float) ($this->annually_price ?? 0),
                    'biennially' => (float) ($this->biennially_price ?? 0),
                    'triennially' => (float) ($this->triennially_price ?? 0),
                ];
            }),
            'features' => $this->features ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
