<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Goals\StoreGoalRequest;
use App\Http\Requests\Goals\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Modules\Goals\Services\GoalService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private GoalService $goalService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $goals = $this->goalService->listForUser($request->user()->id, $status);

        return $this->success(GoalResource::collection($goals));
    }

    public function store(StoreGoalRequest $request): JsonResponse
    {
        $goal = $this->goalService->create(
            $request->user()->id,
            $request->validated()
        );

        return $this->success(new GoalResource($goal), 'Meta criada.', 201);
    }

    public function show(Request $request, string $goal): JsonResponse
    {
        $goalModel = $this->goalService->findForUser($goal, $request->user()->id);

        return $this->success(new GoalResource($goalModel));
    }

    public function update(UpdateGoalRequest $request, string $goal): JsonResponse
    {
        $updated = $this->goalService->update($goal, $request->user()->id, $request->validated());

        return $this->success(new GoalResource($updated), 'Meta atualizada.');
    }

    public function destroy(Request $request, string $goal): JsonResponse
    {
        $this->goalService->delete($goal, $request->user()->id);

        return $this->success(null, 'Meta excluída.');
    }
}
