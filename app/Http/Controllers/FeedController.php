<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function feed()
    {
        $posts = Post::orderBy('created_at', 'DESC')->cursorPaginate(50);

        return PostResource::collection($posts);
    }
}
