<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyPaths\StoreStudyPathRequest;
use App\Http\Requests\StudyPaths\UpdateStudyPathRequest;
use App\Http\Resources\StudyPathResource;
use App\Modules\StudyPaths\Services\StudyPathService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudyPathController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly StudyPathService $studyPathService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paths = $this->studyPathService->listForUser($request->user()->id);

        return $this->success(StudyPathResource::collection($paths));
    }

    public function store(StoreStudyPathRequest $request): JsonResponse
    {
        $path = $this->studyPathService->create($request->user()->id, $request->validated());

        return $this->success(new StudyPathResource($path), 'Mapa de estudo criado.', 201);
    }

    public function show(Request $request, string $path): JsonResponse
    {
        $model = $this->studyPathService->findForUser($path, $request->user()->id);

        return $this->success(new StudyPathResource($model));
    }

    public function update(UpdateStudyPathRequest $request, string $path): JsonResponse
    {
        $updated = $this->studyPathService->update($path, $request->user()->id, $request->validated());

        return $this->success(new StudyPathResource($updated), 'Mapa de estudo atualizado.');
    }

    public function destroy(Request $request, string $path): JsonResponse
    {
        $this->studyPathService->delete($path, $request->user()->id);

        return $this->success(null, 'Mapa de estudo excluído.');
    }

    public function byTechnology(Request $request, string $technologyId): JsonResponse
    {
        $path = $this->studyPathService->findByTechnology($request->user()->id, $technologyId);

        return $this->success($path ? new StudyPathResource($path) : null);
    }
}
