<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AgendaDay */
class AgendaDayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_number' => $this->day_number,
            'date' => $this->date?->toDateString(),
            'slots' => AgendaSlotResource::collection($this->whenLoaded('slots')),
        ];
    }
}
