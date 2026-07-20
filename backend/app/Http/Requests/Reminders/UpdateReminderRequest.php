<?php

namespace App\Http\Requests\Reminders;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['sometimes', 'string', 'max:500'],
            'completed' => ['sometimes', 'boolean'],
            'technology_id' => ['nullable', 'uuid', 'exists:technologies,id'],
        ];
    }
}
