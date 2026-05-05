<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class WithdrawSetting extends Model {

    protected $casts = [
        'user_data' => 'object',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function withdrawMethod() {
        return $this->belongsTo(WithdrawMethod::class);
    }

    public function nextWithdrawDate() {

        $date   = Carbon::now();
        $method = $this->withdrawMethod;

        if ($method->schedule_type == 'daily') {
            $date = $date->addDay();
        } else if ($method->schedule_type == 'weekly') {
            $date = $date->addWeek();
        } else if ($method->schedule_type == 'monthly') {
            $firstDayOfMonth  = Carbon::now()->startOfMonth();
            $lastDayOfMonth   = Carbon::now()->lastOfMonth();
            $middleDayOfMonth = $firstDayOfMonth->copy()->addDays($firstDayOfMonth->diffInDays($lastDayOfMonth) / 2);

            if ($method->schedule == 'first_day') {
                $date = $firstDayOfMonth;
                if (Carbon::now() > $firstDayOfMonth) {
                    $date = $firstDayOfMonth->addMonth();
                }
            } else if ($method->schedule == 'fifteenth_day') {
                $date = $middleDayOfMonth;
                if (Carbon::now() > $middleDayOfMonth) {
                    $date = $middleDayOfMonth->addMonth();
                }
            } else if ($method->schedule == 'last_day') {
                $date = $lastDayOfMonth;
            }

        }

        return Carbon::parse($date)->toDateString();
    }

}
