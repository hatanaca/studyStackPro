<?php

namespace App\Models\Analytics;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMinutes extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'analytics.daily_minutes';

    protected $fillable = [
        'user_id',
        'study_date',
        'total_minutes',
        'session_count',
        'technologies',
        'avg_mood',
        'recalculated_at',
    ];

    protected $casts = [
        'study_date' => 'date',
        'total_minutes' => 'integer',
        'session_count' => 'integer',
        'avg_mood' => 'float',
        'recalculated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
