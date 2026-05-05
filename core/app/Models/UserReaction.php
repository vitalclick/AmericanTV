<?php

namespace App\Models;

use App\Constants\Status;

use Illuminate\Database\Eloquent\Model;

class UserReaction extends Model
{
    

    public function scopeLike($query){
        return $query->where('is_like', Status::YES);
    }

    public function scopeDislike($query){
        return $query->where('is_like', Status::NO);
    }


    
    public function user(){
        return $this->belongsTo(User::class);
    }


}

