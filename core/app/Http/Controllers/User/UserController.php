<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\DeviceToken;
use App\Models\Form;
use App\Models\Impression;
use App\Models\Subscriber;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserReaction;
use App\Models\Video;
use App\Models\WatchHistory;
use App\Models\WatchLater;
use App\Models\Withdrawal;
use App\Traits\GetDateMonths;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller {
    use GetDateMonths;
    public function home() {
        $pageTitle      = 'Dashboard';
        $totalViews     = Video::authUser()->published()->public()->sum('views');
        $totalVideo     = Video::authUser()->published()->count();
        $newFollowers   = Subscriber::where('user_id', auth()->id())->whereDate('created_at', '>=', now()->subDays(7))->count();
        $totalFollowers = Subscriber::where('user_id', auth()->id())->count();
        $totalEarning   = Transaction::whereIn('remark', ['ads_revenue', 'earn_from_video'])->where('user_id', auth()->id())->sum('amount');
        $totalLike      = UserReaction::where('video_owner_id', auth()->id())->where('is_like', Status::YES)->count();
        $averageViews   = $totalViews / ($totalVideo > 0 ? $totalVideo : 1);
        return view('Template::user.dashboard', compact('pageTitle', 'totalViews', 'newFollowers', 'averageViews', 'totalLike', 'totalFollowers', 'totalEarning'));
    }

    public function videos() {
        $pageTitle = 'Videos';
        $videos    = $this->videoData();
        return view('Template::user.video.list', compact('pageTitle', 'videos'));
    }

    public function stockVideos() {
        $pageTitle = 'Stock Videos';
        $videos    = $this->videoData('stock');
        return view('Template::user.video.list', compact('pageTitle', 'videos'));
    }

    public function freeVideos() {
        $pageTitle = 'Free Videos';
        $videos    = $this->videoData('free');
        return view('Template::user.video.list', compact('pageTitle', 'videos'));
    }

    public function shorts() {
        $pageTitle = 'Manage Videos';
        $shorts    = Video::authUser()
            ->searchable(['title'])
            ->where('is_shorts_video', Status::YES)->orderBy('id', 'desc')
            ->paginate(getPaginate());
        return view('Template::user.shorts.list', compact('pageTitle', 'shorts'));
    }

    protected function videoData($scope = null) {
        if ($scope) {
            $videos = Video::$scope();
        } else {
            $videos = Video::query();
        }
        return $videos
            ->authUser()
            ->searchable(['title'])
            ->regular()
            ->with('subtitles', 'videoFiles')->orderBy('id', 'desc')
            ->paginate(getPaginate());
    }

    public function kycForm() {
        if (auth()->user()->kv == Status::KYC_PENDING) {
            $notify[] = ['error', 'Your KYC is under review'];
            return to_route('user.home')->withNotify($notify);
        }
        if (auth()->user()->kv == Status::KYC_VERIFIED) {
            $notify[] = ['error', 'You are already KYC verified'];
            return to_route('user.home')->withNotify($notify);
        }
        $pageTitle = 'KYC Form';
        $form      = Form::where('act', 'kyc')->first();
        return view('Template::user.kyc.form', compact('pageTitle', 'form'));
    }

    public function kycData() {
        $user      = auth()->user();
        $pageTitle = 'KYC Data';
        abort_if($user->kv == Status::VERIFIED, 403);
        return view('Template::user.kyc.info', compact('pageTitle', 'user'));
    }

    public function kycSubmit(Request $request) {
        $form           = Form::where('act', 'kyc')->firstOrFail();
        $formData       = $form->form_data;
        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $user = auth()->user();
        foreach (@$user->kyc_data ?? [] as $kycData) {
            if ($kycData->type == 'file') {
                fileManager()->removeFile(getFilePath('verify') . '/' . $kycData->value);
            }
        }
        $userData                   = $formProcessor->processFormData($request, $formData);
        $user->kyc_data             = $userData;
        $user->kyc_rejection_reason = null;
        $user->kv                   = Status::KYC_PENDING;
        $user->save();

        $notify[] = ['success', 'KYC data submitted successfully'];
        return to_route('user.home')->withNotify($notify);
    }

    public function addDeviceToken(Request $request) {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()->all()];
        }

        $deviceToken = DeviceToken::where('token', $request->token)->first();

        if ($deviceToken) {
            return ['success' => true, 'message' => 'Already exists'];
        }

        $deviceToken          = new DeviceToken();
        $deviceToken->user_id = auth()->user()->id;
        $deviceToken->token   = $request->token;
        $deviceToken->is_app  = Status::NO;
        $deviceToken->save();

        return ['success' => true, 'message' => 'Token saved successfully'];
    }

    public function downloadAttachment($fileHash) {
        $filePath  = decrypt($fileHash);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $title     = slug(gs('site_name')) . '- attachments.' . $extension;
        try {
            $mimetype = mime_content_type($filePath);
        } catch (\Exception $e) {
            $notify[] = ['error', 'File does not exists'];
            return back()->withNotify($notify);
        }
        header('Content-Disposition: attachment; filename="' . $title);
        header('Content-Type: ' . $mimetype);
        return readfile($filePath);
    }

    public function reaction(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'is_like' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $video = Video::published()->public()->find($id);

        if (!$video) {
            return response()->json([
                'remark'  => 'video_not_found',
                'status'  => 'error',
                'message' => ['error', 'Video not found'],
            ]);
        }

        $isLike = $request->is_like;

        $userId = auth()->id();

        $existingReaction = $video->userReactions()->where('user_id', $userId)->first();

        if ($existingReaction) {
            if ($existingReaction->is_like == $isLike) {
                $existingReaction->delete();
                return response()->json([
                    'remark' => $isLike == Status::YES ? 'like_remove' : 'dislike_remove',
                    'status' => 'success',
                    'data'   => [
                        'like_count' => $video->userReactions()->like()->count(),
                    ],
                ]);
            } else {
                $existingReaction->is_like = $isLike;
                $existingReaction->save();
                return response()->json([
                    'remark' => $isLike == Status::YES ? 'like' : 'dislike',
                    'status' => 'success',
                    'data'   => [
                        'like_count' => $video->userReactions()->like()->count(),
                    ],
                ]);

            }
        } else {
            $reaction                 = new UserReaction();
            $reaction->user_id        = $userId;
            $reaction->video_id       = $video->id;
            $reaction->video_owner_id = $video->user_id;
            $reaction->is_like        = $isLike;
            $reaction->save();

            return response()->json([
                'remark' => $isLike == Status::YES ? 'like' : 'dislike',
                'status' => 'success',
                'data'   => [
                    'like_count' => $video->userReactions()->like()->count(),
                ],
            ]);
        }
    }

    public function subscribeChannel($id) {
        $user = User::active()->where('id', $id)->first();
        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found',
            ]);
        }

        if ($user->id == auth()->id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You cannot subscribe yourself',
            ]);
        }

        $existingFollower = $user->subscribers()->where('following_id', auth()->id())->first();

        if ($existingFollower) {

            $existingFollower->delete();
            $subscriberCount = $user->subscribers()->count();
            return response()->json([
                'remark'  => 'unsubscribe',
                'status'  => 'success',
                'message' => ['success', 'You have successfully unsubscribe the channel'],
                'data'    => [
                    'subscriber_count' => $subscriberCount,
                ],
            ]);

        } else {
            $follow               = new Subscriber();
            $follow->user_id      = $user->id;
            $follow->following_id = auth()->id();
            $follow->save();

            $userNotification            = new UserNotification();
            $userNotification->user_id   = $follow->user_id;
            $userNotification->title     = auth()->user()->fullname . ' subscribe you channel.';
            $userNotification->click_url = '#';
            $userNotification->save();

            $subscriberCount = $user->subscribers()->count();

            return response()->json([
                'remark'  => 'subscribed',
                'status'  => 'success',
                'message' => ['success', 'You have successfully subscribed the channel'],
                'data'    => [
                    'subscriber_count' => $subscriberCount,
                ],
            ]);
        }
    }

    public function history() {
        $pageTitle       = 'Watch History';
        $user            = auth()->user();
        $videosHistories = $user->watchHistories()->searchable(['video:title'])->with('video', 'video.videoFiles')->orderBy('last_view', 'desc')->whereHas('video.user', function ($query) {
            $query->active();
        })->paginate(getPaginate());
        return view('Template::user.watch_history', compact('pageTitle', 'videosHistories'));
    }

    public function removeHistory($id) {
        $watchHistory = WatchHistory::where('user_id', auth()->id())->findOrFail($id);
        $watchHistory->delete();

        $notify[] = ['success', 'History removed successfully'];
        return back()->withNotify($notify);
    }

    public function removeWatchLater($id) {
        $watchLater = WatchLater::where('user_id', auth()->id())->findOrFail($id);
        $watchLater->delete();

        $notify[] = ['success', 'Watch later video has been removed'];
        return back()->withNotify($notify);
    }

    public function removeAllHistory() {
        $user = auth()->user();
        $user->watchHistories()->delete();
        $notify[] = ['success', 'All history has been removed'];
        return back()->withNotify($notify);
    }

    public function removeAllWatchLater() {
        $user = auth()->user();
        $user->watchLaters()->delete();
        $notify[] = ['success', 'All watch later video has been removed'];
        return back()->withNotify($notify);
    }

    public function watchLater($id) {
        $video = Video::published()->whereHas('user', function ($query) {
            $query->active();
        })->find($id);

        if (!$video) {
            return response()->json([
                'remark'  => 'video_not_found',
                'status'  => 'error',
                'message' => ['error', 'The requested video could not be found'],
            ]);
        }

        $user               = auth()->user();
        $existingWatchLater = $user->watchLaters()->where('video_id', $video->id)->first();

        if ($existingWatchLater) {
            $existingWatchLater->delete();
            return response()->json([
                'remark' => 'watch_later_remove',
            ]);
        }

        $watchLater           = new WatchLater();
        $watchLater->user_id  = auth()->id();
        $watchLater->video_id = $video->id;
        $watchLater->save();
        return response()->json([
            'remark' => 'add_watch_later',
        ]);
    }

    public function earnings() {
        $pageTitle = 'Earnings';
        $user      = auth()->user();

        $totalEarnings      = $user->transactions()->whereIn('remark', ['ads_revenue', 'earn_from_video'])->sum('amount');
        $stockVideoEarnings = $user->transactions()->where('remark', 'earn_from_video')->sum('amount');
        $adsEarnings        = $user->transactions()->where('remark', 'ads_revenue')->sum('amount');
        $earnings           = Transaction::where('user_id', auth()->id())->with('user')->whereIn('remark', ['ads_revenue', 'earn_from_video'])
            ->take(5)
            ->latest()
            ->get();

        $playlistEarnings        = $user->transactions()->where('remark', 'earn_from_playlist')->sum('amount');
        $planEarnings            = $user->transactions()->where('remark', 'earn_from_plan')->sum('amount');
        $adminCommission         = $user->transactions()
            ->whereIn('remark', ['video_sell_charge', 'playlist_sell_charge', 'plan_sell_charge'])
            ->sum('amount');

        return view('Template::user.earning_history', compact('pageTitle', 'playlistEarnings', 'planEarnings', 'adminCommission', 'totalEarnings', 'stockVideoEarnings', 'earnings', 'adsEarnings'));
    }

    public function wallet() {
        $pageTitle = 'Wallet';
        $user      = auth()->user();
        $withdraws = Withdrawal::where('user_id', auth()->id())
            ->where('status', '!=', Status::PAYMENT_INITIATE)
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        return view('Template::user.wallet', compact('pageTitle', 'user', 'withdraws'));
    }

    public function earningChat(Request $request) {
        $diffInDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));

        $groupBy = $diffInDays > 30 ? 'months' : 'days';
        $format  = $diffInDays > 30 ? '%M-%Y' : '%d-%M-%Y';

        if ($groupBy == 'days') {
            $dates = $this->getAllDates($request->start_date, $request->end_date);
        } else {
            $dates = $this->getAllMonths($request->start_date, $request->end_date);
        }

        $totalEarnings = Transaction::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->where('user_id', auth()->id())
            ->whereIn('remark', ['ads_revenue', 'earn_from_video', 'earn_from_plan', 'earn_from_playlist'])
            ->groupBy('created_on')
            ->latest()
            ->get();

        $adsEarnings = Transaction::where('remark', 'ads_revenue')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->where('user_id', auth()->id())
            ->groupBy('created_on')
            ->latest()
            ->get();

        $stockVideoEarnings = Transaction::where('remark', 'earn_from_video')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->where('user_id', auth()->id())
            ->groupBy('created_on')
            ->latest()
            ->get();

        $playlistEarnings = Transaction::where('remark', 'earn_from_playlist')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->where('user_id', auth()->id())
            ->groupBy('created_on')
            ->latest()
            ->get();

        $planEarnings = Transaction::where('remark', 'earn_from_plan')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->where('user_id', auth()->id())
            ->groupBy('created_on')
            ->latest()
            ->get();

        $data = [];

        foreach ($dates as $date) {
            $data[] = [
                'created_on'          => showDateTime($date, 'd-M-y'),
                'total_earning'       => getAmount($totalEarnings->where('created_on', $date)->first()?->amount ?? 0),
                'stock_video_earning' => getAmount($stockVideoEarnings->where('created_on', $date)->first()?->amount ?? 0),
                'ads_earnings'        => getAmount($adsEarnings->where('created_on', $date)->first()?->amount ?? 0),
                'playlist_earnings'   => getAmount($playlistEarnings->where('created_on', $date)->first()?->amount ?? 0),
                'plan_earnings'       => getAmount($planEarnings->where('created_on', $date)->first()?->amount ?? 0),
            ];
        }

        $data = collect($data);

        // Monthly Deposit & Withdraw Report Graph
        $report['created_on'] = $data->pluck('created_on');
        $report['data']       = [
            [
                'name' => 'Total Earnings',
                'data' => $data->pluck('total_earning'),
            ],
            [
                'name' => 'Earnings From Stock Video',
                'data' => $data->pluck('stock_video_earning'),
            ],
            [
                'name' => 'Earnings From ads',
                'data' => $data->pluck('ads_earnings'),
            ],
        ];

        if (gs('is_playlist_sell')) {
            $report['data'][] = [
                'name' => 'Earnings From Playlist',
                'data' => $data->pluck('playlist_earnings'),
            ];
        }

        if (gs('is_monthly_subscription')) {
            $report['data'][] = [
                'name' => 'Earnings From Plans',
                'data' => $data->pluck('plan_earnings'),
            ];
        }

        return response()->json($report);
    }

    public function impressionChat(Request $request) {
        $diffInDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));

        $groupBy = $diffInDays > 30 ? 'months' : 'days';
        $format  = $diffInDays > 30 ? '%M-%Y' : '%d-%M-%Y';

        if ($groupBy == 'days') {
            $dates = $this->getAllDates($request->start_date, $request->end_date);
        } else {
            $dates = $this->getAllMonths($request->start_date, $request->end_date);
        }

        $totalImpression = Impression::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('user_id', auth()->id())
            ->selectRaw('SUM(views) AS views')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $data = [];

        foreach ($dates as $date) {
            $data[] = [
                'created_on'        => showDateTime($date, 'd-M-y'),
                'total_impressions' => getAmount($totalImpression->where('created_on', $date)->first()?->views ?? 0),
            ];
        }

        $data = collect($data);

        // Monthly Deposit & Withdraw Report Graph
        $report['created_on'] = $data->pluck('created_on');
        $report['data']       = [
            [
                'name' => 'Total Impressions',
                'data' => $data->pluck('total_impressions'),
            ],
        ];

        return response()->json($report);
    }

    public function listWatchLater() {
        $pageTitle   = 'Watch Later';
        $user        = auth()->user();
        $watchLaters = $user
            ->watchLaters()
            ->searchable(['video:title'])
            ->with('video')
            ->latest()
            ->paginate(getPaginate());

        return view('Template::user.watch_later', compact('pageTitle', 'watchLaters'));
    }

    public function notificationRead($id) {
        $notification          = UserNotification::where('user_id', auth()->id())->findOrFail($id);
        $notification->is_read = Status::YES;
        $notification->save();
        $url = $notification->click_url;
        if ($url == '#') {
            $url = url()->previous();
        }
        return redirect($url);
    }

    public function notificationAll() {
        $pageTitle     = 'Notifications';
        $notifications = UserNotification::where('user_id', auth()->id())->searchable(['title'])->latest()->paginate(getPaginate());
        return view('Template::user.notification', compact('notifications', 'pageTitle'));
    }

    public function notificationMarkAsReadAll() {
        $notifications = UserNotification::where('user_id', auth()->id())->get();
        foreach ($notifications as $notification) {
            $notification->is_read = Status::YES;
            $notification->save();
        }
        $notify[] = ['success' => 'Notification Marked as Read All'];
        return back()->with($notify);

    }

    public function notificationDelete($id) {
        $notification = UserNotification::where('user_id', auth()->id())->findOrFail($id);
        $notification->delete();
        $notify[] = ['success' => 'Notification  deleted successfully'];
        return back()->with($notify);
    }

    public function notificationDeleteAll() {

        UserNotification::where('user_id', auth()->id())->delete();

        $notify[] = ['success' => 'All notifications deleted successfully'];
        return back()->with($notify);

    }

    public function monetizationSetting() {
        $pageTitle = "Monetization Settings";
        $user      = auth()->user();

        $totalViews          = $user->videos()->public()->published()->sum('views');
        $totalSubscriber     = $user->subscribers()->count();
        $viewInPercent       = ($totalViews / gs('minimum_views')) * 100;
        $subscriberInPercent = ($totalSubscriber / gs('minimum_subscribe')) * 100;
        return view('Template::user.setting.monetization', compact('pageTitle', 'user', 'totalViews', 'totalSubscriber', 'viewInPercent', 'subscriberInPercent'));
    }

    public function applyForMonetization() {
        $user       = auth()->user();
        $totalViews = $user->videos()->public()->published()->sum('views');

        $totalSubscriber = $user->subscribers()->count();

        if ($totalSubscriber < gs('minimum_subscribe')) {
            $notify[] = ['error', 'You must have at least ' . gs('minimum_subscribe') . ' subscribers.'];
            return back()->withNotify($notify);
        }

        if ($totalViews < gs('minimum_views')) {
            $notify[] = ['error', 'You must have at least ' . gs('minimum_views') . ' views.'];
            return back()->withNotify($notify);
        }

        $user->monetization_status = Status::MONETIZATION_APPLYING;
        $user->save();
        $notify[] = ['success', 'Application submitted successfully.'];
        return back()->withNotify($notify);
    }

}
