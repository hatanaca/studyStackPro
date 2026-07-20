<?php

namespace App\Modules\Reminders\Repositories\Contracts;

use App\Models\Reminder;
use Illuminate\Support\Collection;

interface ReminderRepositoryInterface
{
    public function listForUser(string $userId, ?string $technologyId = null): Collection;

    public function findForUser(string $id, string $userId): Reminder;

    public function create(string $userId, array $data): Reminder;

    public function update(string $id, string $userId, array $data): Reminder;

    public function delete(string $id, string $userId): void;
}
