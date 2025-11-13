<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'user_id' => $this->user_id,
            'domain' => $this->domain,
            'username' => $this->username,
            'billing_cycle' => $this->billing_cycle,
            'price' => (float) $this->price,
            'status' => $this->status,
            'next_due_date' => $this->next_due_date?->toIso8601String(),
            'activated_at' => $this->activated_at?->toIso8601String(),
            'suspended_at' => $this->suspended_at?->toIso8601String(),
            'terminated_at' => $this->terminated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Include product data if loaded
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'description' => $this->product->description,
                ];
            }),

            // Include server data if loaded
            'server' => $this->whenLoaded('server', function () {
                return [
                    'id' => $this->server->id,
                    'name' => $this->server->name,
                    'hostname' => $this->server->hostname,
                ];
            }),
        ];
    }
}
