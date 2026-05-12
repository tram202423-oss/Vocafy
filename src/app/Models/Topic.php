<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'thumbnail',
        'is_active',
    ];

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }
}
