<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'email' => $this->email,
            'username' => $this->username,
            'company_name' => $this->company_name,
            'country' => $this->country,
            'status' => $this->verified ? 'active' : 'inactive',
            'email_verified' => $this->hasVerifiedEmail(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Include subscription data if loaded
            'subscription' => $this->whenLoaded('subscription', function () {
                return [
                    'plan_name' => $this->subscription->plan?->name,
                    'status' => $this->subscription->status,
                    'trial_ends_at' => $this->subscription->trial_ends_at?->toIso8601String(),
                ];
            }),
        ];
    }
}
