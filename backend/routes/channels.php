<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dashboard.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
