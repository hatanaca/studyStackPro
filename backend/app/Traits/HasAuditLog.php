<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Trait para registro de auditoria (created_by, updated_by, etc.).
 * Opcional: pode ser usado em models que precisam rastrear quem alterou.
 */
trait HasAuditLog
{
    protected static function bootHasAuditLog(): void
    {
        static::creating(function ($model) {
            if (Auth::check() && in_array('created_by', $model->getFillable())) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check() && in_array('updated_by', $model->getFillable())) {
                $model->updated_by = Auth::id();
            }
        });
    }
}
