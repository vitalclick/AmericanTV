<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    public function followUser(){
        return $this->belongsTo(User::class,'user_id');
    }


    public function followingUser(){
        return $this->belongsTo(User::class,'following_id');
    }

}
