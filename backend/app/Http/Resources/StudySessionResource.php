<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** API Resource para serializar StudySession. Inclui technology quando eager-loaded. */
class StudySessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'technology_id' => $this->technology_id,
            'technology' => $this->whenLoaded('technology', fn () => [
                'id' => $this->technology->id,
                'name' => $this->technology->name,
                'slug' => $this->technology->slug,
                'color' => $this->technology->color,
            ]),
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_min' => $this->duration_min,
            'duration_formatted' => $this->duration_formatted,
            'notes' => $this->notes,
            'mood' => $this->mood,
            'focus_score' => $this->focus_score,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
