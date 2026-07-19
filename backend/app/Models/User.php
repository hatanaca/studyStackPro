<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'email', 'password', 'timezone', 'locale', 'avatar_url',
        'linkedin_id',
        'linkedin_token', 'linkedin_refresh_token', 'linkedin_token_expires_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
        'discord_token', 'discord_refresh_token',
        'google_token', 'google_refresh_token',
        'linkedin_token', 'linkedin_refresh_token',
    ];

    public array $oauthTokenFields = [
        'discord_token', 'discord_refresh_token', 'discord_token_expires_at',
        'google_token', 'google_refresh_token', 'google_token_expires_at',
        'linkedin_token', 'linkedin_refresh_token', 'linkedin_token_expires_at',
    ];

    // IDs OAuth — não em $fillable para evitar mass assignment
    public array $oauthIdFields = ['google_id', 'discord_id', 'linkedin_id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'discord_token' => 'encrypted', 'discord_refresh_token' => 'encrypted',
            'google_token' => 'encrypted', 'google_refresh_token' => 'encrypted',
            'linkedin_token' => 'encrypted', 'linkedin_refresh_token' => 'encrypted',
            'discord_token_expires_at' => 'datetime',
            'google_token_expires_at' => 'datetime',
            'linkedin_token_expires_at' => 'datetime',
        ];
    }

    public function technologies(): HasMany { return $this->hasMany(Technology::class); }
    public function studySessions(): HasMany { return $this->hasMany(StudySession::class); }
}
