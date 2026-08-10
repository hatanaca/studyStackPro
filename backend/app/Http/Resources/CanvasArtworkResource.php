<?php

namespace App\Http\Resources;

use App\Models\CanvasArtwork;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CanvasArtworkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var CanvasArtwork $artwork */
        $artwork = $this->resource;

        return [
            'id' => $artwork->id,
            'user_id' => $artwork->user_id,
            'title' => $artwork->title,
            'canvas_data' => $artwork->canvas_data,
            'mural_items' => $artwork->mural_items,
            'width' => $artwork->width,
            'height' => $artwork->height,
            'bg_color' => $artwork->bg_color,
            'created_at' => $artwork->created_at?->toIso8601String(),
            'updated_at' => $artwork->updated_at?->toIso8601String(),
        ];
    }
}
