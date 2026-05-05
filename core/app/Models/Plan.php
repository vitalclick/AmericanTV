<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use GlobalStatus;

    public function videos()
    {
        return $this->belongsToMany(Video::class, 'plan_videos');
    }

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class, 'plan_playlists');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchasedPlans()
    {
        return $this->hasMany(PurchasedPlan::class, 'plan_id');
    }

    public function totalEarnings()
    {
        return $this->purchasedPlans()->sum('amount');
    }

    public function scopeWithVideosOrPlaylists($query)
    {
        return $query->where(function ($q) {
                $q->whereHas('videos')
                    ->orWhereHas('playlists');
            });
    }

}
