<?php

namespace App\Modules\Notifications\Repositories;

use App\Models\Notification;
use App\Modules\Notifications\DTOs\NotificationDTO;
use App\Modules\Notifications\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function listForUser(string $userId, ?bool $unreadOnly = null): Collection
    {
        $query = Notification::where('user_id', $userId)->orderByDesc('created_at');
        if ($unreadOnly === true) {
            $query->where('read', false);
        }

        return $query->limit(50)->get();
    }

    public function findForUser(string $id, string $userId): Notification
    {
        $notification = Notification::find($id);
        if (! $notification) {
            throw (new ModelNotFoundException)->setModel(Notification::class, $id);
        }
        if ($notification->user_id !== $userId) {
            throw new AuthorizationException('Acesso negado a este recurso.');
        }

        return $notification;
    }

    public function create(NotificationDTO $dto): Notification
    {
        return Notification::forceCreate([
            'user_id' => $dto->userId,
            'type' => $dto->type,
            'title' => $dto->title,
            'message' => $dto->message,
            'action_url' => $dto->actionUrl,
            'action_label' => $dto->actionLabel,
        ]);
    }

    public function markRead(string $id, string $userId): void
    {
        $notification = $this->findForUser($id, $userId);
        $notification->update(['read' => true]);
    }

    public function markAllRead(string $userId): void
    {
        Notification::where('user_id', $userId)
            ->where('read', false)
            ->update(['read' => true]);
    }

    public function delete(string $id, string $userId): void
    {
        $notification = $this->findForUser($id, $userId);
        $notification->delete();
    }

    public function unreadCount(string $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('read', false)
            ->count();
    }
}
