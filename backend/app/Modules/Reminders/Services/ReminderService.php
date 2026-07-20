<?php

namespace App\Modules\Reminders\Services;

use App\Models\Reminder;
use App\Modules\Reminders\Repositories\Contracts\ReminderRepositoryInterface;
use Illuminate\Support\Collection;

class ReminderService
{
    public function __construct(
        private ReminderRepositoryInterface $repository
    ) {}

    public function listForUser(string $userId, ?string $technologyId = null): Collection
    {
        return $this->repository->listForUser($userId, $technologyId);
    }

    public function findForUser(string $id, string $userId): Reminder
    {
        return $this->repository->findForUser($id, $userId);
    }

    public function create(string $userId, array $data): Reminder
    {
        return $this->repository->create($userId, $data);
    }

    public function update(string $id, string $userId, array $data): Reminder
    {
        return $this->repository->update($id, $userId, $data);
    }

    public function delete(string $id, string $userId): void
    {
        $this->repository->delete($id, $userId);
    }
}
