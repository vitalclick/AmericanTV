<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDeletionRequest extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'source',
        'reason',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
