<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Model base abstrato.
 *
 * Usa HasUuid para chaves UUID. Serializa datas em ISO8601.
 * keyType string e incrementing false para compatibilidade com UUID.
 */
abstract class BaseModel extends Model
{
    use HasUuid;

    /** Chave primária é string (UUID) */
    protected $keyType = 'string';

    /** Não usa auto-incremento */
    public $incrementing = false;

    /** Serializa datas em ISO8601 para JSON */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->toIso8601String();
    }
}
