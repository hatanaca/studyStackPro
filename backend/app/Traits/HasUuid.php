<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Alias para HasUuids do Laravel.
 * Garante UUID v4 como chave primária.
 */
trait HasUuid
{
    use HasUuids;
}
