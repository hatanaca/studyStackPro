<?php

namespace App\Http\Requests\Exercises;

use Illuminate\Foundation\Http\FormRequest;

class StoreExerciseTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'kind' => ['required', 'in:numeric,symbolic'],
            'prompt' => ['required', 'string', 'max:4000'],
            'parameters_spec' => ['required', 'array'],
            'answer_expression' => ['required', 'string', 'max:2000'],
            'solution_latex' => ['nullable', 'string', 'max:4000'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:20'],
            'difficulty' => ['nullable', 'integer', 'between:1,5'],
        ];
    }
}
