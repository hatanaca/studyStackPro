<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends BaseModel
{
    protected $fillable = [
        'type',
        'target_value',
        'current_value',
        'status',
        'start_date',
        'end_date',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'integer',
            'current_value' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
