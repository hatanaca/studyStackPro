<?php

namespace App\Http\Requests\Exercises;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExerciseTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:200'],
            'kind' => ['sometimes', 'in:numeric,symbolic'],
            'prompt' => ['sometimes', 'string', 'max:4000'],
            'parameters_spec' => ['sometimes', 'array'],
            'answer_expression' => ['sometimes', 'string', 'max:2000'],
            'solution_latex' => ['nullable', 'string', 'max:4000'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:20'],
            'difficulty' => ['nullable', 'integer', 'between:1,5'],
        ];
    }
}
