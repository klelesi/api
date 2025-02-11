<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Markdown extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $guarded = [];

    public function markdownable(): MorphTo
    {
        return $this->morphTo();
    }
}
