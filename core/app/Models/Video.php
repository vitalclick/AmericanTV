<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Video extends Model {
    use GlobalStatus;

    protected $hidden = [
        'video',
    ];

    public function storage() {
        return $this->belongsTo(Storage::class, 'storage_id');
    }
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function playlist() {
        return $this->belongsTo(Playlist::class);
    }


    
    public function playlists() {
        return $this->belongsToMany(Playlist::class, 'playlist_videos');
    }

    public function plans() {
        return $this->belongsToMany(Plan::class, 'plan_videos');
    }

    public function videoFiles() {
        return $this->hasMany(VideoFile::class);
    }

    public function subtitles() {
        return $this->hasMany(Subtitle::class);
    }

    public function userReactions() {
        return $this->hasMany(UserReaction::class);
    }

    public function comments() {
        return $this->hasMany(Comment::class)->where('parent_id', 0);
    }

    public function allComments() {
        return $this->hasMany(Comment::class);
    }

    public function adPlayDurations() {
        return $this->hasMany(AdPlayDuration::class);
    }

    public function tags() {
        return $this->hasMany(VideoTag::class, 'video_id');
    }

    public function scopeAuthUser($query) {
        return $query->where('user_id', auth()->id());
    }

    public function scopePublished($query) {
        return $query->where('status', Status::PUBLISHED);
    }

    public function scopeOnlyPlaylist($query) {
        return $query->where('is_only_playlist', Status::YES);
    }
    public function scopeWithoutOnlyPlaylist($query) {
        return $query->where('is_only_playlist', Status::NO);
    }

    public function scopePublic($query) {
        return $query->where('visibility', Status::PUBLIC);
    }

    public function scopePrivate($query) {
        return $query->where('visibility', Status::PRIVATE);
    }

    public function scopeStock($query) {
        return $query->where('stock_video', Status::YES);
    }


    public function scopeFree($query) {
        return $query->where('stock_video', Status::NO);
    }

    public function scopeRegular($query) {
        return $query->where('is_shorts_video', Status::NO);
    }

    public function scopeShorts($query) {
        return $query->where('is_shorts_video', Status::YES);
    }

    public function scopeDraft($query) {
        return $query->where('status', Status::DRAFT);
    }

    public function scopeRejected($query) {
        return $query->where('status', Status::REJECTED);
    }

    public function statusBadge(): Attribute {
        return new Attribute(function () {
            $html = '';
            if (!$this->status) {
                $html = '<span class="badge badge--warning">' . trans(' Draft') . '</span>';
            } else if ($this->status == Status::REJECTED) {
                $html = '<span class="badge badge--danger">' . trans('Rejected') . '</span>';
            } else {
                $html = '<span class="badge badge--success">' . trans(' Published') . '</span>';
            }
            return $html;
        });
    }

    public function visibilityStatus(): Attribute {
        return new Attribute(function () {
            $html = '';

            if (request()->routeIs('admin.*')) {
                if (!$this->visibility) {
                    $html = '<span class="badge badge--primary">' . trans(' Public') . '</span>';
                } else {
                    $html = '<span class="badge badge--warning">' . trans(' Private') . '</span>';
                }
            } else {
                if (!$this->visibility) {
                    $html = '<span class="text--success">' . trans(' Public') . '</span>';
                } else {
                    $html = '<span class="text--danger">' . trans(' Private') . '</span>';
                }
            }
            return $html;
        });
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

    public function adsDurations() {
        $adPlayDurations = [];
        if ($this->user->monetization_status && $this->adPlayDurations) {
            $triggerTime = [];
            $this->adPlayDurations->map(function ($adPlayDuration) use (&$triggerTime) {
                $playDuration                 = $adPlayDuration->play_duration;
                $minutes                      = floor($playDuration);
                $inSecond                     = $minutes * 60;
                $fractionalMinutes            = $playDuration - $minutes;
                $secondsFromFractionalMinutes = $fractionalMinutes * 100;
                $totalSeconds                 = $inSecond + round($secondsFromFractionalMinutes);
                $triggerTime[]                = (int) $totalSeconds;
            });
            $adPlayDurations = $triggerTime;
        }
        return $adPlayDurations;
    }

    public function showEligible() {
        $isEligible = true;
        if ($this->stock_video) {
            $isEligible = false;
        }

        if (auth()->check()) {
            $user = auth()->user();

            $purchasedPlans = $user->purchasedPlans()->with('plan.videos', 'plan.playlists.videos')->get();

            $purchasePlanVideoIds = $purchasedPlans->flatMap(function ($item) {
                return $item->plan->videos->pluck('id')->toArray();
            })->unique()->toArray();

            $purchasePlanPlaylistVideoIds = $purchasedPlans->flatMap(function ($item) {
                return $item->plan->playlists->flatMap(function ($playlist) {
                    return $playlist->videos->pluck('id')->toArray();
                })->toArray();
            })->unique()->toArray();

            $purchasedVideoIds = $user->purchasedVideoId;

            $purchasedPlaylist = $user->purchasedPlaylists()->with('playlist.videos')->get();

            $playlistVideoIds = $purchasedPlaylist->flatMap(function ($item) {
                return $item->playlist->videos->pluck('id')->toArray();
            })->unique()->toArray();

            if ($this->stock_video) {
                $isEligible = $this->user_id == $user->id ? true : in_array($this->id, $user->purchasedVideoId) || in_array($this->id, $purchasePlanVideoIds) || in_array($this->id, $purchasedVideoIds) || in_array($this->id, $playlistVideoIds) || in_array($this->id, $purchasePlanPlaylistVideoIds);
            }

        }
        return $isEligible;
    }
}
