<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function feed(Request $request)
    {
        $query = Post::query()
            ->orderBy('created_at', 'DESC');

        $with = ['author', 'markdown', 'link', 'score'];
        if ($request->user()) {
            $with[] = 'interactions';
        }

        $posts = $query->with($with)->cursorPaginate(50);

        return PostResource::collection($posts);
    }
}
