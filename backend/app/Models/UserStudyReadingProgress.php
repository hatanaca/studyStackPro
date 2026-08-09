<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStudyReadingProgress extends BaseModel
{
    protected $fillable = [
        'user_id',
        'sub_topic_id',
        'progress',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'float',
            'last_read_at' => 'datetime',
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
