<?php

namespace App\Models\Analytics;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnologyMetrics extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $table = 'analytics.technology_metrics';

    protected $fillable = [
        'user_id',
        'technology_id',
        'total_minutes',
        'session_count',
        'avg_session_min',
        'percentage_total',
        'first_studied_at',
        'last_studied_at',
        'recalculated_at',
    ];

    protected $casts = [
        'total_minutes' => 'integer',
        'total_hours' => 'float',
        'session_count' => 'integer',
        'avg_session_min' => 'float',
        'percentage_total' => 'float',
        'first_studied_at' => 'datetime',
        'last_studied_at' => 'datetime',
        'recalculated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }
}
