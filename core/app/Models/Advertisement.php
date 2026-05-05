<?php

namespace App\Models;

use App\Constants\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function countries()
    {
        return $this->hasMany(AdvertisementCountry::class)->where('except', Status::NO);
    }

    public function expectedCountries()
    {
        return $this->hasMany(AdvertisementCountry::class)->where('except', Status::YES);
    }


    public function schedules()
    {

        return $this->hasMany(AdvertisementSchedule::class, 'advertisement_id');
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }


    public function storage()
    {
        return $this->belongsTo(Storage::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function advertisementAnalytics()
    {
        return $this->hasMany(AdvertisementAnalytics::class);
    }



    public function scopePending($query)
    {

        return $query->where('status', Status::ADVERTISEMENT_PENDING);
    }

    public function scopeRejected($query)
    {

        return $query->where('status', Status::ADVERTISEMENT_REJECTED);
    }



    public function scopeRunning($query)
    {
        if (gs('ads_module')) {
            return $query->where('status', Status::RUNNING)->where('start_date', '<=', now())->where('end_date', '>=', now());
        } else {
            return $query->where('status', Status::RUNNING);
        }
    }

    public function scopeExpired($query)
    {


        $query->where('status', Status::RUNNING)->where('end_date', '<', now());
    }


    public function scopeStop($query)
    {
        return $query->where('status', Status::PAUSE);
    }

    public function scopeImpression($query)
    {
        return $query->where('ad_type', Status::IMPRESSION);
    }

    public function scopeClick($query)
    {
        return $query->where('ad_type', Status::CLICK);
    }

    public function scopeBoth($query)
    {
        return $query->where('ad_type', Status::BOTH);
    }


    public function adReaches()
    {
        return $this->hasMany(AdvertisementReached::class);
    }

    public function statusBadge(): Attribute
    {

        return new Attribute(function () {
            $html = '';
            if (!gs('ads_module')) {
                if ($this->status == Status::RUNNING) {
                    $html = '<span class="badge badge--success">' . trans('Running') . '</span>';
                } else if ($this->status == Status::PAUSE) {
                    $html = '<span class="badge badge--danger">' . trans('Pause') . '</span>';
                }
            } else {
                if ($this->status == Status::RUNNING && $this->start_date <= now() && $this->end_date >= now()) {
                    $html = '<span class="badge badge--success">' . trans('Running') . '</span>';
                } else if ($this->status == Status::ADVERTISEMENT_PENDING) {
                    $html = '<span class="badge badge--warning">' . trans('Pending') . '</span>';
                } else if ($this->end_date < now()) {
                    $html = '<span class="badge badge--danger">' . trans('Expired') . '</span>';
                } else if ($this->status == Status::PAUSE) {

                    $html = '<span class="badge badge--danger">' . trans('Pause') . '</span>';
                } else if ($this->status == Status::ADVERTISEMENT_REJECTED) {

                    $html = '<span class="badge badge--danger">' . trans('Rejected') . '</span>';
                }
            }

            return $html;
        });
    }


    public function eligible($video = null): bool
    {
        $eligible = false;

        $campaign = $this->campaign;
        if ($campaign && $campaign->status  != Status::RUNNING || $campaign->payment_status != Status::PAYMENT_SUCCESS) {
            return false;
        }

        $dailyBudget =  $this->daily_costs;
        $perUserCost  = $dailyBudget / $this->ad_reached;

        if ($campaign->available_amount < $perUserCost) {
            return false;
        }

        $countries = $this->countries()->pluck('country')->toArray();

        $info       = json_decode(json_encode(getIpInfo()), true);
        $ownCountry = @implode(',', $info['country']);


        if ($this->is_all_countries) {

            $eligible = true;
            $exceptCountries = $this->expectedCountries()->pluck('country')->toArray();
            if (!empty($exceptCountries)) {
                $eligible = !in_array($ownCountry, $exceptCountries);

                if (!$eligible) {
                    return false;
                }
            }
        } else {
            $eligible = true;
            if (!empty($countries)) {
                $eligible = in_array($ownCountry, $countries);

                if (!$eligible) {
                    return false;
                }
            }
        }

        if ($this->is_all_categories) {
            $eligible = true;
        }
        if (($this->categories->count() > 0 && $this->ad_type != Status::IN_FEED) && $video) {

            $eligible = false;
            $categoryIds = $this->categories->pluck('id')->toArray();

            if (empty($categoryIds)) {
                return false;
            }

            $eligible = in_array($video->category_id, $categoryIds);

            if (!$eligible) {
                return false;
            }
        }


        $today = now()->startOfDay();

        if ($this->schedule_type == Status::CUSTOM_DAYS) {
            $hasScheduleToday = $this->schedules()
                ->whereDate('custom_start_date', '<=', $today)
                ->whereDate('custom_end_date', '>=', $today)
                ->exists();

            $eligible = false;

            if ($hasScheduleToday || showDateTime($this->start_date, 'y-m-d') <= now()->format('y-m-d') ||  showDateTime($this->end_date, 'y-m-d') >= now()->format('y-m-d')) {
                $eligible = true;
            }
        } else {
            $eligible = false;

            if (showDateTime($this->start_date, 'y-m-d') <= now()->format('y-m-d') && showDateTime($this->end_date, 'y-m-d') >= now()->format('y-m-d')) {
                $eligible = true;
            }
        }

        $todayEngagement = $this->advertisementAnalytics()
            ->whereDate('created_at', $today)
            ->count();

        $todayReached = $this->adReaches()->whereDate('created_at', $today)->count();
        if ($todayReached >= $this->ad_reached && $todayEngagement >= $this->ad_engagement) {
            $eligible = false;
        }
        return $eligible;
    }




    public function paymentStatusBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->payment_status == Status::PAYMENT_INITIATE) {
                $html = '<span class="badge badge--dark">' . trans('Initiated') . '</span>';
            } else if ($this->payment_status == Status::PAYMENT_PENDING) {
                $html = '<span class="badge badge--warning">' . trans('Pending') . '</span>';
            } else if ($this->payment_status == Status::PAYMENT_REJECT) {
                $html = '<span class="badge badge--danger">' . trans('Reject') . '</span>';
            } else if ($this->payment_status == Status::PAYMENT_SUCCESS) {
                $html = '<span class="badge badge--success">' . trans('Success') . '</span>';
            }



            return $html;
        });
    }

    public function adTypeBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if (gs('ads_module')) {

                if ($this->ad_type == Status::ALL_VIEWS) {
                    $html = '<span class="badge badge--success">' . trans('All Views') . '</span>';
                } else if ($this->ad_type == Status::SKIPPABLE) {
                    $html = '<span class="badge badge--dark">' . trans('Skippable') . '</span>';
                } else if ($this->ad_type == Status::NON_SKIPPABLE) {
                    $html = '<span class="badge badge--info">' . trans('Non Skippable') . '</span>';
                } else if ($this->ad_type == Status::IN_FEED) {
                    $html = '<span class="badge badge--danger">' . trans('In Feed') . '</span>';
                } else {
                    $html = '<span class="badge badge--warning">' . trans('Shorts') . '</span>';
                }
            } else {
                if ($this->ad_type == Status::CLICK) {
                    $html = '<span class="badge badge--success">' . trans('CLick') . '</span>';
                } else if ($this->ad_type == Status::IMPRESSION) {
                    $html = '<span class="badge badge--dark">' . trans('Impression') . '</span>';
                } else if ($this->ad_type == Status::BOTH) {
                    $html = '<span class="badge badge--danger">' . trans('Both') . '</span>';
                }
            }

            return $html;
        });
    }
}
