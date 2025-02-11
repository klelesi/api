<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasUuids, HasFactory, SoftDeletes, HasSlug;

    const POST_TYPE_MARKDOWN = 0;
    const POST_TYPE_LINK = 1;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->usingSeparator('-')
            ->usingLanguage('sl')
            ->doNotGenerateSlugsOnUpdate()
            ->preventOverwrite()
            ->slugsShouldBeNoLongerThan(240);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function markdown(): MorphOne
    {
        return $this->morphOne(Markdown::class, 'markdownable');
    }

    public function link(): MorphOne
    {
        return $this->morphOne(Link::class, 'linkable');
    }
}
