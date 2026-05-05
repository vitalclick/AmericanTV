<?php

namespace App\Models;

use App\Constants\Status;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model {

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function scopeAuthUser($query) {
        return $query->where('user_id', auth()->id());
    }

    public function scopePublic($query) {
        return $query->where('visibility', Status::PUBLIC);
    }

    public function scopePlaylistForSell($query) {
        return $query->where('playlist_subscription', Status::YES)->whereNotNull('price');
    }

    public function videos() {
        return $this->belongsToMany(Video::class, 'playlist_videos');
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_playlists');
    }

    public function statusBadge(): Attribute {
        return new Attribute(function () {
            $html = '';
            if ($this->visibility == Status::PUBLIC) {
                $html = '<span class="badge badge--success">' . '<i class="las la-globe"></i>' . trans(' Public') . '</span>';
            } else {
                $html = '<span class="badge badge--danger">' . '<i class="las la-lock"></i>' . trans(' Private') . '</span>';
            }
            return $html;
        });
    }
}
