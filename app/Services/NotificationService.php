<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;

class NotificationService
{
    public function createNewCommentNotification(Post|Comment $parent, Comment $target)
    {
        if ($parent->author_id !== $target->author_id) {
            Notification::create([
                'user_id' => $parent->author_id,
                'post_id' => $parent->commentable_id ?? $parent->id,
                'target_id' => $target->id,
                'type' => Notification::TYPE_NEW_COMMENT,
            ]);
        }
    }
}
