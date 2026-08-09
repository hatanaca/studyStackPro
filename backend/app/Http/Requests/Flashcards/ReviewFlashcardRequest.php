<?php

namespace App\Http\Requests\Flashcards;

use Illuminate\Foundation\Http\FormRequest;

class ReviewFlashcardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 1=Again, 2=Hard, 3=Good, 4=Easy (FSRS)
            'rating' => ['required', 'integer', 'between:1,4'],
            // snapshot do Card calculado pelo ts-fsrs no cliente
            'state_after' => ['required', 'array'],
            'due_at' => ['required', 'date'],
        ];
    }
}
