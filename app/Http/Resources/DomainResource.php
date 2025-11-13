<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DomainResource extends JsonResource
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
            'domain_name' => $this->domain_name ?? $this->domain,
            'status' => $this->status,
            'registration_date' => $this->registration_date?->toIso8601String() ?? $this->created_at?->toIso8601String(),
            'expiry_date' => $this->expiry_date?->toIso8601String(),
            'auto_renew' => $this->auto_renew ?? false,
            'privacy_protection' => $this->privacy_protection ?? false,
            'registrar' => $this->registrar,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

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
