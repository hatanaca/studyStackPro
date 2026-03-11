<?php

namespace App\Modules\StudySessions\Repositories;

use App\Models\StudySession;
use App\Modules\StudySessions\DTOs\StudySessionDTO;
use App\Modules\StudySessions\Repositories\Contracts\StudySessionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Implementação Eloquent do repositório de sessões.
 * Filtros: technology_id, date_from/to, min_duration, mood, status (active/completed).
 */
class EloquentStudySessionRepository implements StudySessionRepositoryInterface
{
    /** Lista sessões do usuário com filtros e paginação (máx 50 por página) */
    public function findByUser(string $userId, array $filters): LengthAwarePaginator
    {
        $query = StudySession::where('user_id', $userId)
            ->with('technology')
            ->orderBy('started_at', 'desc');

        if (! empty($filters['technology_id'])) {
            $query->where('technology_id', $filters['technology_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('started_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('started_at', '<=', $filters['date_to']);
        }
        if (isset($filters['min_duration']) && $filters['min_duration'] !== '') {
            $query->whereNotNull('duration_min')
                ->where('duration_min', '>=', (int) $filters['min_duration']);
        }
        if (isset($filters['mood']) && $filters['mood'] !== '') {
            $query->where('mood', $filters['mood']);
        }
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->whereNull('ended_at');
            } elseif ($filters['status'] === 'completed') {
                $query->whereNotNull('ended_at');
            }
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->paginate($perPage);
    }

    /** Retorna sessão ativa (ended_at null) mais recente do usuário */
    public function findActiveByUser(string $userId): ?StudySession
    {
        return StudySession::where('user_id', $userId)
            ->whereNull('ended_at')
            ->with('technology')
            ->orderByDesc('started_at')
            ->first();
    }

    /** Busca sessão por ID com tecnologia carregada */
    public function findById(string $id): ?StudySession
    {
        return StudySession::with('technology')->find($id);
    }

    /** Cria sessão a partir do DTO */
    public function create(StudySessionDTO $dto): StudySession
    {
        return StudySession::create([
            'user_id' => $dto->userId,
            'technology_id' => $dto->technologyId ?: null,
            'started_at' => $dto->startedAt,
            'ended_at' => $dto->endedAt,
            'notes' => $dto->notes,
            'mood' => $dto->mood,
            'focus_score' => $dto->focusScore,
        ]);
    }

    /** Atualiza sessão e retorna fresh com technology */
    public function update(StudySession $session, array $data): StudySession
    {
        $session->update($data);

        return $session->fresh(['technology']);
    }

    /** Remove sessão do banco */
    public function delete(StudySession $session): void
    {
        $session->delete();
    }
}
