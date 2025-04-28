<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasUuids, HasFactory;

    protected $guarded = [];

    public function scorable()
    {
        return $this->morphTo();
    }
}
