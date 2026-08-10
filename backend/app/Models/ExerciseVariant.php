<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExerciseVariant extends BaseModel
{
    protected $fillable = [
        'template_id',
        'seed',
        'parameters',
        'prompt_latex',
        'answer_expr',
        'solution_latex',
    ];

    protected function casts(): array
    {
        return [
            'seed' => 'integer',
            'parameters' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ExerciseTemplate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExerciseAttempt::class);
    }
}
