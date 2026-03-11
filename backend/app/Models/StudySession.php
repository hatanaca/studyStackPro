<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model de sessão de estudo.
 *
 * Representa um período de estudo em uma tecnologia. started_at/ended_at definem o intervalo.
 * duration_min pode ser calculado via trigger no banco ou no app. mood e focus_score opcionais.
 */
class StudySession extends BaseModel
{
    use HasFactory;

    protected $table = 'study_sessions';

    protected $fillable = [
        'user_id',
        'technology_id',
        'started_at',
        'ended_at',
        'notes',
        'mood',
        'focus_score',
    ];

    /** Casts de atributos */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_min' => 'integer',
            'mood' => 'integer',
            'focus_score' => 'integer',
        ];
    }

    /** Usuário proprietário da sessão */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Tecnologia estudada na sessão */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }

    /** Accessor: duração formatada (ex: "1h 30min") */
    public function getDurationFormattedAttribute(): ?string
    {
        if ($this->duration_min === null) {
            return null;
        }
        $h = intdiv($this->duration_min, 60);
        $m = $this->duration_min % 60;

        return $h > 0 ? "{$h}h {$m}min" : "{$m}min";
    }
}
