<?php
namespace App\Http\Controllers\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudySessions\IndexStudySessionsRequest;
use App\Http\Requests\StudySessions\StartStudySessionRequest;
use App\Http\Requests\StudySessions\StoreStudySessionRequest;
use App\Http\Requests\StudySessions\UpdateStudySessionRequest;
use App\Http\Resources\StudySessionResource;
use App\Modules\StudySessions\Services\StudySessionService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudySessionController extends Controller
{
    use HasApiResponse;
    public function __construct(private StudySessionService $studySessionService) {}

    public function index(IndexStudySessionsRequest $request): JsonResponse
    {
        $sessions = $this->studySessionService->listForUser(
            $request->user()->id,
            $request->validated()
        );
        return $this->success(
            StudySessionResource::collection($sessions),
            meta: [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ]
        );
    }

    public function store(StoreStudySessionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $dto = new \App\Modules\StudySessions\DTOs\StudySessionDTO(
            userId: $request->user()->id,
            technologyId: $validated['technology_id'] ?? null,
            startedAt: isset($validated['started_at']) ? \Carbon\Carbon::parse($validated['started_at']) : now(),
            endedAt: isset($validated['ended_at']) ? \Carbon\Carbon::parse($validated['ended_at']) : null,
            notes: $validated['notes'] ?? null,
            mood: $validated['mood'] ?? null,
            focusScore: $validated['focus_score'] ?? null,
            title: $validated['title'] ?? null,
        );
        $session = $this->studySessionService->create($request->user()->id, $dto);
        return $this->success(new StudySessionResource($session->load('technology')), 'Sessão criada.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $session = $this->studySessionService->findForUser($id, request()->user()->id);
        return $this->success(new StudySessionResource($session->load('technology')));
    }

    public function update(UpdateStudySessionRequest $request, string $id): JsonResponse
    {
        $session = $this->studySessionService->update($id, $request->user()->id, $request->validated());
        return $this->success(new StudySessionResource($session->load('technology')), 'Sessão atualizada.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->studySessionService->delete($id, request()->user()->id);
        return $this->success(null, 'Sessão excluída.');
    }

    public function active(Request $request): JsonResponse
    {
        $session = $this->studySessionService->getActiveForUser($request->user()->id);
        if (! $session) {
            return $this->success(null);
        }
        $elapsedSeconds = (int) $session->started_at->diffInSeconds(now());
        $resource = new StudySessionResource($session);
        return $this->success(array_merge($resource->resolve($request), ['elapsed_seconds' => $elapsedSeconds]));
    }

    public function start(StartStudySessionRequest $request): JsonResponse
    {
        $session = $this->studySessionService->start($request->user(), $request->validated('technology_id'));
        return $this->success(new StudySessionResource($session->load('technology')), 'Sessão iniciada.', 201);
    }

    public function end(string $id): JsonResponse
    {
        try {
            $session = $this->studySessionService->end($id, request()->user()->id);
            return $this->success(new StudySessionResource($session->load('technology')), 'Sessão finalizada.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'VALIDATION_ERROR', null, 422);
        }
    }
}
