<?php

namespace App\Http\Requests\Goals;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_value' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'status' => ['sometimes', 'string', 'in:active,completed,cancelled'],
            'end_date' => ['nullable', 'date'],
        ];
    }
}
