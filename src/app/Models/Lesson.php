<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'topic_id',
        'title',
        'description',
        'order',
        'is_active',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function vocabularies()
    {
        return $this->hasMany(Vocabulary::class);
    }
}
