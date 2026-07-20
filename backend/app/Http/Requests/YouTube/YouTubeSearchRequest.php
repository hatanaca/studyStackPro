<?php

namespace App\Http\Requests\YouTube;

use Illuminate\Foundation\Http\FormRequest;

class YouTubeSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:200'],
            'pageToken' => ['string', 'nullable'],
            'maxResults' => ['integer', 'min:1', 'max:50', 'nullable'],
        ];
    }
}
