<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdPlayDuration;
use App\Models\Advertisement;
use App\Models\AdvertisementAnalytics;
use App\Models\AdvertisementCountry;
use App\Models\AdvertisementReached;
use App\Models\AdvertisementSchedule;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Storage;
use App\Models\Video;
use App\Rules\FileTypeValidate;
use App\Traits\GetDateMonths;
use App\Traits\StorageDriver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class AdsController extends Controller
{
    use StorageDriver, GetDateMonths;
    public function adSetting($slug = null)
    {
        if (!$slug) {
            $notify[] = ['error' => 'Invalid slug provided'];
            return back()->withNotify($notify);
        }

        $pageTitle = 'Ads Settings';
        $video = Video::authUser()->where('slug', $slug)->first();
        return view('Template::user.ads.setting', compact('pageTitle', 'slug', 'video'));
    }

    public function addPlayDuration(Request $request, $slug = null)
    {

        if (!$slug) {
            $notify[] = ['error' => 'Invalid slug provided'];
            return back()->withNotify($notify);
        }

        $request->validate(
            [
                'play_durations.*' => 'required|numeric',
            ],
            [
                'play_durations.*.required' => 'The play duration field is required.',
                'play_durations.*.numeric' => 'The play duration must be a number.',
            ],
        );

        $video = Video::authUser()->where('slug', $slug)->with('adPlayDurations')->firstOrFail();

        if ($video->adPlayDurations) {
            $video->adPlayDurations()->delete();
        }


        if ($request->play_durations) {
            $play_durations = $request->play_durations;

            if (is_array($play_durations)) {
                sort($play_durations);
            }

            foreach ($play_durations as $play_duration) {
                $addPlayDuration = new AdPlayDuration();
                $addPlayDuration->video_id = $video->id;
                $addPlayDuration->play_duration = $play_duration;
                $addPlayDuration->save();
            }
        }

        $notify[] = ['success' => 'Add ad play duration.'];
        return back()->withNotify($notify);
    }




    public function create($slug)
    {

        if (!gs('ads_module')) {
            abort(404);
        }

        $campaign = Campaign::where('user_id', auth()->id())->where('slug', $slug)->with('advertisements')->first();

        if (!$campaign) {
            $notify[] = ['error' => 'Campaign not found.'];
            return back()->withNotify($notify);
        }
        $countries  = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $categories = Category::active()->get();
        $videoLists = Video::where('user_id', auth()->id())->regular()->published()->free()->public()->paginate(getPaginate());
        $pageTitle = 'Create Ads';
        return view('Template::advertiser.ad_set.create', compact('pageTitle', 'campaign', 'countries', 'videoLists', 'categories'));
    }


    public function editAdSet($id)
    {

        if (!gs('ads_module')) {
            abort(404);
        }

        $advertisement = Advertisement::with('campaign', 'countries', 'expectedCountries', 'schedules')->where('user_id', auth()->id())->findOrFail($id);

        $pageTitle = 'Edit ' . $advertisement->title;
        $campaign = $advertisement->campaign;
        $countries  = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $categories = Category::active()->get();
        $videoLists = Video::where('user_id', auth()->id())->regular()->published()->free()->public()->paginate(getPaginate());
        return view('Template::advertiser.ad_set.create', compact('pageTitle', 'campaign', 'countries', 'advertisement', 'videoLists', 'categories'));
    }


    public function store(Request $request, $id = 0)
    {
        if (!gs('ads_module')) {
            abort(404);
        }


        $countryData = (array) json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countries = implode(',', array_column($countryData, 'country'));

        $videoRequired = 'nullable';
        if ($request->ad_video) {
            $videoRequired = $id ? 'nullable' : ['required', new FileTypeValidate(['mp4', 'mov', 'wmv', 'flv', 'avi', 'mkv'])];
        }


        $logoRequired = 'nullable';
        if ($request->is_clickable) {
            $logoRequired = $id ? 'nullable' : ['required', new FileTypeValidate(['jpg', 'jpeg', 'png'])];
        }


        $isRequired = $id ? 'nullable' : 'required';

        $rules = [
            'title' => 'required|string|max:255',
            'campaign_id' => 'required|integer|exists:campaigns,id',
            'amount' => 'required|integer|min:1',
            'ad_reached' => 'required|integer|min:1',
            'ad_engagement' => 'required|integer|min:1',
            'ad_type' => 'required|in:1,2,3,4',
            'start_date' => $isRequired . '|date',
            'end_date' => $isRequired . '|date|after_or_equal:start_date',
            'schedule_type' => $isRequired . '|in:1,2',

            'countries' => 'nullable|array|min:1',
            'countries.*' => 'nullable|in:' . $countries,

            'expected_countries' => 'nullable|array|min:1',
            'expected_countries.*' => 'nullable|in:' . $countries,

            'ad_video' => $videoRequired,
            'button_label' => 'required_if:is_clickable,1|max:255',
            'action_url' => 'required_if:is_clickable,1|url|max:255',
            'logo' => $logoRequired,
            'is_all_countries' => 'nullable|in:0,1',
            'is_all_categories' => 'nullable|in:0,1',
        ];


        if ($request->schedule_type == 2) {
            $rules = array_merge($rules, [
                'custom_start_date' => $isRequired . '|array|min:1',
                'custom_start_date.*' => 'required|date|after_or_equal:start_date|before_or_equal:end_date',
                'custom_start_time.*' => 'required',
                'custom_end_date' => $isRequired . '|array|min:1',
                'custom_end_date.*' => 'required|date|after_or_equal:custom_start_date.*|before_or_equal:end_date',
                'custom_end_time.*' => 'required',
            ]);
        }


        $messages = [
            'custom_start_date.*.required' => 'The custom start date field is required.',
            'custom_start_time.*.required' => 'The custom start time field is required.',
            'custom_end_date.*.required' => 'The custom end date field is required.',
            'custom_end_time.*.required' => 'The custom end time field is required.',
            'custom_end_date.*.after_or_equal' => 'The custom end date must be after or equal to start date.',
            'custom_end_date.*.before_or_equal' => 'The custom end date must be before or equal to end date.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return responseError('validation_error', $validator->errors());
        }

        if (!$id && !$request->ad_video && !$request->video_id) {
            $notify[] = ['error', 'Please select a video or upload one from your device.'];
            return responseError('validation_error', $notify);
        }

        $campaign  = Campaign::where('user_id', auth()->id())->findOrFail($request->campaign_id);

        $categories = null;
        if ($request->is_all_categories) {

            if ($request->except_categories) {
                $categories = Category::whereIn('id', $request->except_categories)->get();

                if (count($categories) != count($request->except_categories)) {
                    $notify[] = ['error', 'Please select valid categories'];
                    return responseError('validation_error', $notify);
                }

                $categories = Category::whereNotIn('id', $request->except_categories)->get();
            }
        } else if ($request->categories) {
            $categories = Category::whereIn('id', $request->categories)->get();
            if (count($categories) != count($request->categories)) {
                $notify[] = ['error', 'Please select valid categories'];

                return responseError('validation_error', $notify);
            }
        }

        if ($id) {
            $advertisement = Advertisement::where('user_id', auth()->id())->findOrFail($id);
        } else {
            $advertisement = new Advertisement();
        }

        if ($request->video_id) {
            $video = Video::regular()->free()->where('user_id', auth()->id())->where('id', $request->video_id)->first();
            if ($video) {
                $advertisement->video_id = $video->id;
            }
        }

        $advertisement->campaign_id = $campaign->id;
        $advertisement->user_id = auth()->id();
        $advertisement->title = $request->title;
        $advertisement->daily_costs = $request->amount;
        $advertisement->ad_reached = $request->ad_reached;
        $advertisement->ad_engagement = $request->ad_engagement;
        $advertisement->ad_type = $request->ad_type;
        $advertisement->start_date = $request->start_date;
        $advertisement->end_date = $request->end_date;

        $advertisement->schedule_type = $request->schedule_type;
        $advertisement->button_label = $request->button_label;
        $advertisement->url = $request->action_url;
        $advertisement->is_clickable = $request->is_clickable ? Status::YES : Status::NO;
        $advertisement->ad_module = gs('ads_module');
        $advertisement->status = gs('ads_auto_approve') ? Status::RUNNING : Status::ADVERTISEMENT_PENDING;
        $advertisement->payment_status = Status::PAYMENT_SUCCESS;
        if ($request->logo) {
            $advertisement->logo = fileUploader($request->logo, getFilePath('adLogo'), getFileSize('adLogo'), $advertisement->logo);
        }

        if ($request->is_all_categories) {
            $advertisement->is_all_categories = Status::YES;
        }
        $advertisement->save();

        if ($id) {
            $this->oldFileRemove($request, $advertisement);
        }

        if ($request->ad_video) {
            $response =  $this->fileUpload($request, $advertisement);
            if ($response == false) {
                $advertisement->delete();
                $notify[] = ['error', 'Failed to upload video.'];
                return responseError('validation_error', $notify);
            }
        }



        if ($categories) {
            $advertisement->categories()->sync($categories);
        }


        if ($request->is_all_countries) {
            $advertisement->is_all_countries = Status::YES;
            $advertisement->save();

            $advertisement->countries()->delete();

            if ($request->except_countries) {
                foreach ($request->except_countries as $country) {
                    $countries = new AdvertisementCountry();
                    $countries->campaign_id = $campaign->id;
                    $countries->advertisement_id = $advertisement->id;
                    $countries->country = $country;
                    $countries->except = 1;
                    $countries->save();
                }
            }
        } else {
            if ($request->countries) {
                if ($id) {
                    $countries = $advertisement->countries()->delete();
                }
                foreach ($request->countries as $country) {
                    $countries = new AdvertisementCountry();
                    $countries->campaign_id = $campaign->id;
                    $countries->advertisement_id = $advertisement->id;
                    $countries->country = $country;
                    $countries->save();
                }
            }
        }




        if ($request->schedule_type == Status::CUSTOM_DAYS && is_array($request->custom_start_date)) {

            if ($id) {
                $advertisementSchedule = $advertisement->schedules()->delete();
            }

            foreach ($request->custom_start_date as $key => $date) {
                if (isset($request->custom_end_date[$key])) {
                    $advertisementSchedule = new AdvertisementSchedule();
                    $advertisementSchedule->advertisement_id = $advertisement->id;
                    $advertisementSchedule->campaign_id = $campaign->id;
                    $advertisementSchedule->custom_start_date = Carbon::parse($date);
                    $advertisementSchedule->custom_end_date = Carbon::parse($request->custom_end_date[$key]);
                    $advertisementSchedule->save();
                }
            }
        }


        $response = $this->calculateTotalAmount($advertisement);

        if ($response == false) {
            $advertisementSchedule = $advertisement->schedules()->delete();
            $advertisement->countries()->delete();
            $advertisement->delete();

            $notify[] = ['error', 'Failed to calculate total amount.'];
            return responseError('validation_error', $notify);
        }



        $notify[] = ['success', 'Advertisement added successfully.'];
        return responseSuccess('advertisement_add_success', $notify);
    }


    public function calculateTotalAmount($advertisement)
    {


        try {
            $diffInSchedule = 0;
            $diffInDailyAd = 0;
            $dailyAdBudget = 0;
            $scheduleBudget = 0;

            if ($advertisement->schedule_type == 1) {
                $start = Carbon::parse($advertisement->start_date);
                $end = Carbon::parse($advertisement->end_date);
                $days = $start->diffInDays($end) + 1;
                $diffInDailyAd += $days;
                $dailyAdBudget += $advertisement->daily_costs;
            }

            if ($advertisement->schedule_type == Status::CUSTOM_DAYS && $advertisement->schedules) {
                foreach ($advertisement->schedules as $schedule) {
                    $start = Carbon::parse($schedule->custom_start_date);
                    $end = Carbon::parse($schedule->custom_end_date);
                    $days = $start->diffInDays($end) + 1;
                    $diffInSchedule += $days;
                }

                $scheduleBudget += $advertisement->daily_costs;
            }

            $totalAmount = ($scheduleBudget * round($diffInSchedule) + $dailyAdBudget * round($diffInDailyAd));

            $advertisement->total_amount = $totalAmount;
            $advertisement->save();

            return true;
        } catch (\Throwable $th) {
            return false;
            //throw $th;
        }
    }


    public function oldFileRemove($request, $advertisement)
    {
        if ($request->hasFile('video')) {
            $path = getFilePath('adVideo') . '/' . $advertisement->ad_file;

            if (@$$advertisement->storage) {
                $this->removeOldFile($advertisement, $advertisement->storage, $advertisement->ad_file, 'ads');
            } else {
                File::delete($path);
            }
        }
    }

    public function fileUpload($request, $advertisement)
    {
        $availableStorage = Storage::active()->where('available_space', '>', 0)->exists();

        if ($request->hasFile('ad_video')) {
            if (gs('is_storage') && $availableStorage) {
                $fileName = now()->format('Y/F') . '/' . uniqid() . time() . '.' . $request->ad_video->getClientOriginalExtension();
                $advertisement->ad_file = fileUploader($request->ad_video, getFilePath('adVideo') . '/' . now()->format('Y/F'), filename: $fileName);
                $path = getFilePath('adVideo') . '/' . $advertisement->ad_file;
                $response =  $this->uploadServer($fileName, $path, $advertisement, 'ads');
                return $response;
            } else {
                try {
                    $fileName = now()->format('Y/F') . '/' . uniqid() . time() . '.' . $request->ad_video->getClientOriginalExtension();
                    $advertisement->ad_file = fileUploader($request->ad_video, getFilePath('adVideo') . '/' . now()->format('Y/F'), filename: $fileName);
                    $advertisement->save();
                    return true;
                } catch (\Exception $exp) {
                    return false;
                }
            }
        }
    }


    public function getVideo()
    {

        $videoLists = Video::searchable(['title'])->where('user_id', auth()->id())->regular()->published()->free()->public()->paginate(getPaginate());

        return response()->json([
            'status' => 'success',
            'data'   => [
                'videoLists'   => $videoLists,
                'current_page' => $videoLists->currentPage(),
                'last_page'    => $videoLists->lastPage(),
                'total'        => $videoLists->total(),
            ],
        ]);
    }


    public function analytics($id)
    {
        $advertisement = Advertisement::where('user_id', auth()->id())->findOrFail($id);
        $pageTitle = 'Analytics';
        return view('Template::advertiser.ad.analytics', compact('pageTitle', 'advertisement'));
    }

    public function analyticsChart(Request $request, $id)
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
            ->whereHas('advertisement', function ($q) use ($user, $id) {
                $q->where('user_id', $user->id)->where('ad_module', gs('ads_module'))->where('id', $id);
            })
            ->selectRaw('SUM(click) AS click')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $impressions = AdvertisementAnalytics::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->whereHas('advertisement', function ($q) use ($user, $id) {
                $q->where('user_id', $user->id)->where('ad_module', gs('ads_module'))->where('id', $id);
            })
            ->selectRaw('SUM(impression) AS impression')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $adReached = AdvertisementReached::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)->where('advertisement_id', $id)
            ->selectRaw('SUM(reach_count) AS reach_count')
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
                'total_reached_users' => $adReached->where('created_on', $date)->first()?->reach_count ?? 0,
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
            [
                'name' => 'Reached Users',
                'data' => $data->pluck('total_reached_users'),
            ]
        ];

        return response()->json($report);
    }
}
