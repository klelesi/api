<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\SlackAlerts\Facades\SlackAlert;

class CommentController extends Controller
{
    public function store(CreateCommentRequest $request)
    {
        $postId = $request->validated('postId');

        if ($parentId = $request->validated('parentId') ?? null) {
            $postId = Comment::where('id', $parentId)->first()->commentable_id ?? $postId;
        }

        $post = Post::with('comments')->where('id', $postId)->firstOrFail();

        if ($post->isLocked()) {
            abort(400);
        }

        if ($parentId) {
            $ancestors = $this->getAncestors($parentId, $post->comments);

            foreach ($ancestors as $ancestor) {
                if ($ancestor->isLocked()) {
                    abort(400);
                }
            }
        }

        $comment = Comment::create([
            'author_id' => $request->user('sanctum')->id,
            'commentable_id' => $postId,
            'commentable_type' => Post::class,
            'parent_id' => $parentId,
        ]);

        $comment->markdown()->create([
            'markdown' => $request->validated('markdown'),
            'html' => $this->parseMarkdown($request->validated('markdown')),
        ]);

        $post->increment('number_of_comments');

        SlackAlert::message(":tada: Nov komentar! :tada: Prispevek: {$post->slug}");

        return new CommentResource($comment);
    }

    public function update(UpdateCommentRequest $request, string $id)
    {
        $comment = Comment::findOrFail($id);

        if ($request->user()->cannot('update', $comment)) {
            abort(403);
        }

        $post = $comment->commentable;

        if ($post->isLocked() || $comment->isLocked()) {
            abort(400);
        }

        if ($comment->parent_id) {
            $ancestors = $this->getAncestors($comment->parent_id, $post->comments);

            foreach ($ancestors as $ancestor) {
                if ($ancestor->isLocked()) {
                    abort(400);
                }
            }
        }

        $comment->markdown()->update([
            'markdown' => $request->validated('markdown'),
            'html' => $this->parseMarkdown($request->validated('markdown')),
        ]);

        return new CommentResource($comment);
    }

    public function delete(string $id, Request $request)
    {
        $comment = Comment::findOrFail($id);

        if ($request->user()->cannot('delete', $comment)) {
            abort(403);
        }

        $comment->delete();

        return new CommentResource($comment->refresh());
    }


    public function restore(string $id, Request $request)
    {
        $comment = Comment::withTrashed()->findOrFail($id);

        if ($request->user()->cannot('restore', $comment)) {
            abort(403);
        }

        $comment->restore();

        return new CommentResource($comment->refresh());
    }

    public function lock(string $id, Request $request)
    {
        $comment = Comment::findOrFail($id);

        if ($request->user()->cannot('lock content', $comment)) {
            abort(403);
        }

        $comment->locked_at = Carbon::now();
        $comment->save();

        return new CommentResource($comment->refresh());
    }

    public function unlock(string $id, Request $request)
    {
        $comment = Comment::findOrFail($id);

        if ($request->user()->cannot('lock content', $comment)) {
            abort(403);
        }

        $comment->locked_at = null;
        $comment->save();

        return new CommentResource($comment->refresh());
    }

    private function getAncestors(string $currentId, Collection $comments): array
    {
        $map = [];

        foreach ($comments as $comment) {
            $map[$comment->id] = $comment;
        }

        $ancestors = [];

        if (isset($map[$currentId])) {
            $comment = $map[$currentId];
            $ancestors[] = $comment;

            while ($comment->parent_id && isset($map[$comment->parent_id])) {
                $comment = $map[$comment->parent_id];
                $ancestors[] = $comment;
            }
        }

        return $ancestors;
    }
}
