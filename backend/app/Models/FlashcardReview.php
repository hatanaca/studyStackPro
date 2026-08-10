<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashcardReview extends BaseModel
{
    protected $fillable = [
        'flashcard_id',
        'user_id',
        'rating',
        'state_before',
        'state_after',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'state_before' => 'array',
            'state_after' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function flashcard(): BelongsTo
    {
        return $this->belongsTo(Flashcard::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
