<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class TimeSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => ['sometimes', 'integer', 'min:7', 'max:90'],
        ];
    }

    public function getDays(): int
    {
        return (int) $this->input('days', 30);
    }
}
