<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(CreateCommentRequest $request)
    {
        $postId = $request->validated('postId');

        if($parentId = $request->validated('parentId') ?? null){
           $postId = Comment::where('id', $parentId)->first()->commentable_id ?? $postId;
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

        Post::where('id', $postId)->increment('number_of_comments');

        return new CommentResource($comment);
    }

    public function update(UpdateCommentRequest $request, string $id)
    {
        $comment = Comment::findOrFail($id);

        if ($request->user()->cannot('update', $comment)) {
            abort(403);
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
}
