<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInteraction extends Model
{
    /** @use HasFactory<\Database\Factories\UserInteractionFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    const TYPE_VIEW = 'view';

    public function interactable()
    {
        return $this->morphTo();
    }
}
