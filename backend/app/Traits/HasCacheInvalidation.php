<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait HasCacheInvalidation
{
    /**
     * Invalida cache por tags.
     *
     * @param  array<string>  $tags
     */
    protected function invalidateTags(array $tags): void
    {
        Cache::tags($tags)->flush();
    }
}
