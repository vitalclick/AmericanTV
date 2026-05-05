<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model {

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function video() {
        return $this->belongsTo(Video::class);
    }

    public function replies() {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function replierUser() {
        return $this->belongsTo(User::class, 'replier_user_id');
    }

    public function userReactions() {
        return $this->hasMany(UserReaction::class);
    }

    public function reactionLikeCount(): Attribute {
        return new Attribute(
            get: fn() => $this->userReactions->where('is_like', Status::YES)->count()
        );
    }
    public function isLikedByAuthUser(): Attribute {
        return new Attribute(
            get: fn() => $this->userReactions->where('user_id', auth()->id())->where('is_like', Status::YES)->count()
        );
    }
    public function isUnlikedByAuthUser(): Attribute {
        return new Attribute(
            get: fn() => $this->userReactions->where('user_id', auth()->id())->where('is_like', Status::NO)->count()
        );
    }
}
