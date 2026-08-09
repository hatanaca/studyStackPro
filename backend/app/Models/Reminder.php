<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends BaseModel
{
    protected $fillable = [
        'technology_id',
        'text',
        'completed',
    ];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
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
}
