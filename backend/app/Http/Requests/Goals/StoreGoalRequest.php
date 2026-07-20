<?php

namespace App\Http\Requests\Goals;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:minutes_per_week,sessions_per_week,streak_days'],
            'target_value' => ['required', 'integer', 'min:1', 'max:10000'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
