<?php

namespace App\Http\Controllers\V1;

use App\Exceptions\Domain\ConcurrentSessionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudySessions\StartStudySessionRequest;
use App\Http\Requests\StudySessions\StoreStudySessionRequest;
use App\Http\Requests\StudySessions\UpdateStudySessionRequest;
use App\Http\Resources\StudySessionResource;
use App\Modules\StudySessions\DTOs\StudySessionDTO;
use App\Modules\StudySessions\DTOs\StudySessionFilterDTO;
use App\Modules\StudySessions\Services\StudySessionService;
use App\Traits\HasApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de sessões de estudo.
 *
 * Gerencia CRUD de sessões, sessão ativa, início e encerramento. Suporta log manual
 * (store com started_at/ended_at) e modo foco (start/end). Impede sessões concorrentes.
 */
class StudySessionController extends Controller
{
    use HasApiResponse;

    /**
     * Injeta o StudySessionService para regras de negócio de sessões.
     */
    public function __construct(
        private StudySessionService $studySessionService
    ) {}

    /**
     * Lista sessões do usuário com filtros e paginação.
     * Filtros: tecnologia, período, página, por página.
     */
    public function index(Request $request): JsonResponse
    {
        $filterDto = StudySessionFilterDTO::fromArray($request->query());
        $paginator = $this->studySessionService->listForUser($request->user()->id, $filterDto);

        return $this->success(
            StudySessionResource::collection($paginator->items()),
            '',
            200,
            [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        );
    }

    /**
     * Cria uma sessão via log manual (posterior ou retroativo).
     * Aceita started_at, ended_at, notes, mood, focus_score.
     */
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

    /**
     * Retorna detalhes de uma sessão específica. Garante que pertence ao usuário.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $session = $this->studySessionService->findForUser($id, $request->user()->id);

        return $this->success(new StudySessionResource($session));
    }

    /**
     * Atualiza uma sessão existente. Validação via UpdateStudySessionRequest.
     */
    public function update(UpdateStudySessionRequest $request, string $id): JsonResponse
    {
        $session = $this->studySessionService->update($id, $request->user()->id, $request->validated());

        return $this->success(new StudySessionResource($session), 'Sessão atualizada.');
    }

    /**
     * Remove uma sessão. Soft delete ou hard delete conforme regra do service.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->studySessionService->delete($id, $request->user()->id);

        return $this->success(null, 'Sessão deletada.');
    }

    /**
     * Retorna a sessão ativa do usuário (se houver) com elapsed_seconds.
     * Usado pelo timer em tempo real no frontend.
     */
    public function active(Request $request): JsonResponse
    {
        $session = $this->studySessionService->getActiveForUser($request->user()->id);

        if (! $session) {
            return $this->success(null);
        }

        $elapsedSeconds = (int) $session->started_at->diffInSeconds(now());

        return $this->success([
            ...(new StudySessionResource($session))->toArray(request()),
            'elapsed_seconds' => $elapsedSeconds,
        ]);
    }

    /**
     * Inicia uma nova sessão de estudo (modo foco).
     * Lança ConcurrentSessionException se já existir sessão ativa.
     * technology_id opcional: usa a primeira tecnologia do usuário se omitido.
     */
    public function start(StartStudySessionRequest $request): JsonResponse
    {
        $user = $request->user();
        // #region agent log
        @file_put_contents(
            base_path('../debug-ba100a.log'),
            json_encode([
                'sessionId' => 'ba100a',
                'runId' => 'project-smoke',
                'hypothesisId' => 'H5',
                'location' => 'backend/app/Http/Controllers/V1/StudySessionController.php',
                'message' => 'session_start_request',
                'data' => [
                    'hasTechnologyId' => (bool) $request->validated('technology_id'),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND
        );
        // #endregion
        if ($this->studySessionService->getActiveForUser($user->id)) {
            throw new ConcurrentSessionException('O usuário já possui uma sessão ativa.');
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

        try {
            $session = $this->studySessionService->create($user->id, $dto);
        } catch (\Illuminate\Database\QueryException $e) {
            // #region agent log
            @file_put_contents(
                base_path('../debug-ba100a.log'),
                json_encode([
                    'sessionId' => 'ba100a',
                    'runId' => 'project-smoke',
                    'hypothesisId' => 'H5',
                    'location' => 'backend/app/Http/Controllers/V1/StudySessionController.php',
                    'message' => 'session_start_query_exception',
                    'data' => [
                        'code' => $e->getCode(),
                        'error' => $e->getMessage(),
                    ],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_SLASHES).PHP_EOL,
                FILE_APPEND
            );
            // #endregion
            if (str_contains($e->getMessage(), 'sessão ativa') || $e->getCode() === 'P0001') {
                throw new ConcurrentSessionException('O usuário já possui uma sessão ativa.');
            }
            throw $e;
        }

        // #region agent log
        @file_put_contents(
            base_path('../debug-ba100a.log'),
            json_encode([
                'sessionId' => 'ba100a',
                'runId' => 'project-smoke',
                'hypothesisId' => 'H5',
                'location' => 'backend/app/Http/Controllers/V1/StudySessionController.php',
                'message' => 'session_start_success',
                'data' => [
                    'hasEndedAt' => $session->ended_at !== null,
                    'hasTechnology' => $session->technology_id !== null,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND
        );
        // #endregion

        return $this->success(new StudySessionResource($session->load('technology')), 'Sessão iniciada.', 201);
    }

    /**
     * Encerra uma sessão em andamento. Define ended_at = now().
     * Retorna 422 se a sessão já estiver finalizada.
     */
    public function end(Request $request, string $id): JsonResponse
    {
        $session = $this->studySessionService->findForUser($id, $request->user()->id);
        // #region agent log
        @file_put_contents(
            base_path('../debug-ba100a.log'),
            json_encode([
                'sessionId' => 'ba100a',
                'runId' => 'project-smoke',
                'hypothesisId' => 'H5',
                'location' => 'backend/app/Http/Controllers/V1/StudySessionController.php',
                'message' => 'session_end_request',
                'data' => [
                    'alreadyEnded' => $session->ended_at !== null,
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND
        );
        // #endregion
        if ($session->ended_at) {
            return $this->error('Sessão já finalizada.', 'VALIDATION_ERROR', null, 422);
        }

        $session = $this->studySessionService->update($id, $request->user()->id, [
            'ended_at' => now()->toIso8601String(),
        ]);

        return $this->success(new StudySessionResource($session), 'Sessão finalizada.');
    }
}
