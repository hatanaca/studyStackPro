<?php

namespace App\Modules\Flashcards\Services;

use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\FlashcardReview;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Baralhos e cartões de repetição espaçada (FSRS).
 * O servidor é autoritativo para o estado; o cálculo do próximo agendamento
 * é feito no cliente com ts-fsrs e persistido via review().
 */
class FlashcardService
{
    public function listDecks(string $userId): Collection
    {
        return FlashcardDeck::query()
            ->withCount([
                'flashcards',
                'flashcards as due_flashcards_count' => fn ($q) => $q->where('due_at', '<=', now()),
            ])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function createDeck(string $userId, string $name): FlashcardDeck
    {
        $deck = new FlashcardDeck(['name' => $name]);
        $deck->user_id = $userId;
        $deck->save();

        return $deck;
    }

    public function findOwnDeck(string $deckId, string $userId): FlashcardDeck
    {
        $deck = FlashcardDeck::query()->where('id', $deckId)->firstOrFail();

        if ($deck->user_id !== $userId) {
            throw new AuthorizationException;
        }

        return $deck;
    }

    public function showDeck(string $deckId, string $userId): FlashcardDeck
    {
        $deck = FlashcardDeck::query()
            ->withCount([
                'flashcards',
                'flashcards as due_flashcards_count' => fn ($q) => $q->where('due_at', '<=', now()),
            ])
            ->where('id', $deckId)
            ->firstOrFail();

        if ($deck->user_id !== $userId) {
            throw new AuthorizationException;
        }

        return $deck;
    }

    public function updateDeck(string $userId, string $deckId, string $name): FlashcardDeck
    {
        $deck = $this->findOwnDeck($deckId, $userId);
        $deck->update(['name' => $name]);

        return $deck;
    }

    public function deleteDeck(string $userId, string $deckId): void
    {
        $this->findOwnDeck($deckId, $userId)->delete();
    }

    public function listCards(string $deckId, string $userId): Collection
    {
        $this->findOwnDeck($deckId, $userId);

        return Flashcard::query()
            ->where('deck_id', $deckId)
            ->orderBy('due_at')
            ->get();
    }

    /** Cartão novo: agendado para agora (due imediato, vira revisão com o FSRS). */
    public function storeCard(string $userId, string $deckId, string $frontLatex, string $backLatex): Flashcard
    {
        $this->findOwnDeck($deckId, $userId);

        $card = new Flashcard([
            'deck_id' => $deckId,
            'front_latex' => $frontLatex,
            'back_latex' => $backLatex,
            'scheduling_state' => null,
            'fsrs_version' => '3',
            'due_at' => now(),
        ]);
        $card->user_id = $userId;
        $card->save();

        return $card;
    }

    public function destroyCard(string $userId, string $flashcardId): void
    {
        $card = Flashcard::query()->where('id', $flashcardId)->firstOrFail();

        if ($card->user_id !== $userId) {
            throw new AuthorizationException;
        }

        $card->delete();
    }

    /** Cartões com revisão pendente (due_at <= agora). */
    public function dueCards(string $userId, ?string $deckId = null, int $limit = 50): Collection
    {
        return Flashcard::query()
            ->where('user_id', $userId)
            ->where('due_at', '<=', now())
            ->when($deckId, fn ($q, $id) => $q->where('deck_id', $id))
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    /** Persiste a revisão: log + novo estado do cartão (calculado pelo ts-fsrs no cliente). */
    public function review(
        string $userId,
        string $flashcardId,
        int $rating,
        array $stateAfter,
        string $dueAt,
    ): FlashcardReview {
        $card = Flashcard::query()->where('id', $flashcardId)->firstOrFail();

        if ($card->user_id !== $userId) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($card, $userId, $rating, $stateAfter, $dueAt) {
            $review = FlashcardReview::create([
                'flashcard_id' => $card->id,
                'user_id' => $userId,
                'rating' => $rating,
                'state_before' => $card->scheduling_state,
                'state_after' => $stateAfter,
                'reviewed_at' => now(),
            ]);

            $card->update([
                'scheduling_state' => $stateAfter,
                'due_at' => $dueAt,
            ]);

            return $review;
        });
    }
}
