<?php

namespace App\Models\Analytics;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMetrics extends Model
{
    public $timestamps = false;

    protected $table = 'analytics.user_metrics';

    protected $primaryKey = 'user_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'total_sessions',
        'total_minutes',
        'avg_session_min',
        'longest_session_min',
        'shortest_session_min',
        'current_streak_days',
        'max_streak_days',
        'avg_mood',
        'avg_focus_score',
        'last_session_at',
        'recalculated_at',
    ];

    protected $casts = [
        'total_sessions' => 'integer',
        'total_minutes' => 'integer',
        'total_hours' => 'float',
        'avg_session_min' => 'float',
        'longest_session_min' => 'integer',
        'shortest_session_min' => 'integer',
        'current_streak_days' => 'integer',
        'max_streak_days' => 'integer',
        'avg_mood' => 'float',
        'avg_focus_score' => 'float',
        'last_session_at' => 'datetime',
        'recalculated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
