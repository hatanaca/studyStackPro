<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Modules\ItaStudy\Services\UserStudyService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserStudyController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly UserStudyService $study,
    ) {}

    public function subTopic(string $subTopicId): JsonResponse
    {
        $detail = $this->study->subTopicDetail($subTopicId);
        if (!$detail) {
            return $this->error('Sub-tópico não encontrado.', 'SUBTOPIC_NOT_FOUND', null, 404);
        }

        $detail['is_favorited'] = $this->study->isFavorited(request()->user()->id, $subTopicId);

        return $this->success($detail);
    }

    public function favorites(Request $request): JsonResponse
    {
        return $this->success($this->study->listFavorites($request->user()->id));
    }

    public function addFavorite(Request $request): JsonResponse
    {
        $subTopicId = (string) $request->input('sub_topic_id');
        $favorite = $this->study->addFavorite($request->user()->id, $subTopicId);

        if (!$favorite) {
            return $this->error('Sub-tópico não encontrado.', 'SUBTOPIC_NOT_FOUND', null, 404);
        }

        return $this->success($favorite, 'Favorito adicionado.', 201);
    }

    public function removeFavorite(Request $request, string $subTopicId): JsonResponse
    {
        $this->study->removeFavorite($request->user()->id, $subTopicId);

        return $this->success(null, 'Favorito removido.');
    }

    public function getNote(Request $request, string $subTopicId): JsonResponse
    {
        $note = $this->study->getNote($request->user()->id, $subTopicId);

        return $this->success($note);
    }

    public function saveNote(Request $request, string $subTopicId): JsonResponse
    {
        $content = (string) $request->input('content', '');

        return $this->success(
            $this->study->saveNote($request->user()->id, $subTopicId, $content),
            'Nota salva.',
        );
    }

    public function deleteNote(Request $request, string $subTopicId): JsonResponse
    {
        $this->study->deleteNote($request->user()->id, $subTopicId);

        return $this->success(null, 'Nota excluída.');
    }

    public function getReadingProgress(Request $request, string $subTopicId): JsonResponse
    {
        return $this->success($this->study->getReadingProgress($request->user()->id, $subTopicId));
    }

    public function updateReadingProgress(Request $request, string $subTopicId): JsonResponse
    {
        $progress = (float) $request->input('progress', 0);

        return $this->success(
            $this->study->updateReadingProgress($request->user()->id, $subTopicId, $progress),
            'Progresso atualizado.',
        );
    }
}
