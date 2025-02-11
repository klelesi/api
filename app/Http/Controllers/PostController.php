<?php

namespace App\Http\Controllers;

use App\CustomParsedown;
use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use App\Services\LinkService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private LinkService $linkService)
    {
    }

    public function store(CreatePostRequest $request)
    {
        switch ($request->validated('postType')) {
            case Post::POST_TYPE_MARKDOWN:
                $post = $this->createMarkdownPost($request->validated(), $request->user('sanctum'));
                break;
            case Post::POST_TYPE_LINK:
                $post = $this->createLinkPost($request->validated(), $request->user('sanctum'));
                break;
        }

        return new PostResource($post->refresh());
    }

    public function update(UpdatePostRequest $request, string $id)
    {
        $post = Post::findOrFail($id);

        if ($request->user()->cannot('update', $post)) {
            abort(403);
        }

        switch ($post->post_type) {
            case Post::POST_TYPE_MARKDOWN:
                $post = $this->updateMarkdownPost($post, $request->validated());
                break;
            case Post::POST_TYPE_LINK:
                $post = $this->updateLinkPost($post, $request->validated());
                break;
        }

        return new PostResource($post->refresh());
    }

    public function delete(string $id, Request $request)
    {
        $post = Post::findOrFail($id);

        if ($request->user()->cannot('delete', $post)) {
            abort(403);
        }

        $post->delete();

        return new PostResource($post->refresh());
    }

    public function show(string $id)
    {
        $post = Post::where('id', $id)->orWhere('slug', $id)->firstOrFail();

        return new PostResource($post);
    }

    public function restore(string $id, Request $request)
    {
        $post = Post::withTrashed()->findOrFail($id);

        if ($request->user()->cannot('restore', $post)) {
            abort(403);
        }

        $post->restore();

        return new PostResource($post->refresh());
    }

    private function createMarkdownPost(array $data, User $author): Post
    {
        $post = Post::create([
            'author_id' => $author->id,
            'title' => $data['title'],
            'post_type' => Post::POST_TYPE_MARKDOWN,
        ]);

        $post->markdown()->create([
            'markdown' => $data['markdown'],
            'html' => $this->parseMarkdown($data['markdown']),
        ]);

        return $post;
    }

    private function updateMarkdownPost(Post $post, array $data)
    {
        $post->update([
            'title' => $data['title'],
        ]);

        $post->markdown()->update([
            'markdown' => $data['markdown'],
            'html' => $this->parseMarkdown($data['markdown']),
        ]);

        return $post;
    }

    private static function parseMarkdown(string $markdown): string
    {
        $html = (new CustomParsedown())->setSafeMode(true)->text($markdown);
        $purifier = new \HTMLPurifier(['HTML.TargetNoreferrer' => true, 'HTML.TargetNoopener' => true, 'Attr.AllowedFrameTargets' => ['_blank']]);

        return $purifier->purify($html);
    }

    private function createLinkPost(array $data, User $author): Post
    {
        $post = Post::create([
            'author_id' => $author->id,
            'title' => $data['title'],
            'post_type' => Post::POST_TYPE_LINK,
        ]);

        $linkData = [
            'url' => $data['url'],
        ];

        try {
            $linkData['meta'] = $this->linkService->parse($data['url']);
        } catch (\Exception $exception) {

        }

        $post->link()->create($linkData);

        return $post;
    }

    private function updateLinkPost(Post $post, array $data)
    {
        $post->update([
            'title' => $data['title'],
        ]);

        return $post;
    }
}
