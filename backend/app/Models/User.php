<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model de usuário.
 *
 * Autenticável via Laravel Auth, tokens via Sanctum. Relacionamentos: technologies, studySessions.
 * Senha e remember_token ocultos em toArray. Senha cast para hashed automaticamente.
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    /** Campos permitidos para mass assignment */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'locale',
        'avatar_url',
    ];

    /** Campos nunca expostos em JSON (segurança) */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Casts de atributos (tipos e mutators) */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Tecnologias vinculadas ao usuário */
    public function technologies(): HasMany
    {
        return $this->hasMany(Technology::class);
    }

    /** Sessões de estudo do usuário */
    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }
}
