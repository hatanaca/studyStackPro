<?php

namespace App\Policies;

use App\Models\CanvasArtwork;
use App\Models\User;

class CanvasArtworkPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CanvasArtwork $canvasArtwork): bool
    {
        return $user->id === $canvasArtwork->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CanvasArtwork $canvasArtwork): bool
    {
        return $user->id === $canvasArtwork->user_id;
    }

    public function delete(User $user, CanvasArtwork $canvasArtwork): bool
    {
        return $user->id === $canvasArtwork->user_id;
    }
}
