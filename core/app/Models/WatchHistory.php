<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchHistory extends Model
{

    protected $casts = [
        'last_view' => 'datetime'
    ];

    public function video(){
        return $this->belongsTo(Video::class);
    }
}
