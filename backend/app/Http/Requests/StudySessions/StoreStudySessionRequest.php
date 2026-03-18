<?php

namespace App\Http\Requests\StudySessions;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $techId = $this->input('technology_id');
        if (! $techId) {
            return true;
        }

        return $this->user()?->technologies()->where('id', $techId)->exists() ?? false;
    }

    public function rules(): array
    {
        return [
            'technology_id' => ['nullable', 'uuid', 'exists:technologies,id'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after:started_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'mood' => ['nullable', 'integer', 'min:1', 'max:5'],
            'focus_score' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
