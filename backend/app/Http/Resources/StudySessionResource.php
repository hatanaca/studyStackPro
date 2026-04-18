<?php

namespace App\Http\Resources;

use App\Models\StudySession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** API Resource para serializar StudySession. Inclui technology quando eager-loaded. */
class StudySessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var StudySession $session */
        $session = $this->resource;

        $attrs = $session->getAttributes();

        return [
            'id' => $session->id,
            'user_id' => $session->user_id,
            'technology_id' => $session->technology_id,
            // Evita MissingAttributeException com Model::shouldBeStrict() antes da migração `title`.
            'title' => array_key_exists('title', $attrs) ? $attrs['title'] : null,
            'technology' => $this->when($session->relationLoaded('technology'), function () use ($session) {
                if ($session->technology === null) {
                    return null;
                }

                return [
                    'id' => $session->technology->id,
                    'name' => $session->technology->name,
                    'slug' => $session->technology->slug,
                    'color' => $session->technology->color,
                ];
            }),
            'started_at' => $session->started_at?->toIso8601String(),
            'ended_at' => $session->ended_at?->toIso8601String(),
            'duration_min' => $session->duration_min,
            'productivity_score' => $session->productivity_score,
            'duration_formatted' => $session->duration_formatted,
            'notes' => $session->notes,
            'mood' => $session->mood,
            'focus_score' => $session->focus_score,
            'created_at' => $session->created_at?->toIso8601String(),
            'updated_at' => $session->updated_at?->toIso8601String(),
        ];
    }
}
