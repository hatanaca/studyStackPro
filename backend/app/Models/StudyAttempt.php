<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyAttempt extends BaseModel
{
    public $incrementing = false;

    protected $keyType = 'string';

    /** A tabela só possui created_at (sem updated_at). */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'variant_id',
        'answer',
        'is_correct',
        'time_spent_seconds',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'time_spent_seconds' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(StudyQuestionVariant::class, 'variant_id');
    }
}
