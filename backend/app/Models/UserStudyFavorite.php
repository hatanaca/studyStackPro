<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStudyFavorite extends BaseModel
{
    protected $fillable = [
        'user_id',
        'sub_topic_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function subTopic(): BelongsTo
    {
        return $this->belongsTo(StudySubTopic::class, 'sub_topic_id');
    }
}
