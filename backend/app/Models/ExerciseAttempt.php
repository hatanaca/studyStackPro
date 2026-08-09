<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseAttempt extends BaseModel
{
    protected $fillable = [
        'variant_id',
        'answer',
        'is_correct',
        'graded_by',
        'feedback_latex',
        'expected_latex',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExerciseVariant::class);
    }
}
