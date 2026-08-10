<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'kind' => $this->kind,
            'prompt' => $this->prompt,
            'parameters_spec' => $this->parameters_spec,
            'answer_expression' => $this->answer_expression,
            'solution_latex' => $this->solution_latex,
            'variables' => $this->variables,
            'difficulty' => $this->difficulty,
            'is_global' => $this->user_id === null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
