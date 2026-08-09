<?php

namespace App\Http\Requests\Flashcards;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFlashcardDeckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:200'],
        ];
    }
}
