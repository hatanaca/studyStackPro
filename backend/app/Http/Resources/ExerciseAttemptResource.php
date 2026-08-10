<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'variant_id' => $this->variant_id,
            'template_title' => $this->variant?->template?->title,
            'answer' => $this->answer,
            'is_correct' => $this->is_correct,
            'feedback_latex' => $this->feedback_latex,
            'expected_latex' => $this->expected_latex,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
