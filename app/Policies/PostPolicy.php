<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PostPolicy
{

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        if ($post->author_id === $user->id) {
            return true;
        }

        return $user->can('moderate content', $post);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        if ($post->author_id === $user->id) {
            return true;
        }

        return $user->can('delete content', $post);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post): bool
    {
        if ($post->author_id === $user->id) {
            return true;
        }

        return $user->can('delete content', $post);
    }
}
