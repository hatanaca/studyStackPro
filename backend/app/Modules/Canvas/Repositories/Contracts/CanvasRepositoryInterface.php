<?php

namespace App\Modules\Canvas\Repositories\Contracts;

use App\Models\CanvasArtwork;
use App\Modules\Canvas\DTOs\CanvasArtworkDTO;
use Illuminate\Support\Collection;

interface CanvasRepositoryInterface
{
    public function listForUser(string $userId): Collection;

    public function findForUser(string $id, string $userId): CanvasArtwork;

    public function create(CanvasArtworkDTO $dto): CanvasArtwork;

    public function update(CanvasArtwork $artwork, array $data): CanvasArtwork;

    public function delete(CanvasArtwork $artwork): void;
}
