<?php

namespace App\Http\Resources;

use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Goal $goal */
        $goal = $this->resource;

        return [
            'id' => $goal->id,
            'user_id' => $goal->user_id,
            'type' => $goal->type,
            'target_value' => $goal->target_value,
            'current_value' => $goal->current_value,
            'status' => $goal->status,
            'start_date' => $goal->start_date?->toDateString(),
            'end_date' => $goal->end_date?->toDateString(),
            'meta' => $goal->meta,
            'created_at' => $goal->created_at?->toIso8601String(),
            'updated_at' => $goal->updated_at?->toIso8601String(),
        ];
    }
}
