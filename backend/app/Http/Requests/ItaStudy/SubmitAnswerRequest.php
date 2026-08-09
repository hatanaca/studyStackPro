<?php

namespace App\Http\Requests\ItaStudy;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
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
            'time_spent_seconds' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
