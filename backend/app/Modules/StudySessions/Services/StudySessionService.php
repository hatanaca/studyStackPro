<?php

namespace App\Modules\StudySessions\Services;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Models\StudySession;
use App\Modules\StudySessions\DTOs\StudySessionDTO;
use App\Modules\StudySessions\DTOs\StudySessionFilterDTO;
use App\Modules\StudySessions\Repositories\Contracts\StudySessionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Serviço de sessões de estudo.
 *
 * Orquestra CRUD, dispara eventos (Created/Updated/Deleted) para listeners que invalidam cache
 * e disparam recálculo de métricas. Garante isolamento por usuário (findForUser).
 */
class StudySessionService
{
    public function __construct(
        private StudySessionRepositoryInterface $repository,
    ) {}

    /**
     * Lista sessões do usuário com filtros e paginação.
     */
    public function listForUser(string $userId, array|StudySessionFilterDTO $filters = []): LengthAwarePaginator
    {
        $filterArray = $filters instanceof StudySessionFilterDTO
            ? $filters->toArray()
            : $filters;

        return $this->repository->findByUser($userId, $filterArray);
    }

    /**
     * Busca sessão por ID. Aborta 404 se não existir, 403 se não pertencer ao usuário.
     */
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

    /**
     * Retorna a sessão ativa (ended_at null) do usuário, ou null.
     */
    public function getActiveForUser(string $userId): ?StudySession
    {
        return $this->repository->findActiveByUser($userId);
    }

    /**
     * Cria sessão e dispara StudySessionCreated (listeners invalidam cache e disparam recálculo).
     */
    public function create(string $userId, StudySessionDTO $dto): StudySession
    {
        $session = $this->repository->create($dto);
        event(new StudySessionCreated($session));

        return $session;
    }

    /**
     * Atualiza sessão e dispara StudySessionUpdated com campos alterados.
     */
    public function update(string $id, string $userId, array $data): StudySession
    {
        $session = $this->findForUser($id, $userId);
        $session = $this->repository->update($session, $data);
        event(new StudySessionUpdated($session, array_keys($data)));

        return $session;
    }

    /**
     * Remove sessão. Dispara StudySessionDeleted antes para que listeners atualizem analytics.
     */
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
