<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Canvas\StoreCanvasArtworkRequest;
use App\Http\Requests\Canvas\UpdateCanvasArtworkRequest;
use App\Http\Resources\CanvasArtworkResource;
use App\Modules\Canvas\Services\CanvasService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CanvasController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private CanvasService $canvasService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $artworks = $this->canvasService->listForUser($request->user()->id);

        return $this->success(CanvasArtworkResource::collection($artworks));
    }

    public function store(StoreCanvasArtworkRequest $request): JsonResponse
    {
        $artwork = $this->canvasService->create($request->user()->id, $request->validated());

        return $this->success(new CanvasArtworkResource($artwork), 'Artwork criado.', 201);
    }

    public function show(Request $request, string $artwork): JsonResponse
    {
        $model = $this->canvasService->findForUser($artwork, $request->user()->id);

        return $this->success(new CanvasArtworkResource($model));
    }

    public function update(UpdateCanvasArtworkRequest $request, string $artwork): JsonResponse
    {
        $updated = $this->canvasService->update($artwork, $request->user()->id, $request->validated());

        return $this->success(new CanvasArtworkResource($updated), 'Artwork atualizado.');
    }

    public function destroy(Request $request, string $artwork): JsonResponse
    {
        $this->canvasService->delete($artwork, $request->user()->id);

        return $this->success(null, 'Artwork excluído.');
    }
}
