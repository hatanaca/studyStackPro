<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class HeatmapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['sometimes', 'nullable', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function getYear(): ?int
    {
        $year = $this->input('year');

        return $year !== null && $year !== '' ? (int) $year : null;
    }
}
