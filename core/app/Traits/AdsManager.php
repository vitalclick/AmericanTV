<?php

namespace App\Traits;

use App\Constants\Status;
use App\Models\Advertisement;
use App\Models\AdvertisementAnalytics;
use App\Models\AdvertisementReached;
use App\Models\Transaction;
use App\Models\UserNotification;
use Carbon\Carbon;

trait AdsManager
{

    public function getAd($video = null)
    {
        if (!gs('ads_module')) {
    
            return $this->getDefaultAd($video);
        } else {
            return $this->getAdvancedAd($video);
        }
    }

    protected function getDefaultAd($video)
    {
        $ad = Advertisement::whereHas('categories', function ($query) use ($video) {
            $query->active()->where('category_id', $video->category_id);
        })
            ->where('user_id', '!=', $video->user_id)
            ->where('ad_module', Status::DEFAULT)
            ->where('status', Status::RUNNING)
            ->where(function ($query) {
                $query
                    ->orWhere(function ($q) {
                        $q->where('ad_type', Status::IMPRESSION)->where('available_impression', '>', 0);
                    })
                    ->orWhere(function ($q) {
                        $q->where('ad_type', Status::CLICK)->where('available_click', '>', 0);
                    })
                    ->orWhere(function ($q) {
                        $q->where('ad_type', Status::BOTH)
                            ->where(function ($q) {
                                $q->where('available_impression', '>', 0)
                                    ->orWhere('available_click', '>', 0);
                            });
                    });
            })
            ->inRandomOrder()
            ->first();

        if (!$ad) return null;

        if (in_array($ad->ad_type, [Status::IMPRESSION, Status::BOTH])) {
            $this->processImpressionAd($ad, $video);
        }

        return $ad;
    }


    protected function processImpressionAd($ad, $video)
    {
        if (!$ad || !$video) {
            return;
        }

        if(!gs('ads_module')) {
            $ad->available_impression -= 1;
            $ad->save();
        }

        $videoOwner = $video->user;
        $videoOwner->balance += gs('per_impression_earn');
        $videoOwner->save();

        $transaction               = new Transaction();
        $transaction->user_id      = $videoOwner->id;
        $transaction->video_id     = $video->id;
        $transaction->amount       = gs('per_impression_earn');
        $transaction->post_balance = $videoOwner->balance;
        $transaction->charge       = 0;
        $transaction->trx_type     = '+';
        $transaction->details      = 'Earn form ads';
        $transaction->trx          = getTrx();
        $transaction->remark       = 'ads_revenue';
        $transaction->save();

        $adAnalysis                   = new AdvertisementAnalytics();
        $adAnalysis->video_id         = $video->id;
        $adAnalysis->advertisement_id = $ad->id;
        $adAnalysis->impression       = Status::YES;
        $adAnalysis->save();

        $userNotification            = new UserNotification();
        $userNotification->user_id   = $videoOwner->id;
        $userNotification->title     = 'Ads revenue add to your balance';
        $userNotification->click_url = urlPath('user.transactions');
        $userNotification->save();
    }


    protected function getAdvancedAd($video)
    {
        $try = 1;
        $maxTries = 5;

        while ($try <= $maxTries) {
      
            $ad = Advertisement::with('countries', 'schedules')
                ->where('ad_module', Status::ADVANCED)
                ->where('ad_type','!=',Status::IN_FEED)
                ->where('status', Status::RUNNING)
                ->whereHas('campaign', function ($query) use ($video) {
                    $query->where('status', Status::RUNNING)
                        ->where('payment_status', Status::PAYMENT_SUCCESS)
                        ->where('user_id', '!=', $video->user_id);
                })
                ->inRandomOrder()
                ->first();
        
            if ($ad && $ad->eligible($video)) {
                $this->perUserCostsCalculate($ad);
                $this->adsReached($ad, $video);
             
                return $ad;
            }

            $try++;
        }

        return null;
    }


    public function adsReached($ad, $video = null)
    {
        $ad->impression += 1;
        $ad->save();
        
        $userIp = getRealIp();

        $campaign = $ad->campaign;
        $today = Carbon::now();

        $todayReached = $ad->adReaches()->whereDate('created_at', $today)->count();
        $todayEngagement = $ad->advertisementAnalytics()->whereDate('created_at', $today)->count();

        $exists = $ad->adReaches()->where('user_ip', $userIp)->where('advertisement_id', $ad->id)->exists();

        if ($todayReached < $ad->ad_reached) {
            if (!$exists) {
                $adReached = new AdvertisementReached();
                $adReached->advertisement_id = $ad->id;
                $adReached->campaign_id = $campaign->id;
                $adReached->user_ip = $userIp;
                $adReached->save();
                $this->perUserCostsCalculate($ad);
            }
        }

        if ($todayEngagement < $ad->ad_engagement) {
               $this->processImpressionAd($ad, $video);
        }
    }


/**
 * Calculates the cost per user reach for an advertisement and deducts it from the campaign's total amount.
 *
 * @param Advertisement $ad The advertisement for which the per-user cost is calculated.
 * 
 * This function retrieves the campaign associated with the advertisement, calculates the cost 
 * per user reach by dividing the daily budget by the ad's reach, and deducts this value from 
 * the campaign's total amount.
 */


    public function perUserCostsCalculate($ad)
    {
      
        $campaign = $ad->campaign;
        $dailyBudget =  $ad->daily_costs;
   
        $perUserCost  = $dailyBudget / $ad->ad_reached;
     
        $campaign->available_amount -= $perUserCost;
        $campaign->save();

    }

      public function perUserEngagementCostsCalculate($ad)
    {
      
        $campaign = $ad->campaign;
        $dailyBudget =  $ad->daily_costs;
   
        $perUserCost  = $dailyBudget / $ad->ad_engagement;
     
        $campaign->available_amount -= $perUserCost;
        $campaign->save();
        
    }


}
