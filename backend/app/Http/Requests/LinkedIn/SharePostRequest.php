<?php

namespace App\Http\Requests\LinkedIn;

use Illuminate\Foundation\Http\FormRequest;

class SharePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:3000'],
        ];
    }
}
