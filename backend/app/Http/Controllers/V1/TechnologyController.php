<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Technologies\StoreTechnologyRequest;
use App\Http\Requests\Technologies\UpdateTechnologyRequest;
use App\Http\Resources\TechnologyResource;
use App\Modules\Technologies\Services\TechnologyService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TechnologyController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private TechnologyService $technologyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $technologies = $this->technologyService->listForUser($request->user()->id);

        return $this->success(TechnologyResource::collection($technologies));
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        $limit = min((int) $request->query('limit', 10), 50);
        $technologies = $this->technologyService->search($request->user()->id, $q, $limit);

        return $this->success(TechnologyResource::collection($technologies));
    }

    public function store(StoreTechnologyRequest $request): JsonResponse
    {
        $tech = $this->technologyService->create(
            $request->user()->id,
            $request->validated()
        );

        return $this->success(new TechnologyResource($tech), 'Tecnologia criada.', 201);
    }

    public function show(Request $request, string $technology): JsonResponse
    {
        $tech = $this->technologyService->findForUser($technology, $request->user()->id);

        return $this->success(new TechnologyResource($tech));
    }

    public function update(UpdateTechnologyRequest $request, string $technology): JsonResponse
    {
        $tech = $this->technologyService->update($technology, $request->user()->id, $request->validated());

        return $this->success(new TechnologyResource($tech), 'Tecnologia atualizada.');
    }

    public function destroy(Request $request, string $technology): JsonResponse
    {
        $this->technologyService->deactivate($technology, $request->user()->id);

        return $this->success(null, 'Tecnologia desativada.');
    }
}
