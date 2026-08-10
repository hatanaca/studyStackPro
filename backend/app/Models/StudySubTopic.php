<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudySubTopic extends BaseModel
{
    protected $fillable = [
        'topic_id',
        'name',
        'slug',
        'sort_order',
        'description',
        'content',
        'faqs',
        'learning_objectives',
        'simulation_config',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'content' => 'array',
            'faqs' => 'array',
            'learning_objectives' => 'array',
            'simulation_config' => 'array',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(StudyTopic::class, 'topic_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(StudyQuestion::class);
    }
}
