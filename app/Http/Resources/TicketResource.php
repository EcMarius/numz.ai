<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            'ticket_number' => $this->ticket_number,
            'user_id' => $this->user_id,
            'subject' => $this->subject,
            'department' => $this->department,
            'priority' => $this->priority,
            'status' => $this->status,
            'is_open' => $this->isOpen(),
            'is_closed' => $this->isClosed(),
            'assigned_to' => $this->assigned_to,
            'reply_count' => $this->reply_count ?? $this->replies_count,
            'last_reply_at' => $this->last_reply_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Include replies if loaded
            'replies' => TicketReplyResource::collection($this->whenLoaded('replies')),

            // Include client data if loaded
            'client' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            // Include assigned staff if loaded
            'assigned_staff' => $this->whenLoaded('assignedTo', function () {
                return [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                ];
            }),
        ];
    }
}
