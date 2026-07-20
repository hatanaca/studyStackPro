<?php

namespace App\Http\Requests\StudyPaths;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudyPathRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:200'],
            'technology_id' => ['nullable', 'uuid', 'exists:technologies,id'],
            'nodes' => ['nullable', 'array'],
            'edges' => ['nullable', 'array'],
        ];
    }
}
