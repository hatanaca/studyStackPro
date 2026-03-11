<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Technologies\SearchTechnologyRequest;
use App\Http\Requests\Technologies\StoreTechnologyRequest;
use App\Http\Requests\Technologies\UpdateTechnologyRequest;
use App\Http\Resources\TechnologyResource;
use App\Modules\Technologies\Services\TechnologyService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de tecnologias.
 *
 * CRUD de tecnologias (linguagens, ferramentas etc.) vinculadas ao usuário.
 * Suporta busca por nome para autocomplete e soft delete (deactivate).
 */
class TechnologyController extends Controller
{
    use HasApiResponse;

    /**
     * Injeta o TechnologyService para regras de negócio.
     */
    public function __construct(
        private TechnologyService $technologyService
    ) {}

    /**
     * Lista todas as tecnologias ativas do usuário.
     */
    public function index(Request $request): JsonResponse
    {
        $technologies = $this->technologyService->listForUser($request->user()->id);

        return $this->success(TechnologyResource::collection($technologies));
    }

    /**
     * Busca tecnologias por nome (autocomplete, ex: TechnologyPicker).
     * Rate limited para evitar abuso.
     */
    public function search(SearchTechnologyRequest $request): JsonResponse
    {
        $technologies = $this->technologyService->search(
            $request->user()->id,
            $request->getQuery(),
            $request->getLimit()
        );

        return $this->success(TechnologyResource::collection($technologies));
    }

    /**
     * Cria uma nova tecnologia para o usuário.
     */
    public function store(StoreTechnologyRequest $request): JsonResponse
    {
        $tech = $this->technologyService->create(
            $request->user()->id,
            $request->validated()
        );

        return $this->success(new TechnologyResource($tech), 'Tecnologia criada.', 201);
    }

    /**
     * Retorna detalhes de uma tecnologia. ID ou UUID aceitos.
     */
    public function show(Request $request, string $technology): JsonResponse
    {
        $tech = $this->technologyService->findForUser($technology, $request->user()->id);

        return $this->success(new TechnologyResource($tech));
    }

    /**
     * Atualiza uma tecnologia existente.
     */
    public function update(UpdateTechnologyRequest $request, string $technology): JsonResponse
    {
        $tech = $this->technologyService->update($technology, $request->user()->id, $request->validated());

        return $this->success(new TechnologyResource($tech), 'Tecnologia atualizada.');
    }

    /**
     * Desativa uma tecnologia (soft delete). Mantém histórico de sessões.
     */
    public function destroy(Request $request, string $technology): JsonResponse
    {
        $this->technologyService->deactivate($technology, $request->user()->id);

        return $this->success(null, 'Tecnologia desativada.');
    }
}
