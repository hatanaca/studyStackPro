<?php

namespace App\Http\Requests\StudySessions;

use Illuminate\Foundation\Http\FormRequest;

class StartStudySessionRequest extends FormRequest
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
        ];
    }
}
