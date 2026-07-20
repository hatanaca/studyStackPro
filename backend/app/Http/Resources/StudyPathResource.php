<?php

namespace App\Http\Resources;

use App\Models\StudyPath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyPathResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var StudyPath $path */
        $path = $this->resource;

        return [
            'id' => $path->id,
            'user_id' => $path->user_id,
            'title' => $path->title,
            'technology_id' => $path->technology_id,
            'nodes' => $path->nodes,
            'edges' => $path->edges,
            'created_at' => $path->created_at?->toIso8601String(),
            'updated_at' => $path->updated_at?->toIso8601String(),
        ];
    }
}
