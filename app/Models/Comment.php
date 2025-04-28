<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use mysql_xdevapi\Collection;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public array $comments = [];

    protected static function booted(): void
    {
        static::created(function (Comment $comment) {
            Score::create([
                'scorable_id' => $comment->id,
                'scorable_type' => Comment::class,
                'score' => 0,
            ]);
        });
    }

    protected $guarded = [];

    public function markdown(): MorphOne
    {
        return $this->morphOne(Markdown::class, 'markdownable');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function score(): MorphOne
    {
        return $this->morphOne(Score::class, 'scorable');
    }

    public function isLocked()
    {
        return $this->locked_at !== null;
    }
}
