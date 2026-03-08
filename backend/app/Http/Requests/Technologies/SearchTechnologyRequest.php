<?php

namespace App\Http\Requests\Technologies;

use Illuminate\Foundation\Http\FormRequest;

class SearchTechnologyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:200'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function getQuery(): string
    {
        return trim((string) $this->input('q', ''));
    }

    public function getLimit(): int
    {
        return min(50, max(1, (int) $this->input('limit', 10)));
    }
}
