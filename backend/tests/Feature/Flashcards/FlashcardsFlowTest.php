<?php

namespace Tests\Feature\Flashcards;

use App\Models\Flashcard;
use App\Models\FlashcardDeck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashcardsFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_create_and_list_decks(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/flashcard-decks', ['name' => 'Fórmulas']);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'data' => ['name' => 'Fórmulas']]);

        $list = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/flashcard-decks');

        $list->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_add_card_to_deck(): void
    {
        $deck = FlashcardDeck::forceCreate(['user_id' => $this->user->id, 'name' => 'Fórmulas']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/flashcard-decks/{$deck->id}/cards", [
                'front_latex' => '\\frac{d}{dx}x^n',
                'back_latex' => 'n x^{n-1}',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'deck_id' => $deck->id,
                    'front_latex' => '\\frac{d}{dx}x^n',
                ],
            ]);

        $this->assertDatabaseHas('flashcards', [
            'deck_id' => $deck->id,
            'user_id' => $this->user->id,
            'fsrs_version' => '3',
        ]);
    }

    public function test_due_returns_pending_cards(): void
    {
        $deck = FlashcardDeck::forceCreate(['user_id' => $this->user->id, 'name' => 'Fórmulas']);
        Flashcard::forceCreate([
            'deck_id' => $deck->id,
            'user_id' => $this->user->id,
            'front_latex' => 'A',
            'back_latex' => 'B',
            'due_at' => now()->subMinute(),
        ]);
        Flashcard::forceCreate([
            'deck_id' => $deck->id,
            'user_id' => $this->user->id,
            'front_latex' => 'C',
            'back_latex' => 'D',
            'due_at' => now()->addDay(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/flashcards/due');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['front_latex' => 'A']]]);
    }

    public function test_review_persists_state_and_updates_card(): void
    {
        $deck = FlashcardDeck::forceCreate(['user_id' => $this->user->id, 'name' => 'Fórmulas']);
        $card = Flashcard::forceCreate([
            'deck_id' => $deck->id,
            'user_id' => $this->user->id,
            'front_latex' => 'A',
            'back_latex' => 'B',
            'due_at' => now(),
        ]);

        $dueAt = now()->addDays(3)->toIso8601String();
        $stateAfter = ['due' => $dueAt, 'stability' => 4.5, 'difficulty' => 3.0, 'reps' => 1];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/flashcards/{$card->id}/review", [
                'rating' => 3,
                'state_after' => $stateAfter,
                'due_at' => $dueAt,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['rating' => 3, 'flashcard_id' => $card->id],
            ]);

        $this->assertDatabaseHas('flashcard_reviews', [
            'flashcard_id' => $card->id,
            'user_id' => $this->user->id,
            'rating' => 3,
        ]);

        $this->assertDatabaseHas('flashcards', [
            'id' => $card->id,
            'scheduling_state' => json_encode($stateAfter),
        ]);
    }

    public function test_review_rejects_invalid_rating(): void
    {
        $deck = FlashcardDeck::forceCreate(['user_id' => $this->user->id, 'name' => 'Fórmulas']);
        $card = Flashcard::forceCreate([
            'deck_id' => $deck->id,
            'user_id' => $this->user->id,
            'front_latex' => 'A',
            'back_latex' => 'B',
            'due_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/flashcards/{$card->id}/review", [
                'rating' => 9,
                'state_after' => [],
                'due_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJson(['error' => ['code' => 'VALIDATION_ERROR']]);
    }

    public function test_cross_user_deck_is_forbidden(): void
    {
        $other = User::factory()->create();
        $deck = FlashcardDeck::forceCreate(['user_id' => $other->id, 'name' => 'Alheio']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson("/api/v1/flashcard-decks/{$deck->id}/cards");

        $response->assertStatus(403)
            ->assertJson(['error' => ['code' => 'FORBIDDEN']]);
    }

    public function test_cross_user_review_is_forbidden(): void
    {
        $other = User::factory()->create();
        $deck = FlashcardDeck::forceCreate(['user_id' => $other->id, 'name' => 'Alheio']);
        $card = Flashcard::forceCreate([
            'deck_id' => $deck->id,
            'user_id' => $other->id,
            'front_latex' => 'A',
            'back_latex' => 'B',
            'due_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/v1/flashcards/{$card->id}/review", [
                'rating' => 3,
                'state_after' => ['stability' => 5.0],
                'due_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(403)
            ->assertJson(['error' => ['code' => 'FORBIDDEN']]);
    }
}
