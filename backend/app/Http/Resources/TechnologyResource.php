<?php

namespace App\Http\Resources;

use App\Models\Technology;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** API Resource para serializar Technology. */
class TechnologyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Technology $technology */
        $technology = $this->resource;

        return [
            'id' => $technology->id,
            'user_id' => $technology->user_id,
            'name' => $technology->name,
            'slug' => $technology->slug,
            'color' => $technology->color,
            'icon' => $technology->icon,
            'description' => $technology->description,
            'is_active' => $technology->is_active,
            'created_at' => $technology->created_at?->toIso8601String(),
            'updated_at' => $technology->updated_at?->toIso8601String(),
        ];
    }
}
