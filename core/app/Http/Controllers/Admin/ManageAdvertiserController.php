<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\Advertisement;
use App\Models\AdvertisementAnalytics;
use App\Models\Campaign;
use App\Models\Form;
use App\Models\User;
use App\Traits\GetDateMonths;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManageAdvertiserController extends Controller {

    use GetDateMonths;

    public function pending() {
        $pageTitle = "Pending Advertisers";

        $advertisers = $this->advertiserDatA('pendingAdvertisers');
        return view('admin.advertiser.index', compact('pageTitle', 'advertisers'));
    }
    public function approved() {
        $pageTitle   = "Pending Advertisers";
        $advertisers = $this->advertiserDatA('approvedAdvertisers');

        return view('admin.advertiser.index', compact('pageTitle', 'advertisers'));
    }

    public function rejected() {
        $pageTitle   = "Pending Advertisers";
        $advertisers = $this->advertiserDatA('rejectedAdvertisers');
        return view('admin.advertiser.index', compact('pageTitle', 'advertisers'));
    }

    protected function advertiserData($scope = null) {
        if ($scope) {
            $advertisers = User::$scope();
        } else {
            $advertisers = User::query();
        }

        return $advertisers
            ->searchable(['username', 'email'])
            ->latest()
            ->paginate(getPaginate());
    }

    public function detail($id) {
        $user      = User::where('advertiser_status', '!=', Status::MONETIZATION_INITIATE)->findOrFail($id);
        $pageTitle = 'Advertiser Details for ' . $user->fullname;
        $widget['total_ads']             = $user->advertisements()->where('ad_module', gs('ads_module'))->count();
        $widget['running_ads']           = $user->advertisements()->where('ad_module', gs('ads_module'))->running()->count();
        $widget['pause_ads']             = $user->advertisements()->where('ad_module', gs('ads_module'))->stop()->count();
        $widget['click_ads']             = $user->advertisements()->where('ad_module', gs('ads_module'))->click()->count();
        $widget['impressions_ads']       = $user->advertisements()->where('ad_module', gs('ads_module'))->impression()->count();
        $widget['both_type_ads']         = $user->advertisements()->where('ad_module', gs('ads_module'))->both()->count();
        $widget['total_spent_amount']    = $user->advertisements()->where('ad_module', gs('ads_module'))->sum('total_amount');



        $widget['last_seven_days_spent'] = $user->advertisements()->where('ad_module', gs('ads_module'))->where('created_at', '>=', now()->subDays(7))->sum('total_amount');
        $totalCampaign    = Campaign::where('user_id', $user->id)->count(); 
                $totalBudget = 0;
        $availableBudget = 0;

        if(gs('ads_module') == Status::YES){
            $totalBudget =  $user->advertisements()->where('ad_module', Status::YES)->sum('total_amount');
            $availableBudget = Campaign::where('user_id', $user->id)->sum('total_amount');
            $dailyAds = $user->advertisements()->where('ad_module', Status::YES)->where('schedule_type', Status::ALL_DAYS)->count();
            $customAds = $user->advertisements()->where('ad_module', Status::YES)->where('schedule_type', Status::CUSTOM_DAYS)->count();
        }

        return view('admin.advertiser.detail', compact('pageTitle', 'user', 'widget','totalCampaign', 'totalBudget', 'availableBudget', 'dailyAds', 'customAds'));
    }

    public function dataApprove($id) {
        $user                    = User::where('advertiser_status', '!=', Status::MONETIZATION_INITIATE)->findOrFail($id);
        $user->advertiser_status = Status::ADVERTISER_APPROVED;
        $user->save();

        notify($user, 'ADVERTISER_APPROVE', []);

        $notify[] = ['success', 'Advertiser document approved successfully'];
        return back()->withNotify($notify);
    }

    public function dataReject(Request $request, $id) {
        $request->validate([
            'reason' => 'required',
        ]);
        $user                    = User::where('advertiser_status', '!=', Status::MONETIZATION_INITIATE)->findOrFail($id);
        $user->advertiser_status = Status::ADVERTISER_REJECTED;

        $user->advertiser_rejection_reason = $request->reason;
        $user->save();

        notify($user, 'ADVERTISER_REJECT', [
            'reason' => $request->reason,
        ]);

        $notify[] = ['success', 'Advertiser document  rejected successfully'];
        return back()->withNotify($notify);
    }

    public function report(Request $request, $id) {

        $user = User::where('advertiser_status', '!=', Status::MONETIZATION_INITIATE)->findOrFail($id);

        $diffInDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));

        $groupBy = $diffInDays > 30 ? 'months' : 'days';
        $format  = $diffInDays > 30 ? '%M-%Y' : '%d-%M-%Y';

        if ($groupBy == 'days') {
            $dates = $this->getAllDates($request->start_date, $request->end_date);
        } else {
            $dates = $this->getAllMonths($request->start_date, $request->end_date);
        }

        $clicks = AdvertisementAnalytics::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->whereHas('advertisement', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('ad_module', gs('ads_module'));
            })
            ->selectRaw('SUM(click) AS click')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $impressions = AdvertisementAnalytics::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->whereHas('advertisement', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('ad_module', gs('ads_module'));
            })
            ->selectRaw('SUM(impression) AS impression')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $data = [];

        foreach ($dates as $date) {
            $data[] = [
                'created_on'        => showDateTime($date, 'd-M-y'),
                'total_clicks'      => $clicks->where('created_on', $date)->first()?->click ?? 0,
                'total_impressions' => $impressions->where('created_on', $date)->first()?->impression ?? 0,
            ];

        }

        $data = collect($data);

        $report['created_on'] = $data->pluck('created_on');
        $report['data']       = [

            [
                'name' => 'Clicks',
                'data' => $data->pluck('total_clicks'),
            ],
            [
                'name' => 'Impressions',
                'data' => $data->pluck('total_impressions'),
            ],

        ];

        return response()->json($report);
    }

    public function setting() {
        $pageTitle = 'Advertiser Setting';
        $form      = Form::where('act', 'advertiser')->first();
        return view('admin.advertiser.setting', compact('pageTitle', 'form'));
    }

    public function settingUpdate(Request $request) {
        $formProcessor       = new FormProcessor();
        $generatorValidation = $formProcessor->generatorValidation();
        $request->validate($generatorValidation['rules'], $generatorValidation['messages']);
        $exist = Form::where('act', 'advertiser')->first();
        $formProcessor->generate('advertiser', $exist, 'act');

        $notify[] = ['success', 'Advertiser data updated successfully'];
        return back()->withNotify($notify);
    }

}
