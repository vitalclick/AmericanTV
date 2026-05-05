<?php

namespace App\Models;

use App\Constants\Status;

use Illuminate\Database\Eloquent\Model;

class AdvertisementAnalytics extends Model
{
    
    public function advertisement(){
        return $this->belongsTo(Advertisement::class);
    }

    

    public function scopeClick($query){

        return $query->where('click',Status::YES);
    }


    public function scopeImpression($query){

        return $query->where('impression',Status::YES);
    }

}
