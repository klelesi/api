<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetInteractionsForPostsRequest;
use App\Http\Requests\StoreUserInteractionRequest;
use App\Http\Resources\UserInteractionResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\UserInteraction;
use App\Services\UserInteractionService;

class UserInteractionController extends Controller
{
    public function store(StoreUserInteractionRequest $request, UserInteractionService $userInteractionService)
    {
        if ($postId = $request->validated('postId')) {
            $item = Post::where('id', $postId)->firstOrFail();
        } else if ($commentId = $request->validated('commentId')) {
            $item = Comment::where('id', $commentId)->firstOrFail();
        }

        switch ($type = $request->validated('type')) {
            case UserInteraction::TYPE_VIEW:
                $userInteractionService->addViewInteraction($request->user(), $request->validated('postId'));
                break;
            case UserInteraction::TYPE_UPVOTE:
                if ($postId) {
                    $userInteractionService->addPostUpvote($request->user(), $item);
                } else if ($commentId) {
                    $userInteractionService->addCommentUpvote($request->user(), $item);
                }
                break;
            case UserInteraction::TYPE_DOWNVOTE:
                if ($postId) {
                    $userInteractionService->addPostDownvote($request->user(), $item);
                } else if ($commentId) {
                    $userInteractionService->addCommentDownvote($request->user(), $item);
                }
                break;
            default:
                throw new \Exception("$type not supported");
        }


        return response()->json(['data' => null], 200);
    }

    public function delete(StoreUserInteractionRequest $request, UserInteractionService $userInteractionService)
    {
        if ($postId = $request->validated('postId')) {
            $item = Post::where('id', $postId)->firstOrFail();
        } else if ($commentId = $request->validated('commentId')) {
            $item = Comment::where('id', $commentId)->firstOrFail();
        }

        switch ($type = $request->validated('type')) {
            case UserInteraction::TYPE_VIEW:
                $userInteractionService->removeViewInteraction($request->user(), $request->validated('postId'));
                break;
            case UserInteraction::TYPE_UPVOTE:
                if ($postId) {
                    $userInteractionService->removePostUpvote($request->user(), $item);
                } else if ($commentId) {
                    $userInteractionService->removeCommentUpvote($request->user(), $item);
                }
                break;
            case UserInteraction::TYPE_DOWNVOTE:
                if ($postId) {
                    $userInteractionService->removePostDownvote($request->user(), $item);
                } else if ($commentId) {
                    $userInteractionService->removeCommentDownvote($request->user(), $item);
                }
                break;
            default:
                throw new \Exception("$type not supported");
        }


        return response()->json(['data' => null], 200);
    }

    public
    function posts(GetInteractionsForPostsRequest $request)
    {
        $userInteractions = UserInteraction::where('user_id', $request->user()->id)
            ->whereIn('interactable_id', $request->validated('postIds'))
            ->where('interactable_type', Post::class)
            ->get();

        return UserInteractionResource::collection($userInteractions);
    }
}
