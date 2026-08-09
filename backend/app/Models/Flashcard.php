<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flashcard extends BaseModel
{
    protected $fillable = [
        'deck_id',
        'front_latex',
        'back_latex',
        'scheduling_state',
        'fsrs_version',
        'due_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduling_state' => 'array',
            'due_at' => 'datetime',
        ];
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(FlashcardDeck::class, 'deck_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FlashcardReview::class);
    }
}
