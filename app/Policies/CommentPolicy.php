<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment): bool
    {
        if ($comment->author_id === $user->id) {
            return true;
        }

        return $user->can('moderate content', $comment);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->author_id === $user->id) {
            return true;
        }

        return $user->can('delete content', $comment);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Comment $comment): bool
    {
        if ($comment->author_id === $user->id) {
            return true;
        }

        return $user->can('delete content', $comment);
    }
}
