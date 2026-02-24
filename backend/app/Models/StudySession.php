<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends BaseModel
{
    protected $table = 'study_sessions';

    protected $fillable = [
        'user_id',
        'technology_id',
        'started_at',
        'ended_at',
        'notes',
        'mood',
        'focus_score',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_min' => 'integer',
            'mood' => 'integer',
            'focus_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }

    public function getDurationFormattedAttribute(): ?string
    {
        if ($this->duration_min === null) {
            return null;
        }
        $h = intdiv($this->duration_min, 60);
        $m = $this->duration_min % 60;
        return $h > 0 ? "{$h}h {$m}min" : "{$m}min";
    }
}
