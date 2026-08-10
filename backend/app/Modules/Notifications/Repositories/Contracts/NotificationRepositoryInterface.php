<?php

namespace App\Modules\Notifications\Repositories\Contracts;

use App\Models\Notification;
use App\Modules\Notifications\DTOs\NotificationDTO;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    public function listForUser(string $userId, ?bool $unreadOnly = null): Collection;

    public function findForUser(string $id, string $userId): Notification;

    public function create(NotificationDTO $dto): Notification;

    public function markRead(string $id, string $userId): void;

    public function markAllRead(string $userId): void;

    public function delete(string $id, string $userId): void;

    public function unreadCount(string $userId): int;
}
