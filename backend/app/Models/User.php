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
        'google_id',
        'discord_id',
        'discord_token',
        'discord_refresh_token',
        'discord_token_expires_at',
        'google_token',
        'google_refresh_token',
        'google_token_expires_at',
    ];

    /** Campos nunca expostos em JSON (segurança) */
    protected $hidden = [
        'password',
        'remember_token',
        'discord_token',
        'discord_refresh_token',
        'google_token',
        'google_refresh_token',
    ];

    /** Casts de atributos (tipos e mutators) */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'discord_token' => 'encrypted',
            'discord_refresh_token' => 'encrypted',
            'google_token' => 'encrypted',
            'google_refresh_token' => 'encrypted',
            'discord_token_expires_at' => 'datetime',
            'google_token_expires_at' => 'datetime',
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
