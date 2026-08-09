<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlashcardReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'flashcard_id' => $this->flashcard_id,
            'rating' => $this->rating,
            'state_after' => $this->state_after,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
        ];
    }
}
