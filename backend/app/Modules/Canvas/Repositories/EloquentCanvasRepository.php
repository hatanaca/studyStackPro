<?php

namespace App\Modules\Canvas\Repositories;

use App\Models\CanvasArtwork;
use App\Modules\Canvas\DTOs\CanvasArtworkDTO;
use App\Modules\Canvas\Repositories\Contracts\CanvasRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class EloquentCanvasRepository implements CanvasRepositoryInterface
{
    public function listForUser(string $userId): Collection
    {
        return CanvasArtwork::where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findForUser(string $id, string $userId): CanvasArtwork
    {
        $artwork = CanvasArtwork::find($id);
        if (! $artwork) {
            throw (new ModelNotFoundException)->setModel(CanvasArtwork::class, $id);
        }
        if ($artwork->user_id !== $userId) {
            throw new AuthorizationException('Acesso negado a este recurso.');
        }

        return $artwork;
    }

    public function create(CanvasArtworkDTO $dto): CanvasArtwork
    {
        return CanvasArtwork::forceCreate([
            'user_id' => $dto->userId,
            'title' => $dto->title,
            'canvas_data' => $dto->canvasData,
            'mural_items' => $dto->muralItems,
            'width' => $dto->width,
            'height' => $dto->height,
            'bg_color' => $dto->bgColor,
        ]);
    }

    public function update(CanvasArtwork $artwork, array $data): CanvasArtwork
    {
        $artwork->update($data);

        return $artwork->fresh();
    }

    public function delete(CanvasArtwork $artwork): void
    {
        $artwork->delete();
    }
}
