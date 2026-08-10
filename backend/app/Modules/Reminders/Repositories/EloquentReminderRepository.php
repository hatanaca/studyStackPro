<?php

namespace App\Modules\Reminders\Repositories;

use App\Models\Reminder;
use App\Modules\Reminders\Repositories\Contracts\ReminderRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class EloquentReminderRepository implements ReminderRepositoryInterface
{
    public function listForUser(string $userId, ?string $technologyId = null): Collection
    {
        $query = Reminder::where('user_id', $userId);

        if ($technologyId) {
            $query->where('technology_id', $technologyId);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function findForUser(string $id, string $userId): Reminder
    {
        $reminder = Reminder::find($id);

        if (! $reminder) {
            throw (new ModelNotFoundException)->setModel(Reminder::class, $id);
        }

        if ($reminder->user_id !== $userId) {
            throw new AuthorizationException('Acesso negado a este lembrete.');
        }

        return $reminder;
    }

    public function create(string $userId, array $data): Reminder
    {
        return Reminder::forceCreate(array_merge($data, ['user_id' => $userId]));
    }

    public function update(string $id, string $userId, array $data): Reminder
    {
        $reminder = $this->findForUser($id, $userId);
        $reminder->update($data);

        return $reminder->fresh();
    }

    public function delete(string $id, string $userId): void
    {
        $reminder = $this->findForUser($id, $userId);
        $reminder->delete();
    }
}
