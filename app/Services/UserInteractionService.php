<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Score;
use App\Models\User;
use App\Models\UserInteraction;

class UserInteractionService
{
    public function addViewInteraction(User $user, string $postId)
    {
        $post = Post::where('id', $postId)->first();

        if ($post) {
            return UserInteraction::updateOrCreate([
                'user_id' => $user->id,
                'type' => UserInteraction::TYPE_VIEW,
                'interactable_type' => Post::class,
                'interactable_id' => $postId
            ], []);
        }

        return null;
    }

    public function addPostUpvote(User $user, Post $post)
    {
        $this->removePostDownvote($user, $post);

        $interaction = UserInteraction::updateOrCreate([
            'user_id' => $user->id,
            'type' => UserInteraction::TYPE_UPVOTE,
            'interactable_type' => Post::class,
            'interactable_id' => $post->id,
        ], []);

        if ($interaction->wasRecentlyCreated) {
            $this->incrementScore($post);
        }
    }

    public function addCommentUpvote(User $user, Comment $comment)
    {
        $this->removeCommentDownvote($user, $comment);

        $interaction = UserInteraction::updateOrCreate([
            'user_id' => $user->id,
            'type' => UserInteraction::TYPE_UPVOTE,
            'interactable_type' => Comment::class,
            'interactable_id' => $comment->id,
        ], []);

        if ($interaction->wasRecentlyCreated) {
            $this->incrementScore($comment);
        }
    }


    public function addPostDownvote(mixed $user, Post $post)
    {
        $this->removePostUpvote($user, $post);

        $interaction = UserInteraction::updateOrCreate([
            'user_id' => $user->id,
            'type' => UserInteraction::TYPE_DOWNVOTE,
            'interactable_type' => Post::class,
            'interactable_id' => $post->id,
        ], []);

        if ($interaction->wasRecentlyCreated) {
            $this->decrementScore($post);
        }
    }

    public function addCommentDownvote(mixed $user, Comment $comment)
    {
        $this->removeCommentUpvote($user, $comment);

        $interaction = UserInteraction::updateOrCreate([
            'user_id' => $user->id,
            'type' => UserInteraction::TYPE_DOWNVOTE,
            'interactable_type' => Comment::class,
            'interactable_id' => $comment->id,
        ], []);

        if ($interaction->wasRecentlyCreated) {
            $this->decrementScore($comment);
        }
    }

    private function incrementScore(Post|Comment $item)
    {
        $item->score()->increment('score', 1);
    }

    private function decrementScore(Post|Comment $item)
    {
        $item->score()->decrement('score', 1);
    }

    public function removeViewInteraction(User $user, Post $post)
    {
        $interaction = UserInteraction::where('user_id', $user->id)
            ->where('type', UserInteraction::TYPE_VIEW)
            ->where('interactable_id', $post->id)->first();

        if ($interaction) {
            $interaction->delete();
        }
    }

    public function removePostUpvote(User $user, Post $post)
    {
        $interaction = UserInteraction::where('user_id', $user->id)
            ->where('type', UserInteraction::TYPE_UPVOTE)
            ->where('interactable_id', $post->id)->first();

        if ($interaction) {
            $interaction->delete();
            $this->decrementScore($post);
        }
    }

    public function removeCommentUpvote(User $user, Comment $comment)
    {
        $interaction = UserInteraction::where('user_id', $user->id)
            ->where('type', UserInteraction::TYPE_UPVOTE)
            ->where('interactable_id', $comment->id)->first();

        if ($interaction) {
            $interaction->delete();
            $this->decrementScore($comment);
        }
    }

    public function removePostDownvote(User $user, Post $post)
    {
        $interaction = UserInteraction::where('user_id', $user->id)
            ->where('type', UserInteraction::TYPE_DOWNVOTE)
            ->where('interactable_id', $post->id)->first();

        if ($interaction) {
            $interaction->delete();
            $this->incrementScore($post);
        }
    }

    public function removeCommentDownvote(User $user, Comment $comment)
    {
        $interaction = UserInteraction::where('user_id', $user->id)
            ->where('type', UserInteraction::TYPE_DOWNVOTE)
            ->where('interactable_id', $comment->id)->first();

        if ($interaction) {
            $interaction->delete();
            $this->incrementScore($comment);
        }
    }
}
