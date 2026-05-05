<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\Advertisement;
use App\Models\AdvertisementAnalytics;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Deposit;
use App\Models\Form;
use App\Models\GatewayCurrency;
use App\Models\Storage;
use App\Rules\FileTypeValidate;
use App\Traits\GetDateMonths;
use App\Traits\StorageDriver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class AdvertiserController extends Controller
{
    use GetDateMonths, StorageDriver;
    public function home()
    {
        $pageTitle = 'Advertiser';
        $user      = auth()->user();
        $totalAds  = Advertisement::where('user_id', $user->id)->where('ad_module', gs('ads_module'))->count();

        $analyticsQuery = AdvertisementAnalytics::whereHas('advertisement', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('ad_module', gs('ads_module'));
        });

        $totalClicks      = (clone $analyticsQuery)->where('click', Status::YES)->count();
        $totalImpressions = (clone $analyticsQuery)->where('impression', Status::YES)->count();
        $totalCampaign    = Campaign::where('user_id', $user->id)->count(); 

        $advertisements = Advertisement::with('campaign')->where('ad_module', gs('ads_module'))->where('user_id', auth()->id())->latest()->take(10)->get();

        
        return view('Template::advertiser.dashboard', compact('user', 'pageTitle', 'totalImpressions', 'totalClicks', 'totalAds','totalCampaign','advertisements'));
    }

    public function adsChart(Request $request)
    {
        $user       = auth()->user();
        $diffInDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));

        $groupBy = $diffInDays > 30 ? 'months' : 'days';
        $format  = $diffInDays > 30 ? '%M-%Y' : '%d-%M-%Y';

        if ($groupBy == 'days') {
            $dates = $this->getAllDates($request->start_date, $request->end_date);
        } else {
            $dates = $this->getAllMonths($request->start_date, $request->end_date);
        }

        $clicks = AdvertisementAnalytics::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->whereHas('advertisement', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('ad_module', gs('ads_module'));
            })
            ->selectRaw('SUM(click) AS click')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $impressions = AdvertisementAnalytics::whereDate('created_at', '>=', $request->start_date)
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

    public function dataSubmit(Request $request)
    {

        $form           = Form::where('act', 'advertiser')->firstOrFail();

        $formData       = $form->form_data;
        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $user = auth()->user();
        foreach (@$user->advertiser_data ?? [] as $advertiserData) {
            if ($advertiserData->type == 'file') {
                fileManager()->removeFile(getFilePath('verify') . '/' . $advertiserData->value);
            }
        }
        $advertiserData          = $formProcessor->processFormData($request, $formData);


        $user->advertiser_data   = $advertiserData;
        $user->advertiser_status = Status::ADVERTISER_PENDING;
        $user->save();

        $notify[] = ['success', 'Data submitted successfully'];
        return back()->withNotify($notify);
    }

    public function adList()
    {
        if(gs('ads_module')){
            abort(404);
        }
        $pageTitle      = 'All Advertisement';
        $advertisements = Advertisement::searchable(['title', 'categories:name'])
            ->where('user_id', auth()->id())
            ->where('ad_module', gs('ads_module'))
            ->with('categories')->orderBy('id', 'desc')
            ->where('step', Status::SECOND_STEP)
            ->paginate(getPaginate());

        $totalClick = Advertisement::where('ad_module', gs('ads_module'))->where('user_id', auth()->id())->sum('click');

        $totalImpression     = Advertisement::where('ad_module', gs('ads_module'))->where('user_id', auth()->id())->sum('impression');
        $availableClick      = Advertisement::where('ad_module', gs('ads_module'))->where('user_id', auth()->id())->sum('available_click');
        $availableImpression = Advertisement::where('ad_module', gs('ads_module'))->where('user_id', auth()->id())->sum('available_impression');

      

        return view('Template::advertiser.ad.list', compact('pageTitle', 'advertisements', 'totalClick', 'totalImpression', 'availableClick', 'availableImpression'));
    }

    public function advanceAdList()
    {
        if(!gs('ads_module')){
            abort(404);
        }
        $pageTitle      = 'All Advertisement';
        $advertisements = Advertisement::where('ad_module', Status::YES)->searchable(['title'])
            ->where('user_id', auth()->id())
            ->with('campaign', 'countries','adReaches','advertisementAnalytics')->orderBy('id', 'desc')

            ->paginate(getPaginate());
        $user = auth()->user();
            
       
        $totalAds  = Advertisement::where('ad_module', gs('ads_module'))->where('user_id', $user->id)->count();
        $totalCampaign  = Campaign::where('user_id', $user->id)->count();
        $totalDailyBudget = Advertisement::where('user_id', $user->id)->where('ad_module', gs('ads_module'))->sum('daily_costs');
        $totalCosts  = Campaign::where('user_id', $user->id)->sum('total_amount');
        

        return view('Template::advertiser.ad.advance_list', compact('pageTitle', 'advertisements', 'totalCampaign','totalAds','totalDailyBudget', 'totalCosts'));
    }



    public function createAd()
    {

        if(gs('ads_module')){
            abort(404);
        }
        $pageTitle  = 'Create Ad';
        $categories = Category::whereHas('videos')->active()->get();
        $availableStorage =  Storage::active()->where('available_space', '>', 0)->exists();
        return view('Template::advertiser.ad.create', compact('pageTitle', 'categories', 'availableStorage'));
    }

    public function uploadAdVideo(Request $request)
    {


        if (gs('ads_module') == Status::YES) {
             return response()->json([
                    'remark'  => 'error',
                    'status'  => 'error',
                    'message' => ['error' => 'This Advertise module is disabled'],
                ]);
        }



        $validator = Validator::make($request->all(), [
            'video' => ['required', new FileTypeValidate(['mp4', 'mov', 'wmv', 'flv', 'avi', 'mkv'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $initiateAds = Advertisement::where('user_id', auth()->id())->where('step', Status::FIRST_STEP)->get();
        if ($initiateAds) {
            foreach ($initiateAds as $initiateAd) {
                $path = getFilePath('adVideo') . '/' . $initiateAd->ad_file;

                if (@$initiateAd->storage) {
                    $this->removeOldFile($initiateAd, $initiateAd->storage, $initiateAd->ad_file, 'ads');
                } else {
                    File::delete($path);
                }
                $initiateAd->delete();
            }
        }

        $advertisement          = new Advertisement();
        $advertisement->user_id = auth()->id();

        if ($request->hasFile('video')) {
            try {
                $fileName = now()->format('Y/F') . '/' . uniqid() . time() . '.' . $request->video->getClientOriginalExtension();
                $advertisement->ad_file = fileUploader($request->video, getFilePath('adVideo') . '/' . now()->format('Y/F'), filename: $fileName);
            } catch (\Exception $exp) {
                return response()->json([
                    'remark'  => 'error',
                    'status'  => 'error',
                    'message' => ['error' => 'Video upload failed'],
                ]);
            
            }
        }

        $advertisement->step = Status::FIRST_STEP;
        $advertisement->save();
        return response()->json([
            'remark'  => 'success',
            'status'  => 'success',
            'message' => ['success' => 'Video uploaded successfully'],
            'data'    => [
                'advertisement' => $advertisement,
            ],
        ]);
    }


    public function uploadFtp($id)
    {

         if (gs('ads_module') == Status::YES) {
             return response()->json([
                    'remark'  => 'error',
                    'status'  => 'error',
                    'message' => ['error' => 'This Advertise module is disabled'],
                ]);
        }

        try {
            $ad = Advertisement::where('user_id', auth()->id())->find($id);

            if (!$ad) {
                return response()->json([
                    'remark'  => 'error',
                    'status'  => 'error',
                    'message' => ['error' => 'Advertisement not found'],
                ]);
            }


            $path = getFilePath('adVideo') . '/' . $ad->ad_file;
            $filename = $ad->ad_file;

            $response =  $this->uploadServer($filename, $path, $ad, 'ads');
            if ($response == false) {
                return response()->json([
                    'remark'  => 'error',
                    'status'  => 'error',
                    'message' => ['error' => 'Could not upload video to server'],
                ]);
            }

            return response()->json([
                'remark'  => 'success',
                'status'  => 'success',
                'message' => ['success' => 'Ads uploaded successfully'],
                'data'    => [
                    'advertisement' => $ad,
                ],
            ]);
        } catch (\Throwable $th) {

            return response()->json([
                'status'  => 'error',
                'message' => ['error' => $th->getMessage()],
            ]);
        }
    }

    public function processedCheckout(Request $request, $id)
    {

         if (gs('ads_module') == Status::YES) {
            abort(404);
        }

        $request->validate(
            [
                'title'         => 'required|string',
                'category_id'   => 'required|array|min:1',
                'category_id.*' => 'integer',
                'logo'          => ['required_if:ad_type,2,3', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
                'url'           => 'nullable|url|required_if:ad_type,2,3',
                'ad_type'       => 'required|numeric',
                'impression'    => 'nullable|numeric|required_if:ad_type,1,3',
                'click'         => 'nullable|numeric|required_if:ad_type,2,3',
                'button_label'  => 'nullable|string|required_if:ad_type,2,3',
            ],
            [
                'title.required'         => 'Title is required',
                'category_id.required'   => 'Please select at least one category',
                'category_id.*.integer'  => 'Each category must be a valid integer',

                'url.url'                => 'Invalid URL',
                'ad_type.required'       => 'Please select Ad type',
                'impression.required_if' => 'Please enter an impression value',
                'click.required_if'      => 'Please enter a click value',
            ],
        );

        $categories = Category::whereIn('id', $request->category_id)->get();

        if (count($categories) != count($request->category_id)) {
            $notify[] = ['error', 'Please select valid categories'];
            return back()->withNotify($notify);
        }

        $totalAmount    = 0;
        $impressionCost = 0;
        $clickCost      = 0;

        if ($request->impression) {
            $impressionCost = $request->impression * gs('per_impression_spent');
        }

        if ($request->click) {
            $clickCost = $request->click * gs('per_click_spent');
        }

        $totalAmount = $impressionCost + $clickCost;

        $advertisement        = Advertisement::where('user_id', auth()->id())->findOrFail($id);
        $advertisement->title = $request->title;
        if ($request->hasFile('logo')) {
            try {
                $advertisement->logo = fileUploader($request->logo, getFilePath('adLogo'));
            } catch (\Exception $exp) {
                $notify[] = ['error' => 'Couldn\'t upload your video'];
                return back()->withNotify($notify);
            }
        }
        $advertisement->url                  = $request->url;
        $advertisement->button_label         = $request->button_label;
        $advertisement->ad_type              = $request->ad_type;
        $advertisement->impression           = $request->impression ?? 0;
        $advertisement->available_impression = $request->impression ?? 0;
        $advertisement->click                = $request->click ?? 0;
        $advertisement->available_click      = $request->click ?? 0;
        $advertisement->ad_module      = gs('ads_module');


        if ($request->ad_type == Status::IMPRESSION || $request->ad_type == Status::BOTH) {
            $advertisement->per_impression = gs('per_impression_spent');
        }

        if ($request->ad_type == Status::CLICK || $request->ad_type == Status::BOTH) {
            $advertisement->per_click = gs('per_click_spent');
        }

        $advertisement->step         = Status::SECOND_STEP;
        $advertisement->total_amount = $totalAmount;
        $advertisement->save();

        $advertisement->categories()->sync($request->category_id);
        return to_route('user.deposit.index', $advertisement->id);
    }

    public function paymentHistory()
    {
        $pageTitle = 'Transactions';

        $payments = Deposit::searchable(['trx'])->where('user_id', auth()->id())->where(function($query){
           $query->where('advertisement_id', '!=', 0)->orWhere('campaign_id','!=', 0);
        })->orderby('id', 'desc')->paginate(getPaginate());

        return view('Template::advertiser.payment_history', compact('pageTitle', 'payments'));
    }

    public function status($id)
    {

        if (gs('ads_module') == Status::NO) {
            abort(404);
        }

        $advertisement         = Advertisement::where('user_id', auth()->id())->where('payment_status', Status::PAYMENT_SUCCESS)->findOrFail($id);
        $advertisement->status = $advertisement->status == Status::RUNNING ? Status::PAUSE : Status::RUNNING;
        $advertisement->save();

        $notify[] = ['success', 'Status has been changed'];
        return back()->withNotify($notify);
    }


    public function published($id)
    {

        $advertisement = Advertisement::with('campaign')->where('user_id', auth()->id())->findOrFail($id);

        $advertisement->status = Status::ADVERTISEMENT_PENDING;
        $advertisement->save();


        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();

        $pageTitle = 'Payment Methods';

        return view('Template::user.payment.deposit', compact('gatewayCurrency', 'pageTitle', 'advertisement'));
    }

    



}
