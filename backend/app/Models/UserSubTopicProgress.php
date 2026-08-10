<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubTopicProgress extends BaseModel
{
    protected $fillable = [
        'user_id',
        'sub_topic_id',
        'attempted',
        'correct',
        'mastered',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'attempted' => 'integer',
            'correct' => 'integer',
            'mastered' => 'boolean',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function subTopic(): BelongsTo
    {
        return $this->belongsTo(StudySubTopic::class, 'sub_topic_id');
    }
}
