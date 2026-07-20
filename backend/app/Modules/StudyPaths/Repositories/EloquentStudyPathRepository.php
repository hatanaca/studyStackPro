<?php

namespace App\Modules\StudyPaths\Repositories;

use App\Models\StudyPath;
use App\Modules\StudyPaths\DTOs\StudyPathDTO;
use App\Modules\StudyPaths\Repositories\Contracts\StudyPathRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class EloquentStudyPathRepository implements StudyPathRepositoryInterface
{
    public function listForUser(string $userId): Collection
    {
        return StudyPath::where('user_id', $userId)
            ->with('technology')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findForUser(string $id, string $userId): StudyPath
    {
        $path = StudyPath::find($id);
        if (! $path) {
            throw (new ModelNotFoundException)->setModel(StudyPath::class, $id);
        }
        if ($path->user_id !== $userId) {
            throw new AuthorizationException('Acesso negado a este recurso.');
        }

        return $path;
    }

    public function findByTechnology(string $userId, string $technologyId): ?StudyPath
    {
        return StudyPath::where('user_id', $userId)
            ->where('technology_id', $technologyId)
            ->first();
    }

    public function create(StudyPathDTO $dto): StudyPath
    {
        return StudyPath::forceCreate([
            'user_id' => $dto->userId,
            'title' => $dto->title,
            'technology_id' => $dto->technologyId,
            'nodes' => $dto->nodes,
            'edges' => $dto->edges,
        ]);
    }

    public function update(StudyPath $path, array $data): StudyPath
    {
        $path->update($data);

        return $path->fresh();
    }

    public function delete(StudyPath $path): void
    {
        $path->delete();
    }
}
