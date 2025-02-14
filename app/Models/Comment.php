<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $guarded = [];

    public function markdown(): MorphOne
    {
        return $this->morphOne(Markdown::class, 'markdownable');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
