<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:info,success'],
            'title' => ['required', 'string', 'max:200'],
            'message' => ['nullable', 'string', 'max:1000'],
            'action_url' => ['nullable', 'string', 'max:500'],
            'action_label' => ['nullable', 'string', 'max:100'],
        ];
    }
}
