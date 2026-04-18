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
            'technology_id' => ['sometimes', 'uuid', 'exists:technologies,id'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'started_at' => ['sometimes', 'date'],
            // 'after:started_at' só se aplica quando started_at também está presente
            // na requisição; PATCH parcial com apenas ended_at não deve falhar.
            'ended_at' => array_filter([
                'nullable',
                'date',
                $this->has('started_at') ? 'after:started_at' : null,
            ]),
            'notes' => ['nullable', 'string', 'max:2000'],
            'mood' => ['nullable', 'integer', 'min:1', 'max:5'],
            'focus_score' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
