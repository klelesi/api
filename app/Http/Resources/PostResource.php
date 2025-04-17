<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Models\UserInteraction;
use App\Services\LinkData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\AuthorResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'postType' => $this->post_type,
            'title' => $this->title,
            'slug' => "/guna/{$this->slug}",
            'numberOfComments' => $this->number_of_comments,
            'html' => $this->when($this->post_type == Post::POST_TYPE_MARKDOWN, function () {
                return $this->markdown->html;
            }),
            'markdown' => $this->when($this->post_type == Post::POST_TYPE_MARKDOWN, function () {
                return $this->markdown->markdown;
            }),
            'url' => $this->when($this->post_type == Post::POST_TYPE_LINK, function () {
                return $this->link->url;
            }),
            'urlHost' => $this->when($this->post_type == Post::POST_TYPE_LINK, function () {
                return parse_url($this->link->url)['host'];
            }),
            'urlMeta' => $this->when($this->post_type == Post::POST_TYPE_LINK, function () {
                return $this->link->meta ?? new LinkData("");
            }),
            'author' => new AuthorResource($this->author),
            'comments' => $this->whenLoaded('comments',
                function () {
                    return CommentResource::collection($this->nestedComments());
                }
            ),
            'interactions' => $this->whenLoaded('interactions', function () {
                return UserInteractionResource::collection($this->interactions);
            }),
            'lockedAt' => $this->locked_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
