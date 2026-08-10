<?php

namespace App\Http\Requests\Canvas;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCanvasArtworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:200'],
            'canvas_data' => ['nullable', 'array'],
            'mural_items' => ['nullable', 'array'],
            'width' => ['sometimes', 'integer', 'min:100', 'max:5000'],
            'height' => ['sometimes', 'integer', 'min:100', 'max:5000'],
            'bg_color' => ['sometimes', 'string', 'max:20'],
        ];
    }
}
