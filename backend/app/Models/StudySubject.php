<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class StudySubject extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function topics(): HasMany
    {
        return $this->hasMany(StudyTopic::class, 'subject_id')->orderBy('sort_order');
    }
}
