<?php

namespace App\Http\Requests\Exercises;

use Illuminate\Foundation\Http\FormRequest;

class SolveExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expression' => ['required', 'string', 'max:2000'],
            'variable' => ['required', 'string', 'max:20'],
        ];
    }
}
