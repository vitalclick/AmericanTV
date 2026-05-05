<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasedPlan extends Model
{

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

}
