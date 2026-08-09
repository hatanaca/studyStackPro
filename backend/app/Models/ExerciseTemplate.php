<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExerciseTemplate extends BaseModel
{
    protected $fillable = [
        'title',
        'kind',
        'prompt',
        'parameters_spec',
        'answer_expression',
        'solution_latex',
        'variables',
        'difficulty',
    ];

    protected function casts(): array
    {
        return [
            'parameters_spec' => 'array',
            'variables' => 'array',
            'difficulty' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ExerciseVariant::class);
    }
}
