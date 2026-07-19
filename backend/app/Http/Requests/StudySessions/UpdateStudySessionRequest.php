<?php

namespace App\Http\Requests\StudySessions;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudySessionRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'technology_id' => ['nullable', 'uuid', 'exists:technologies,id'],
            'started_at' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
            'ended_at' => [
                'nullable', 'date_format:Y-m-d\TH:i:s',
                function ($attribute, $value, $fail) {
                    if ($this->has('started_at') && $value && $this->started_at >= $value) {
                        $fail('A data de término deve ser posterior à data de início.');
                    }
                },
            ],
            'duration_min' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'mood' => ['nullable', 'integer', 'min:1', 'max:5'],
            'focus_score' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
