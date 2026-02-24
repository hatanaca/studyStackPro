<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Technologies\StoreTechnologyRequest;
use App\Http\Requests\Technologies\UpdateTechnologyRequest;
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
        return $this->success($technologies);
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        $limit = min((int) $request->query('limit', 10), 50);
        $technologies = $this->technologyService->search($request->user()->id, $q, $limit);
        return $this->success($technologies);
    }

    public function store(StoreTechnologyRequest $request): JsonResponse
    {
        $tech = $this->technologyService->create(
            $request->user()->id,
            $request->validated()
        );
        return $this->success($tech, 'Tecnologia criada.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tech = $this->technologyService->findForUser($id, $request->user()->id);
        return $this->success($tech);
    }

    public function update(UpdateTechnologyRequest $request, string $id): JsonResponse
    {
        $tech = $this->technologyService->update($id, $request->user()->id, $request->validated());
        return $this->success($tech, 'Tecnologia atualizada.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->technologyService->deactivate($id, $request->user()->id);
        return $this->success(null, 'Tecnologia desativada.');
    }
}
