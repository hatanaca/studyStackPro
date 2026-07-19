<?php

namespace App\Modules\StudySessions\Services;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Exceptions\Domain\ConcurrentSessionException;
use App\Models\StudySession;
use App\Models\User;
use App\Modules\StudySessions\DTOs\StudySessionDTO;
use App\Modules\StudySessions\DTOs\StudySessionFilterDTO;
use App\Modules\StudySessions\Repositories\Contracts\StudySessionRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

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
     * Busca sessão por ID. Lança ModelNotFoundException se não existir,
     * AuthorizationException se não pertencer ao usuário.
     */
    public function findForUser(string $id, string $userId): StudySession
    {
        $session = $this->repository->findById($id);
        if (! $session) {
            throw (new ModelNotFoundException)->setModel(StudySession::class, $id);
        }
        if ($session->user_id !== $userId) {
            throw new AuthorizationException('Acesso negado a este recurso.');
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
        if ($dto->userId !== $userId) {
            throw new AuthorizationException('Acesso negado a este recurso.');
        }

        $session = $this->repository->create($dto);
        event(new StudySessionCreated($session));

        return $session;
    }

    /**
     * Inicia uma nova sessão de estudo (modo foco).
     * Lança ConcurrentSessionException se já existir sessão ativa.
     */
    public function start(User $user, ?string $technologyId): StudySession
    {
        if ($this->getActiveForUser($user->id)) {
            throw new ConcurrentSessionException('O usuário já possui uma sessão ativa.');
        }

        $techId = $technologyId ?? $user->technologies()->first()?->id;

        $dto = new StudySessionDTO(
            userId: $user->id,
            technologyId: $techId,
            startedAt: now(),
            endedAt: null,
            notes: null,
            mood: null,
            title: null,
        );

        try {
            return $this->create($user->id, $dto);
        } catch (QueryException $e) {
            $state = (string) ($e->errorInfo[0] ?? '');
            if ($state === 'P0001') {
                throw new ConcurrentSessionException('O usuário já possui uma sessão ativa.');
            }
            throw $e;
        }
    }

    /**
     * Encerra uma sessão em andamento. Define ended_at = now().
     * Lança InvalidArgumentException se a sessão já estiver finalizada.
     */
    public function end(string $id, string $userId): StudySession
    {
        $session = $this->findForUser($id, $userId);

        if ($session->ended_at) {
            throw new \InvalidArgumentException('Sessão já finalizada.');
        }

        $endedAt = now()->max($session->started_at->copy()->addSecond());

        return $this->update($id, $userId, [
            'ended_at' => $endedAt->toIso8601String(),
        ]);
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
