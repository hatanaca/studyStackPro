<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Canais de broadcast (WebSocket). dashboard.{userId} é canal privado.
 * Autorização: usuário autenticado deve ter id === userId para subscrever.
 */
Broadcast::channel('dashboard.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
