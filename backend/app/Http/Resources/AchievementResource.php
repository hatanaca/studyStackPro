<?php

namespace App\Http\Resources;

use App\Models\Achievement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Achievement $achievement */
        $achievement = $this->resource;

        return [
            'id' => $achievement->id,
            'badge_key' => $achievement->badge_key,
            'title' => $achievement->title,
            'description' => $achievement->description,
            'icon' => $achievement->icon,
            'metadata' => $achievement->metadata,
            'created_at' => $achievement->created_at?->toIso8601String(),
        ];
    }
}
