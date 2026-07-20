<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanvasArtwork extends BaseModel
{
    protected $fillable = [
        'title',
        'canvas_data',
        'mural_items',
        'width',
        'height',
        'bg_color',
    ];

    protected function casts(): array
    {
        return [
            'canvas_data' => 'array',
            'mural_items' => 'array',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
