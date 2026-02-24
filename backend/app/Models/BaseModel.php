<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasUuid;

    protected $keyType = 'string';

    public $incrementing = false;

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->toIso8601String();
    }
}
