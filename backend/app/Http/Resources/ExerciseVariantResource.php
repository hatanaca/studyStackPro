<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_id' => $this->template_id,
            'parameters' => $this->parameters,
            'prompt_latex' => $this->prompt_latex,
            'answer_expr' => $this->answer_expr,
            'solution_latex' => $this->solution_latex,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
