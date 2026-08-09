<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashcardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deck_id' => $this->deck_id,
            'front_latex' => $this->front_latex,
            'back_latex' => $this->back_latex,
            'scheduling_state' => $this->scheduling_state,
            'fsrs_version' => $this->fsrs_version,
            'due_at' => $this->due_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
