<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Flashcards\ReviewFlashcardRequest;
use App\Http\Requests\Flashcards\StoreFlashcardDeckRequest;
use App\Http\Requests\Flashcards\StoreFlashcardRequest;
use App\Http\Requests\Flashcards\UpdateFlashcardDeckRequest;
use App\Http\Resources\FlashcardDeckResource;
use App\Http\Resources\FlashcardResource;
use App\Http\Resources\FlashcardReviewResource;
use App\Modules\Flashcards\Services\FlashcardService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlashcardController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly FlashcardService $flashcards,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->success(FlashcardDeckResource::collection(
            $this->flashcards->listDecks($request->user()->id)
        ));
    }

    public function show(Request $request, string $deck): JsonResponse
    {
        return $this->success(new FlashcardDeckResource(
            $this->flashcards->showDeck($deck, $request->user()->id)
        ));
    }

    public function store(StoreFlashcardDeckRequest $request): JsonResponse
    {
        $deck = $this->flashcards->createDeck($request->user()->id, $request->validated('name'));

        return $this->success(new FlashcardDeckResource($deck), 'Baralho criado.', 201);
    }

    public function update(UpdateFlashcardDeckRequest $request, string $deck): JsonResponse
    {
        $model = $this->flashcards->updateDeck($request->user()->id, $deck, $request->validated('name'));

        return $this->success(new FlashcardDeckResource($model), 'Baralho atualizado.');
    }

    public function destroy(Request $request, string $deck): JsonResponse
    {
        $this->flashcards->deleteDeck($request->user()->id, $deck);

        return $this->success(null, 'Baralho excluído.');
    }

    public function cards(Request $request, string $deck): JsonResponse
    {
        return $this->success(FlashcardResource::collection(
            $this->flashcards->listCards($deck, $request->user()->id)
        ));
    }

    public function storeCard(StoreFlashcardRequest $request, string $deck): JsonResponse
    {
        $card = $this->flashcards->storeCard(
            $request->user()->id,
            $deck,
            $request->validated('front_latex'),
            $request->validated('back_latex'),
        );

        return $this->success(new FlashcardResource($card), 'Cartão criado.', 201);
    }

    public function destroyCard(Request $request, string $flashcard): JsonResponse
    {
        $this->flashcards->destroyCard($request->user()->id, $flashcard);

        return $this->success(null, 'Cartão excluído.');
    }

    public function due(Request $request): JsonResponse
    {
        $deckId = $request->query('deck_id');

        return $this->success(FlashcardResource::collection(
            $this->flashcards->dueCards($request->user()->id, is_string($deckId) ? $deckId : null)
        ));
    }

    public function review(ReviewFlashcardRequest $request, string $flashcard): JsonResponse
    {
        $review = $this->flashcards->review(
            $request->user()->id,
            $flashcard,
            (int) $request->validated('rating'),
            $request->validated('state_after'),
            $request->validated('due_at'),
        );

        return $this->success(new FlashcardReviewResource($review), 'Revisão registrada.');
    }
}
