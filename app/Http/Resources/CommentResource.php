<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'parentId' => $this->parent_id,
            'author' => new AuthorResource($this->author),
            'html' => $this->when($this->deleted_at === null, $this->markdown->html, __('content.deleted_comment')),
            'markdown' => $this->when($this->deleted_at === null, $this->markdown->markdown, __('content.deleted_comment')),
            'comments'=> CommentResource::collection(collect($this->comments)),
            'lockedAt' => $this->locked_at,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
            'deletedAt' => $this->deleted_at,
        ];
    }
}
