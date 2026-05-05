<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Campaign;
use App\Models\GatewayCurrency;

use Illuminate\Http\Request;


class CampaignController extends Controller
{

    public function index()
    {
        $pageTitle = 'New Campaign';
        $campaigns = Campaign::searchable(['title'])->where('user_id', auth()->id())->latest()->paginate(getPaginate());
        return view('Template::advertiser.campaign.index', compact('pageTitle', 'campaigns'));
    }
    public function create($id = 0)
    {
        $pageTitle = 'New Campaign';

        if ($id) {
            $campaign = Campaign::where('user_id', auth()->id())->findOrFail($id);
            $pageTitle = 'Edit Campaign';
        }
        return view('Template::advertiser.campaign.create', compact('pageTitle'));
    }



    public function save(Request $request, $id = 0)
    {

        $request->validate([
            'title' => 'required|string',
            'slug' => 'required|string|unique:campaigns,slug,' . $id,
            'total_budget' => 'required|numeric|gte:0',
            'add_budget' => 'nullable|numeric|gte:0'
        ]);

        if ($id) {
            $campaign = Campaign::where('user_id', auth()->id())->findOrFail($id);
        } else {
            $campaign = new Campaign();
        }

        $campaign->title = $request->title;
        $campaign->slug = $request->slug;
        $campaign->user_id = auth()->id();

        if($request->add_budget ){
            $campaign->hold_amount = $request->add_budget ?? 0;
        }else{
            $campaign->total_amount = $request->total_budget;
        }
        $campaign->status = Status::PAUSE;
        $campaign->payment_status = Status::PAYMENT_PENDING;
        $campaign->save();

        $notify[] = ['success', $id ? 'Campaign updated successfully' :  'Campaign created successfully.'];

        if (!$id) {
            return to_route('user.advertiser.ad.create', $campaign->slug)->withNotify($notify);
        }

        return back()->withNotify($notify);
    }

    public function gateways($id)
    {

        $campaign = Campaign::where('user_id', auth()->id())->where('payment_status', '!=', Status::PAYMENT_SUCCESS)->findOrFail($id);

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();

        $pageTitle = 'Payment Methods';

        return view('Template::user.payment.deposit', compact('gatewayCurrency', 'pageTitle', 'campaign'));
    }




    public function checkSlug()
    {
        $campaign = Campaign::where('slug', request()->slug)->exists();
        return response()->json([
            'exists' => $campaign,
        ]);
    }


    public function status($id)
    {
        $campaign = Campaign::where('user_id', auth()->id())->find($id);
        if ($campaign) {

            $campaign->status = $campaign->status == STATUS::ENABLE ? STATUS::DISABLE : STATUS::ENABLE;
            $campaign->save();

            return response()->json([
                'status' => 'success',
                'campaign_status' => $campaign->status,
                'message' =>  'Status change successfully',

            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Campaign not found',
        ]);
    }




    public function detail($slug)
    {
        $campaign = Campaign::searchable(['title', 'advertisements:title'])->with('advertisements', 'advertisements.schedules', 'advertisements.countries')->where('user_id', auth()->id())->where('slug', $slug)->firstOrFail();
        $pageTitle = $campaign->title . ' Details';
        return view('Template::advertiser.campaign.detail', compact('pageTitle', 'campaign'));
    }
}
