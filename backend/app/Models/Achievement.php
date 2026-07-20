<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends BaseModel
{
    protected $fillable = [
        'badge_key',
        'title',
        'description',
        'icon',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
