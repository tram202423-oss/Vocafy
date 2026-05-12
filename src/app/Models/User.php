<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasUuids;

    /**
     * Không auto increment vì dùng UUID
     */
    public $incrementing = false;

    /**
     * Key type là string (UUID)
     */
    protected $keyType = 'string';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Hidden fields khi serialize JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Pivot: user_vocabularies
     */
    public function vocabularies()
    {
        return $this->belongsToMany(
            Vocabulary::class,
            'user_vocabularies',
            'user_id',
            'vocabulary_id'
        )
        ->withPivot([
            'mastery_level',
            'correct_count',
            'wrong_count',
            'last_reviewed_at',
            'next_review_at',
        ])
        ->withTimestamps();
    }

    /**
     * Direct relation tới bảng pivot (nếu cần quản lý chi tiết)
     */
    public function userVocabularies()
    {
        return $this->hasMany(UserVocabulary::class);
    }
}