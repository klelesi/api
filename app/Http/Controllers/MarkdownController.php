<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransformMarkdownRequest;
use Illuminate\Http\Request;

class MarkdownController extends Controller
{
    public function preview(TransformMarkdownRequest $request)
    {
        return response()->json(['data' => [
            'html' => $this->parseMarkdown($request->validated('markdown'))
        ]]);
    }
}
