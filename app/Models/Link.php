<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Link extends Model
{
    /** @use HasFactory<\Database\Factories\LinkFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'json',
    ];

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
