<?php

namespace App\Http\Requests\CodeExecution;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10000'],
            'language' => ['required', 'string', 'in:javascript,php,lua,html,css,sql,laravel'],
        ];
    }
}
