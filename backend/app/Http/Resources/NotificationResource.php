<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Notification $notification */
        $notification = $this->resource;

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'read' => $notification->read,
            'action_url' => $notification->action_url,
            'action_label' => $notification->action_label,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
