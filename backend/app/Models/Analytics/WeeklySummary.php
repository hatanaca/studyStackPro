<?php

namespace App\Models\Analytics;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklySummary extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'analytics.weekly_summaries';

    protected $fillable = [
        'user_id',
        'week_start',
        'week_number',
        'year',
        'total_minutes',
        'session_count',
        'active_days',
        'recalculated_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_number' => 'integer',
        'year' => 'integer',
        'total_minutes' => 'integer',
        'session_count' => 'integer',
        'active_days' => 'integer',
        'recalculated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
