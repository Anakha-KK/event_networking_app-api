<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserConnection */
class UserConnectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'attendee_id' => $this->attendee_id,
            'is_first_timer' => $this->is_first_timer,
            'base_points' => $this->base_points,
            'total_points' => $this->total_points,
            'notes_added' => $this->notes_added,
            'notes' => $this->notes,
            'connected_at' => $this->connected_at?->toIso8601String(),
        ];
    }
}
