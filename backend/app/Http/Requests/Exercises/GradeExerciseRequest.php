<?php

namespace App\Http\Requests\Exercises;

use Illuminate\Foundation\Http\FormRequest;

class GradeExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'string', 'uuid'],
            'answer' => ['required', 'string', 'max:2000'],
        ];
    }
}
