<?php

namespace App\Http\Requests\StudySessions;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/** Query string de GET /study-sessions: filtros e paginação validados antes do repositório. */
class IndexStudySessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technology_id' => ['sometimes', 'nullable', 'uuid'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'min_duration' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100000'],
            'mood' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'status' => ['sometimes', 'nullable', 'string', 'in:active,completed'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $from = $this->input('date_from');
            $to = $this->input('date_to');
            if (! $from || ! $to) {
                return;
            }
            if (Carbon::parse($from)->gt(Carbon::parse($to))) {
                $validator->errors()->add('date_to', 'A data final deve ser igual ou posterior à inicial.');
            }
        });
    }
}
