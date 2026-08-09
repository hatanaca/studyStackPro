<?php

namespace App\Http\Requests\ItaStudy;

use Illuminate\Foundation\Http\FormRequest;

class GenerateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sub_topic_id' => ['required', 'string', 'uuid'],
            'difficulty' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
