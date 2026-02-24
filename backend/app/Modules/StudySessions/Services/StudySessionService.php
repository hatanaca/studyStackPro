<?php

namespace App\Modules\StudySessions\Services;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Models\StudySession;
use App\Modules\StudySessions\DTOs\StudySessionDTO;
use App\Modules\StudySessions\Repositories\Contracts\StudySessionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StudySessionService
{
    public function __construct(
        private StudySessionRepositoryInterface $repository,
    ) {}

    public function listForUser(string $userId, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->findByUser($userId, $filters);
    }

    public function findForUser(string $id, string $userId): StudySession
    {
        $session = $this->repository->findById($id);
        if (! $session) {
            abort(404);
        }
        if ($session->user_id !== $userId) {
            abort(403);
        }

        return $session;
    }

    public function create(string $userId, StudySessionDTO $dto): StudySession
    {
        $session = $this->repository->create($dto);
        event(new StudySessionCreated($session));

        return $session;
    }

    public function update(string $id, string $userId, array $data): StudySession
    {
        $session = $this->findForUser($id, $userId);
        $session = $this->repository->update($session, $data);
        event(new StudySessionUpdated($session));

        return $session;
    }

    public function delete(string $id, string $userId): void
    {
        $session = $this->findForUser($id, $userId);
        event(new StudySessionDeleted(
            $session->user_id,
            $session->id,
            (int) ($session->duration_min ?? 0),
            $session->started_at
        ));
        $this->repository->delete($session);
    }
}
