<?php

namespace App\Modules\Canvas\Services;

use App\Models\CanvasArtwork;
use App\Modules\Canvas\DTOs\CanvasArtworkDTO;
use App\Modules\Canvas\Repositories\Contracts\CanvasRepositoryInterface;
use Illuminate\Support\Collection;

class CanvasService
{
    public function __construct(
        private CanvasRepositoryInterface $repository
    ) {}

    public function listForUser(string $userId): Collection
    {
        return $this->repository->listForUser($userId);
    }

    public function findForUser(string $id, string $userId): CanvasArtwork
    {
        return $this->repository->findForUser($id, $userId);
    }

    public function create(string $userId, array $data): CanvasArtwork
    {
        $dto = new CanvasArtworkDTO(
            userId: $userId,
            title: $data['title'] ?? 'Sem título',
            canvasData: $data['canvas_data'] ?? null,
            muralItems: $data['mural_items'] ?? null,
            width: $data['width'] ?? 800,
            height: $data['height'] ?? 600,
            bgColor: $data['bg_color'] ?? '#ffffff',
        );

        return $this->repository->create($dto);
    }

    public function update(string $id, string $userId, array $data): CanvasArtwork
    {
        $artwork = $this->repository->findForUser($id, $userId);

        return $this->repository->update($artwork, $data);
    }

    public function delete(string $id, string $userId): void
    {
        $artwork = $this->repository->findForUser($id, $userId);
        $this->repository->delete($artwork);
    }
}
