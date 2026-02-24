<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudySessions\StartStudySessionRequest;
use App\Http\Requests\StudySessions\StoreStudySessionRequest;
use App\Http\Requests\StudySessions\UpdateStudySessionRequest;
use App\Http\Resources\StudySessionResource;
use App\Modules\StudySessions\DTOs\StudySessionDTO;
use App\Modules\StudySessions\Services\StudySessionService;
use App\Traits\HasApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudySessionController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private StudySessionService $studySessionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'technology_id' => $request->query('technology_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'min_duration' => $request->query('min_duration'),
            'mood' => $request->query('mood'),
            'status' => $request->query('status'),
            'per_page' => $request->query('per_page', 15),
        ];

        $paginator = $this->studySessionService->listForUser($request->user()->id, $filters);

        return response()->json([
            'success' => true,
            'data' => StudySessionResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreStudySessionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $dto = new StudySessionDTO(
            userId: $request->user()->id,
            technologyId: $validated['technology_id'],
            startedAt: Carbon::parse($validated['started_at']),
            endedAt: isset($validated['ended_at']) ? Carbon::parse($validated['ended_at']) : null,
            notes: $validated['notes'] ?? null,
            mood: $validated['mood'] ?? null,
            focusScore: $validated['focus_score'] ?? null,
        );
        $session = $this->studySessionService->create($request->user()->id, $dto);

        return $this->success(new StudySessionResource($session->load('technology')), 'Sessão criada.', 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $session = $this->studySessionService->findForUser($id, $request->user()->id);

        return $this->success(new StudySessionResource($session));
    }

    public function update(UpdateStudySessionRequest $request, string $id): JsonResponse
    {
        $session = $this->studySessionService->update($id, $request->user()->id, $request->validated());

        return $this->success(new StudySessionResource($session), 'Sessão atualizada.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->studySessionService->delete($id, $request->user()->id);

        return $this->success(null, 'Sessão deletada.');
    }

    public function active(Request $request): JsonResponse
    {
        $session = $request->user()
            ->studySessions()
            ->whereNull('ended_at')
            ->with('technology')
            ->first();

        if (! $session) {
            return $this->success(null);
        }

        $elapsedSeconds = (int) $session->started_at->diffInSeconds(now());

        return $this->success([
            ...(new StudySessionResource($session))->toArray(request()),
            'elapsed_seconds' => $elapsedSeconds,
        ]);
    }

    public function start(StartStudySessionRequest $request): JsonResponse
    {
        $user = $request->user();
        $existing = $user->studySessions()->whereNull('ended_at')->first();
        if ($existing) {
            return $this->error('Você já possui uma sessão ativa.', 'VALIDATION_ERROR', null, 422);
        }

        $techId = $request->validated('technology_id')
            ?? $user->technologies()->first()?->id;
        $dto = new StudySessionDTO(
            userId: $user->id,
            technologyId: $techId,
            startedAt: now(),
            endedAt: null,
            notes: null,
            mood: null,
        );

        $session = $this->studySessionService->create($user->id, $dto);

        return $this->success(new StudySessionResource($session->load('technology')), 'Sessão iniciada.', 201);
    }

    public function end(Request $request, string $id): JsonResponse
    {
        $session = $this->studySessionService->findForUser($id, $request->user()->id);
        if ($session->ended_at) {
            return $this->error('Sessão já finalizada.', 'VALIDATION_ERROR', null, 422);
        }

        $session = $this->studySessionService->update($id, $request->user()->id, [
            'ended_at' => now()->toIso8601String(),
        ]);

        return $this->success(new StudySessionResource($session), 'Sessão finalizada.');
    }
}
