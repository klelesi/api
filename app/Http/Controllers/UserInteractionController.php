<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetInteractionsForPostsRequest;
use App\Http\Requests\StoreUserInteractionRequest;
use App\Http\Resources\UserInteractionResource;
use App\Models\Post;
use App\Models\UserInteraction;

class UserInteractionController extends Controller
{
    public function store(StoreUserInteractionRequest $request)
    {
        $userInteraction = UserInteraction::updateOrCreate([
            'user_id' => $request->user()->id,
            'type' => $request->validated('type'),
            'interactable_type' => Post::class,
            'interactable_id' => $request->validated('postId')
        ], []);

        return response()->json(['data' => null], 200);
    }

    public function posts(GetInteractionsForPostsRequest $request)
    {
        $userInteractions = UserInteraction::where('user_id', $request->user()->id)
            ->whereIn('interactable_id', $request->validated('postIds'))
            ->where('interactable_type', Post::class)
            ->get();

        return UserInteractionResource::collection($userInteractions);
    }
}
