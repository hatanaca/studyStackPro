<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyQuestionVariant extends BaseModel
{
    public $incrementing = false;

    protected $keyType = 'string';

    /** A tabela só possui created_at (sem updated_at). */
    public const UPDATED_AT = null;

    protected $fillable = [
        'question_id',
        'user_id',
        'seed',
        'parameters',
        'prompt_resolved',
        'answer_value',
        'choices_resolved',
    ];

    protected function casts(): array
    {
        return [
            'seed' => 'integer',
            'parameters' => 'array',
            'choices_resolved' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(StudyQuestion::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(StudyAttempt::class, 'variant_id');
    }
}
