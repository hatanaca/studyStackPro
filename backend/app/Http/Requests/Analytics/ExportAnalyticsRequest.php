<?php

namespace App\Http\Requests\Analytics;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ExportAnalyticsRequest extends FormRequest
{
    private const MAX_DAYS = 366;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start' => [
                'required',
                'date',
                'before_or_equal:end',
            ],
            'end' => [
                'required',
                'date',
                'after_or_equal:start',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'start.required' => 'A data inicial é obrigatória.',
            'start.date' => 'A data inicial deve ser uma data válida.',
            'start.before_or_equal' => 'A data inicial deve ser igual ou anterior à data final.',
            'end.required' => 'A data final é obrigatória.',
            'end.date' => 'A data final deve ser uma data válida.',
            'end.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $start = $this->input('start');
            $end = $this->input('end');
            if (! $start || ! $end) {
                return;
            }
            $days = Carbon::parse($start)->diffInDays(Carbon::parse($end));
            if ($days > self::MAX_DAYS) {
                $validator->errors()->add(
                    'end',
                    'O intervalo não pode ser maior que '.self::MAX_DAYS.' dias.'
                );
            }
        });
    }
}
