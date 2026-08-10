<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyTopic extends BaseModel
{
    protected $fillable = [
        'subject_id',
        'name',
        'slug',
        'difficulty',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(StudySubject::class, 'subject_id');
    }

    public function subTopics(): HasMany
    {
        return $this->hasMany(StudySubTopic::class, 'topic_id')->orderBy('sort_order');
    }
}
