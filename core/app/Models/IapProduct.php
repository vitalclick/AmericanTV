<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IapProduct extends Model
{
    protected $casts = [
        'is_subscription' => 'boolean',
        'active'          => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }

    public function resolveItem()
    {
        return match ($this->type) {
            'plan'     => $this->plan,
            'video'    => $this->video,
            'playlist' => $this->playlist,
            default    => null,
        };
    }

    public function scopeForApple($query, string $productId)
    {
        return $query->where('apple_product_id', $productId)->where('active', true);
    }

    public function scopeForGoogle($query, string $productId)
    {
        return $query->where('google_product_id', $productId)->where('active', true);
    }
}
