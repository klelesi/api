<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Http\Request;
use Spatie\SlackAlerts\Facades\SlackAlert;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request)
    {
        $data = [
            'user_id' => $request->user()->id ?? null,
            'comment' => $request->validated('comment'),
        ];

        if ($postId = $request->validated('postId')) {
            $data['reportable_type'] = Post::class;
            $data['reportable_id'] = $postId;
        } else if ($commentId = $request->validated('commentId')) {
            $data['reportable_type'] = Comment::class;
            $data['reportable_id'] = $commentId;
        }

        Report::create($data);
        SlackAlert::message("Nova prijava vsebine!
        Razlog: {$request->validated('comment')}
        Prispevek: {$request->validated('postId')}
        Komentar: {$request->validated('commentId')}");

        return response()->json(['data' => null]);
    }
}
