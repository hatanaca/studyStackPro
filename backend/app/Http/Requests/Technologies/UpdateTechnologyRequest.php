<?php

namespace App\Http\Requests\Technologies;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTechnologyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $technology = $this->route('technology');
        if (! $technology) {
            return true;
        }

        return $this->user()?->technologies()->where('id', $technology)->exists()
            || $this->user()?->technologies()->where('slug', $technology)->exists();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/'],
            'icon' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
