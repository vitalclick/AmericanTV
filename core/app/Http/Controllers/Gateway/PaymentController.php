<?php

namespace App\Http\Controllers\Gateway;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Models\AdminNotification;
use App\Models\Advertisement;
use App\Models\Campaign;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\Plan;
use App\Models\Playlist;
use App\Models\PurchasedPlan;
use App\Models\PurchasedPlaylist;
use App\Models\PurchasedVideo;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Video;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function deposit($id = null, $monetization = null)
    {
        if ($monetization && !gs('monetization_status')) {
            abort(404);
        }

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();

        $advertisement = null;
        if ($id) {
            $advertisement = Advertisement::where('user_id', auth()->id())->pending()->findOrFail($id);
        }

        $pageTitle = 'Payment Methods';

        return view('Template::user.payment.deposit', compact('gatewayCurrency', 'pageTitle', 'monetization', 'advertisement'));
    }

    public function depositInsert(Request $request)
    {

        $request->validate([
            'amount'       => 'required|numeric|gt:0',
            'gateway'      => 'required',
            'currency'     => 'required',
            'video_id'     => 'sometimes|integer',
            'playlist_id'  => 'sometimes|integer',
            'plan_id'      => 'sometimes|integer',
            'monetization' => 'nullable|in:1',

        ]);


        if ($request->monetization && !gs('monetization_status')) {
            abort(404);
        }

        if ($request->playlist_id && !gs('is_playlist_sell')) {
            abort(404);
        }

        if ($request->plan_id && !gs('is_monthly_subscription')) {
            abort(404);
        }

        $video         = null;
        $playlist      = null;
        $plan          = null;
        $user = auth()->user();

        if ($request->video_id) {
            $video  = Video::published()->stock()->findOrFail($request->video_id);
            $amount = $video->price;
        } else if ($request->playlist_id) {
            $playlist = Playlist::public()->playlistForSell()->findOrFail($request->playlist_id);
            $amount   = $playlist->price;
        } else if ($request->plan_id) {
            $plan   = Plan::active()->withVideosOrPlaylists()->findOrFail($request->plan_id);
            $amount = $plan->price;
        } elseif ($request->advertisement_id) {
            $advertisement = Advertisement::where('user_id', $user->id)->pending()->findOrFail($request->advertisement_id);
            $amount        = $advertisement->total_amount;
        } else if ($request->campaign_id) {
            $campaign = Campaign::where('user_id', $user->id)
                ->where('payment_status', Status::PAYMENT_PENDING)
                ->findOrFail($request->campaign_id);

            $amount = getAmount(  $campaign->hold_amount > 0 ?  $campaign->hold_amount : $campaign->total_amount ) ;

        } else if ($request->monetization) {
            $amount = gs('monetization_amount');
        } else {
            $amount = $request->amount;
        }

        $gate = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->where('method_code', $request->gateway)->where('currency', $request->currency)->first();
        if (!$gate) {
            $notify[] = ['error', 'Invalid gateway'];
            return back()->withNotify($notify);
        }

        if ($gate->min_amount > $amount || $gate->max_amount < $amount) {
            $notify[] = ['error', 'Please follow payment limit'];
            return back()->withNotify($notify);
        }

        $charge      = $gate->fixed_charge + ($amount * $gate->percent_charge / 100);
        $payable     = $amount + $charge;
        $finalAmount = $payable * $gate->rate;

        $data                  = new Deposit();
        $data->user_id         = $user->id;
        $data->method_code     = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount          = $amount;
        $data->charge          = $charge;
        $data->rate            = $gate->rate;
        $data->final_amount    = $finalAmount;
        $data->btc_amount      = 0;
        $data->btc_wallet      = "";
        $data->trx             = getTrx();

        if ($video) {
            $data->video_id    = $video->id;
            $data->success_url = route('video.play', [$video->id, $video->slug]);
            $data->failed_url  = route('video.play', [$video->id, $video->slug]);
        } else if ($playlist) {
            $data->playlist_id = $playlist->id;
            $data->success_url = route('preview.playlist.videos', [@$playlist->slug, @$playlist->user->slug]);
            $data->failed_url  = route('preview.playlist.videos', [@$playlist->slug, @$playlist->user->slug]);
        } else if ($plan) {
            $data->plan_id     = $plan->id;
            $data->success_url = route('preview.monthly.plan', @$plan->user->slug);
            $data->failed_url  = route('preview.monthly.plan', @$plan->user->slug);
        } else if ($request->monetization) {
            $data->is_monetization = Status::YES;
            $data->success_url     = route('user.monetization');
            $data->failed_url      = route('user.monetization');
        } else if ($request->campaign_id) {
            $data->campaign_id     = $campaign->id;
            $data->success_url      = route('user.advertiser.ad.create', $campaign->slug);
            $data->failed_url       = route('user.advertiser.campaign.index');
        } else if ($advertisement) {
            $data->advertisement_id = $advertisement->id;
            $data->success_url      = route('user.advertiser.ad.list');
            $data->failed_url       = route('user.advertiser.ad.list');
        }

        $data->save();
        session()->put('Track', $data->trx);
        return to_route('user.deposit.confirm');
    }

    public function depositConfirm()
    {
        $track   = session()->get('Track');
        $deposit = Deposit::where('trx', $track)->where('status', Status::PAYMENT_INITIATE)->orderBy('id', 'DESC')->with('gateway')->firstOrFail();

        if ($deposit->method_code >= 1000) {
            return to_route('user.deposit.manual.confirm');
        }

        $dirName = $deposit->gateway->alias;
        $new     = __NAMESPACE__ . '\\' . $dirName . '\\ProcessController';

        $data = $new::process($deposit);
        $data = json_decode($data);

        if (isset($data->error)) {
            $notify[] = ['error', $data->message];
            return back()->withNotify($notify);
        }
        if (isset($data->redirect)) {
            return redirect($data->redirect_url);
        }

        // for Stripe V3
        if (@$data->session) {
            $deposit->btc_wallet = $data->session->id;
            $deposit->save();
        }


        $pageTitle = 'Payment Confirm';
        return view("Template::$data->view", compact('data', 'pageTitle', 'deposit'));
    }

    public static function userDataUpdate($deposit, $isManual = null)
    {
        if ($deposit->status == Status::PAYMENT_INITIATE || $deposit->status == Status::PAYMENT_PENDING) {
            $deposit->status = Status::PAYMENT_SUCCESS;
            $deposit->save();

            $user = User::find($deposit->user_id);
            $user->balance += $deposit->amount;
            $user->save();

            $methodName = $deposit->methodName();

            $transaction               = new Transaction();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $deposit->amount;
            $transaction->post_balance = $user->balance;
            $transaction->charge       = $deposit->charge;
            $transaction->trx_type     = '+';
            $transaction->details      = 'Payment Via ' . $methodName;
            $transaction->trx          = $deposit->trx;
            $transaction->remark       = 'deposit';
            $transaction->save();

            if (!$isManual) {
                self::createAdminNotification($user->id, 'Payment successful via ' . $methodName);
            }

            if ($deposit->video_id) {
                $videoOwner = User::find($deposit->video->user_id);
                $videoOwner->balance += $deposit->amount;
                $videoOwner->save();

                $user->balance -= $deposit->amount;
                $user->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->video_id     = $deposit->video_id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = $deposit->charge;
                $transaction->trx_type     = '-';
                $transaction->details      = 'Payment for video purchase via ' . $methodName;
                $transaction->trx          = $deposit->trx;
                $transaction->remark       = 'purchased_video';
                $transaction->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $videoOwner->id;
                $transaction->video_id     = $deposit->video_id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $videoOwner->balance;
                $transaction->charge       = 0;
                $transaction->trx_type     = '+';
                $transaction->details      = $deposit->user->fullname . " purchased your video.";
                $transaction->trx          = $deposit->trx;
                $transaction->remark       = 'earn_from_video';
                $transaction->save();

                $purchased           = new PurchasedVideo();
                $purchased->user_id  = $deposit->user_id;
                $purchased->video_id = $deposit->video_id;
                $purchased->owner_id = $deposit->video->user_id;
                $purchased->trx      = $deposit->trx;
                $purchased->amount   = $deposit->amount;
                $purchased->save();

                if (gs('video_sell_charge') > 0) {
                    $commission = ($deposit->amount * gs('video_sell_charge')) / 100;
                    $videoOwner->balance -= $commission;
                    $videoOwner->save();

                    $transaction               = new Transaction();
                    $transaction->user_id      = $videoOwner->id;
                    $transaction->video_id     = $deposit->video_id;
                    $transaction->amount       = $commission;
                    $transaction->post_balance = $videoOwner->balance;
                    $transaction->charge       = 0;
                    $transaction->trx_type     = '-';
                    $transaction->details      = "Platform commission for video sale";
                    $transaction->trx          = $deposit->trx;
                    $transaction->remark       = 'video_sale_commission';
                    $transaction->save();
                }

                self::createAdminNotification($user->id, 'Payment successful via ' . $methodName);

                self::createUserNotification(
                    $deposit->video->user_id,
                    'New Purchase: ' . $deposit->video->title,
                    'video.play',
                    [$deposit->video->id, $deposit->video->slug]
                );

                self::sendPurchaseNotification($user, $deposit, $methodName, 'video');

                self::sendOwnerNotification($videoOwner, $deposit, 'video');
            } else if ($deposit->playlist_id) {
                $playlistOwner = User::find($deposit->playlist->user_id);
                $playlistOwner->balance += $deposit->amount;
                $playlistOwner->save();

                $user->balance -= $deposit->amount;
                $user->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->playlist_id  = $deposit->playlist_id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = $deposit->charge;
                $transaction->trx_type     = '-';
                $transaction->details      = 'Payment for playlist purchase via ' . $methodName;
                $transaction->trx          = $deposit->trx;
                $transaction->remark       = 'purchased_playlist';
                $transaction->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $playlistOwner->id;
                $transaction->playlist_id  = $deposit->playlist_id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $playlistOwner->balance;
                $transaction->charge       = 0;
                $transaction->trx_type     = '+';
                $transaction->details      = $deposit->user->fullname . " purchased your playlist.";
                $transaction->trx          = $deposit->trx;
                $transaction->remark       = 'earn_from_playlist';
                $transaction->save();

                $purchased              = new PurchasedPlaylist();
                $purchased->user_id     = $deposit->user_id;
                $purchased->playlist_id = $deposit->playlist_id;
                $purchased->owner_id    = $deposit->playlist->user_id;
                $purchased->trx         = $deposit->trx;
                $purchased->amount      = $deposit->amount;
                $purchased->save();

                if (gs('playlist_sell_charge') > 0) {
                    $commission = ($deposit->amount * gs('playlist_sell_charge')) / 100;
                    $playlistOwner->balance -= $commission;
                    $playlistOwner->save();

                    $transaction               = new Transaction();
                    $transaction->user_id      = $playlistOwner->id;
                    $transaction->playlist_id  = $deposit->playlist_id;
                    $transaction->amount       = $commission;
                    $transaction->post_balance = $playlistOwner->balance;
                    $transaction->charge       = 0;
                    $transaction->trx_type     = '-';
                    $transaction->details      = "Platform commission for playlist sale";
                    $transaction->trx          = $deposit->trx;
                    $transaction->remark       = 'playlist_sale_commission';
                    $transaction->save();
                }

                self::createAdminNotification($user->id, 'Payment successful via ' . $methodName);

                self::createUserNotification(
                    $deposit->playlist->user_id,
                    'New playlist purchase: ' . $deposit->playlist->title,
                    'user.playlist.videos',
                    $deposit->playlist->slug
                );

                self::sendPurchaseNotification($user, $deposit, $methodName, 'playlist');

                self::sendOwnerNotification($playlistOwner, $deposit, 'playlist');
            } else if ($deposit->plan_id) {
                $planOwner = User::find($deposit->plan->user_id);
                $planOwner->balance += $deposit->amount;
                $planOwner->save();

                $user->balance -= $deposit->amount;
                $user->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->plan_id      = $deposit->plan_id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = $deposit->charge;
                $transaction->trx_type     = '-';
                $transaction->details      = 'Payment for plan purchase via ' . $methodName;
                $transaction->trx          = $deposit->trx;
                $transaction->remark       = 'purchased_plan';
                $transaction->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $planOwner->id;
                $transaction->plan_id      = $deposit->plan_id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $planOwner->balance;
                $transaction->charge       = 0;
                $transaction->trx_type     = '+';
                $transaction->details      = $deposit->user->fullname . " purchased your plan.";
                $transaction->trx          = $deposit->trx;
                $transaction->remark       = 'earn_from_plan';
                $transaction->save();

                $purchased               = new PurchasedPlan();
                $purchased->user_id      = $deposit->user_id;
                $purchased->plan_id      = $deposit->plan_id;
                $purchased->owner_id     = $deposit->plan->user_id;
                $purchased->trx          = $deposit->trx;
                $purchased->amount       = $deposit->amount;
                $purchased->expired_date = now()->addDays(30);
                $purchased->save();

                if (gs('plan_sell_charge') > 0) {
                    $commission = ($deposit->amount * gs('plan_sell_charge')) / 100;
                    $planOwner->balance -= $commission;
                    $planOwner->save();

                    $transaction               = new Transaction();
                    $transaction->user_id      = $planOwner->id;
                    $transaction->plan_id      = $deposit->plan_id;
                    $transaction->amount       = $commission;
                    $transaction->post_balance = $planOwner->balance;
                    $transaction->charge       = 0;
                    $transaction->trx_type     = '-';
                    $transaction->details      = "Platform commission for plan sale";
                    $transaction->trx          = $deposit->trx;
                    $transaction->remark       = 'plan_sale_commission';
                    $transaction->save();
                }

                self::createAdminNotification($user->id, 'Payment successful via ' . $methodName);

                self::createUserNotification(
                    $deposit->plan->user_id,
                    'New Plan Purchase: ' . $deposit->plan->name,
                    'user.manage.plan.details',
                    $deposit->plan->slug
                );

                self::sendPurchaseNotification($user, $deposit, $methodName, 'plan');

                self::sendOwnerNotification($planOwner, $deposit, 'plan');
            } else if ($deposit->advertisement_id) {

                $user->balance -= $deposit->amount;
                $user->save();
        

                $advertisement                 = $deposit->advertisement;
                $advertisement->status         =   Status::RUNNING;
                $advertisement->payment_status = Status::PAYMENT_SUCCESS;
                $advertisement->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = $deposit->charge;
                $transaction->trx_type     = '-';
                $transaction->details      = 'Payment for advertisement published via ' . $methodName;
                $transaction->trx          = $deposit->trx;
                $transaction->remark       = 'advertisements_published';
                $transaction->save();

                self::createAdminNotification($user->id, 'Payment successful via ' . $methodName);

                self::createUserNotification(
                    $deposit->user_id,
                    $deposit->advertisement->title . " has been published.",
                    'user.advertiser.ad.list'
                );

                self::sendPurchaseNotification($user, $deposit, $methodName, 'advertisement');
            } else if ($deposit->is_monetization) {
                $user->balance -= $deposit->amount;
                $user->monetization_status = Status::MONETIZATION_APPROVED;
                $user->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = $deposit->charge;
                $transaction->trx_type     = '-';
                $transaction->details      = 'Payment for monetization purchase via ' . $methodName;
                $transaction->trx          = $deposit->trx;
                $transaction->remark       = 'purchased_monetization';
                $transaction->save();

                self::createAdminNotification($user->id, 'Payment successful via ' . $methodName);

                self::createUserNotification(
                    $deposit->user_id,
                    "Payment successfully captured.",
                    'user.monetization'
                );

                self::sendPurchaseNotification($user, $deposit, $methodName, 'monetization');
            }
            else if ($deposit->campaign_id) {

                $user->balance -= $deposit->amount;
                $user->save();
                
                $campaign = $deposit->campaign;

            
                $campaign->status = Status::RUNNING;
                $campaign->payment_status = Status::PAYMENT_SUCCESS; 
                $campaign->available_amount += $deposit->amount;
                $campaign->save();

                if($campaign->hold_amount <= 0){
                     self::createUserNotification(
                    $deposit->user_id,
                    $campaign->title . " has been published.",
                    'user.advertiser.campaign.index'
                );


                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = $deposit->charge;
                $transaction->trx_type     = '-';
                $transaction->details      = 'Payment for campaign published via ' . $methodName;
                $transaction->trx          = $deposit->trx;
                $transaction->remark       = 'campaign_published';
                $transaction->save();
                
                self::sendPurchaseNotification($user, $deposit, $methodName, 'campaign');

                
                }else{
                    self::createUserNotification(
                    $deposit->user_id,
                    $campaign->title . " has been updated.",
                    'user.advertiser.campaign.index'
                );

                $campaign->total_amount +=$campaign->hold_amount;
                $campaign->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->amount       = $deposit->amount;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = $deposit->charge;
                $transaction->trx_type     = '-';
                $transaction->details      = 'Payment for campaign updated via ' . $methodName;
                $transaction->trx          = $deposit->trx;
                $transaction->remark       = 'campaign_updated';
                $transaction->save();

                self::sendPurchaseNotification($user, $deposit, $methodName, 'campaign_updated');
                }

                $campaign->hold_amount = 0;
                $campaign->save();
                
        

                self::createAdminNotification($user->id, 'Payment successful via ' . $methodName);


            }

            notify($user, $isManual ? 'DEPOSIT_APPROVE' : 'DEPOSIT_COMPLETE', [
                'method_name'     => $methodName,
                'method_currency' => $deposit->method_currency,
                'method_amount'   => showAmount($deposit->final_amount, currencyFormat: false),
                'amount'          => showAmount($deposit->amount, currencyFormat: false),
                'charge'          => showAmount($deposit->charge, currencyFormat: false),
                'rate'            => showAmount($deposit->rate, currencyFormat: false),
                'trx'             => $deposit->trx,
                'post_balance'    => showAmount($user->balance),
            ]);
        }
    }

    private static function createAdminNotification($userId, $title)
    {
        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $userId;
        $adminNotification->title     = $title;
        $adminNotification->click_url = urlPath('admin.deposit.successful');
        $adminNotification->save();
    }

    private static function createUserNotification($userId, $title, $route, $params = null)
    {
        $userNotification            = new UserNotification();
        $userNotification->user_id   = $userId;
        $userNotification->title     = $title;
        $userNotification->click_url = urlPath($route, $params);
        $userNotification->save();
    }

    private static function sendPurchaseNotification($user, $deposit, $methodName, $type)
    {
        $notificationType = match ($type) {
            'video' => 'PURCHASED_VIDEO',
            'playlist' => 'PURCHASED_PLAYLIST',
            'plan' => 'PURCHASED_PLAN',
            'campaign' => 'PUBLISHED_CAMPAIGN',
            'campaign_updated' => 'UPDATE_CAMPAIGN',
            'advertisement' => 'PUBLISHED_ADVERTISEMENT',
            'monetization' => 'PURCHASED_MONETIZATION',
        };

        $data = [
            'method_name'     => $methodName,
            'method_currency' => $deposit->method_currency,
            'method_amount'   => showAmount($deposit->final_amount, currencyFormat: false),
            'amount'          => showAmount($deposit->amount, currencyFormat: false),
            'charge'          => showAmount($deposit->charge, currencyFormat: false),
            'rate'            => showAmount($deposit->rate, currencyFormat: false),
            'trx'             => $deposit->trx,
        ];

        if ($type === 'video') {
            $data['title']        = $deposit->video->title;
            $data['post_balance'] = showAmount($user->balance);
        } else if ($type === 'playlist') {
            $data['title']        = $deposit->playlist->title;
            $data['post_balance'] = showAmount($user->balance);
        } else if ($type === 'plan') {
            $data['plan_name']    = $deposit->plan->name;
            $data['post_balance'] = showAmount($user->balance);
        } else if ($type === 'campaign' || $type === 'campaign_updated') {
            $data['title']        = $deposit->campaign->title;
            $data['post_balance'] = showAmount($user->balance);
        }else if ($type === 'advertisement') {
            $data['title']        = $deposit->advertisement->title;
            $data['post_balance'] = showAmount($user->balance);
        } else if ($type === 'monetization') {
            $data['post_balance'] = showAmount($user->balance);
        }

        notify($user, $notificationType, $data);
    }

    private static function sendOwnerNotification($owner, $deposit, $type)
    {
        $notificationType = match ($type) {
            'video' => 'NEW_VIDEO_SELL',
            'playlist' => 'NEW_PLAYLIST_SELL',
            'plan' => 'NEW_PLAN_SELL',
        };

        $data = [
            'amount'       => showAmount($deposit->amount, currencyFormat: false),
            'rate'         => showAmount($deposit->rate, currencyFormat: false),
            'trx'          => $deposit->trx,
            'post_balance' => showAmount($owner->balance),
        ];

        if ($type === 'video') {
            $data['title'] = $deposit->video->title;
        } else if ($type === 'playlist') {
            $data['title']           = $deposit->playlist->title;
            $data['method_currency'] = $deposit->method_currency;
        } else if ($type === 'plan') {
            $data['plan_name']       = $deposit->plan->name;
            $data['method_currency'] = $deposit->method_currency;
        }

        notify($owner, $notificationType, $data);
    }

    public function manualDepositConfirm()
    {
        $track = session()->get('Track');
        $data  = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        if ($data->method_code > 999) {
            $pageTitle = 'Confirm Payment';
            $method    = $data->gatewayCurrency();
            $gateway   = $method->method;
            return view('Template::user.payment.manual', compact('data', 'pageTitle', 'method', 'gateway'));
        }
        abort(404);
    }

    public function manualDepositUpdate(Request $request)
    {
        $track = session()->get('Track');
        $data  = Deposit::with('gateway')->where('status', Status::PAYMENT_INITIATE)->where('trx', $track)->first();
        abort_if(!$data, 404);
        $gatewayCurrency = $data->gatewayCurrency();
        $gateway         = $gatewayCurrency->method;
        $formData        = $gateway->form->form_data;

        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $userData = $formProcessor->processFormData($request, $formData);

        $data->detail = $userData;
        $data->status = Status::PAYMENT_PENDING;
        $data->save();


        if ($data->is_monetization) {
            $user = $data->user;
            $user->monetization_status = Status::MONETIZATION_APPLYING;
            $user->save();
        }

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $data->user->id;
        $adminNotification->title     = 'Payment request from ' . $data->user->username;
        $adminNotification->click_url = urlPath('admin.deposit.details', $data->id);
        $adminNotification->save();

        notify($data->user, 'DEPOSIT_REQUEST', [
            'method_name'     => $data->gatewayCurrency()->name,
            'method_currency' => $data->method_currency,
            'method_amount'   => showAmount($data->final_amount, currencyFormat: false),
            'amount'          => showAmount($data->amount, currencyFormat: false),
            'charge'          => showAmount($data->charge, currencyFormat: false),
            'rate'            => showAmount($data->rate, currencyFormat: false),
            'trx'             => $data->trx,
        ]);

        $notify[] = ['success', 'You have payment request has been taken'];
        return redirect($data->success_url)->withNotify($notify);
    }
}
