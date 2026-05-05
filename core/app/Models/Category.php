<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class Category extends Model {
    use GlobalStatus;

    public function videos() {
        return $this->hasMany(Video::class);
    }

    public function advertisements() {
        return $this->belongsToMany(Advertisement::class);
    }

    
}
