<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model de tecnologia (linguagem, ferramenta, framework).
 *
 * Pertence a um usuário. is_active permite soft delete.
 * Relacionamentos: user, studySessions.
 *
 * user_id não é fillable: só repositório confiável / forceCreate / factory (unguarded) definem o dono.
 */
class Technology extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'icon',
        'description',
        'is_active',
    ];

    /** Casts de atributos */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** Usuário proprietário da tecnologia */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Sessões de estudo desta tecnologia */
    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }
}
