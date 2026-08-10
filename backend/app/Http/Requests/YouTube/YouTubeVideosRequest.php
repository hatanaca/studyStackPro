<?php

namespace App\Http\Requests\YouTube;

use Illuminate\Foundation\Http\FormRequest;

class YouTubeVideosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'string', 'max:500'],
        ];
    }
}
