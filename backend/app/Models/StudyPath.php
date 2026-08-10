<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPath extends BaseModel
{
    protected $fillable = [
        'title',
        'nodes',
        'edges',
    ];

    protected function casts(): array
    {
        return [
            'nodes' => 'array',
            'edges' => 'array',
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
