<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Google\Service\CloudSearch\UserId;
use Illuminate\Http\Request;

class CampaignController extends Controller
{

    public function __construct()
    {
      if(!gs('ads_module')){
          abort(404);
      }
    }


    public function index($id=null)
    {
        $pageTitle = 'Campaigns';
        $campaigns  = $this->campaignData(userId: $id);
        return view('admin.campaigns.index', compact('pageTitle', 'campaigns'));
    }

    public function active()
    {
        $pageTitle = 'Active Campaigns';
        $campaigns  = $this->campaignData('active');
        return view('admin.campaigns.index', compact('pageTitle', 'campaigns'));
    }

    public function inactive()
    {
        $pageTitle = 'Inactive Campaigns';
        $campaigns  = $this->campaignData('inactive');
        return view('admin.campaigns.index', compact('pageTitle', 'campaigns'));
    }


    protected function campaignData($scope = null, $userId = null)
    {
        if ($scope) {
            $campaigns = Campaign::$scope();
        } else {
            $campaigns = Campaign::query();
        }
         if ($userId) {
            $campaigns = $campaigns->where('user_id', $userId);
        }

        return $campaigns->searchable(['title'])->orderBy('id', 'desc')->paginate(getPaginate());
    }


    public function detail($id)
    {
        $campaign = Campaign::searchable(['title', 'advertisements:title'])->with('advertisements', 'advertisements.schedules', 'advertisements.countries')->findOrFail($id);
        $pageTitle = $campaign->title . ' Details';
        return view('admin.campaigns.detail', compact('pageTitle', 'campaign'));
    }


    public function status($id)
    {
        return Campaign::changeStatus($id);
    }
}
