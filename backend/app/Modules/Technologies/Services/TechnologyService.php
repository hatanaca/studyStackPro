<?php

namespace App\Modules\Technologies\Services;

use App\Models\Technology;
use App\Modules\Technologies\DTOs\TechnologyDTO;
use App\Modules\Technologies\Repositories\Contracts\TechnologyRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Serviço de tecnologias.
 *
 * CRUD e busca. Usa TechnologyDTO para criação. Deactivate = soft delete (is_active=false).
 * Invalida cache de tecnologias ao desativar.
 */
class TechnologyService
{
    public function __construct(
        private TechnologyRepositoryInterface $repository
    ) {}

    /**
     * Lista tecnologias ativas do usuário.
     */
    public function listForUser(string $userId): Collection
    {
        return $this->repository->listForUser($userId);
    }

    /**
     * Busca por nome (ILIKE) com limite. Usado em autocomplete.
     */
    public function search(string $userId, string $query, int $limit = 10): Collection
    {
        return $this->repository->search($userId, $query, $limit);
    }

    /**
     * Busca tecnologia por ID/UUID. Garante que pertence ao usuário.
     */
    public function findForUser(string $id, string $userId): Technology
    {
        return $this->repository->findForUser($id, $userId);
    }

    /**
     * Cria tecnologia a partir de array validado. Converte para TechnologyDTO.
     */
    public function create(string $userId, array $data): Technology
    {
        $dto = new TechnologyDTO(
            userId: $userId,
            name: $data['name'],
            color: $data['color'] ?? null,
            icon: $data['icon'] ?? null,
            description: $data['description'] ?? null,
        );

        return $this->repository->create($dto);
    }

    /**
     * Atualiza tecnologia existente.
     */
    public function update(string $id, string $userId, array $data): Technology
    {
        $tech = $this->repository->findForUser($id, $userId);

        return $this->repository->update($tech, $data);
    }

    /**
     * Desativa tecnologia (soft delete). Invalida cache do usuário.
     */
    public function deactivate(string $id, string $userId): void
    {
        $tech = $this->repository->findForUser($id, $userId);
        $tech->forceFill(['is_active' => false])->save();
        $this->repository->invalidateCacheForUser($userId);
    }
}
