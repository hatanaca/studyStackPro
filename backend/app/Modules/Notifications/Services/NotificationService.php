<?php

namespace App\Modules\Notifications\Services;

use App\Models\Notification;
use App\Modules\Notifications\DTOs\NotificationDTO;
use App\Modules\Notifications\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Collection;

class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $repository
    ) {}

    public function listForUser(string $userId, ?bool $unreadOnly = null): Collection
    {
        return $this->repository->listForUser($userId, $unreadOnly);
    }

    public function create(string $userId, array $data): Notification
    {
        $dto = new NotificationDTO(
            userId: $userId,
            type: $data['type'],
            title: $data['title'],
            message: $data['message'] ?? null,
            actionUrl: $data['action_url'] ?? null,
            actionLabel: $data['action_label'] ?? null,
        );

        return $this->repository->create($dto);
    }

    public function markRead(string $id, string $userId): void
    {
        $this->repository->markRead($id, $userId);
    }

    public function markAllRead(string $userId): void
    {
        $this->repository->markAllRead($userId);
    }

    public function delete(string $id, string $userId): void
    {
        $this->repository->delete($id, $userId);
    }

    public function unreadCount(string $userId): int
    {
        return $this->repository->unreadCount($userId);
    }
}
