<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function feed(Request $request)
    {
        $author = $request->query('author');

        $query = Post::query()
            ->orderBy('created_at', 'DESC');

        $with = ['author', 'markdown', 'link', 'score'];

        if ($request->user()) {
            $with[] = 'interactions';
        }

        if ($author && $user = User::where('username', $author)->orWhere('id', $author)->first()) {
            $query->where('author_id', $user->id);
        }

        $perPage = $request->query('perPage', 50);

        if (!is_numeric($perPage)) {
            $perPage = 50;
        }

        $posts = $query->with($with)->cursorPaginate($perPage);

        return PostResource::collection($posts);
    }
}
