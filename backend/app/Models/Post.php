<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['userId', 'title', 'body'];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'postId');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

}
