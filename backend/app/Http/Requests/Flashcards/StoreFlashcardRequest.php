<?php

namespace App\Http\Requests\Flashcards;

use Illuminate\Foundation\Http\FormRequest;

class StoreFlashcardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'front_latex' => ['required', 'string', 'max:4000'],
            'back_latex' => ['required', 'string', 'max:4000'],
        ];
    }
}
