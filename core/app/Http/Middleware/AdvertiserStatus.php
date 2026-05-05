<?php

namespace App\Http\Middleware;

use App\Constants\Status;
use Closure;
use Auth;

class AdvertiserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
    
        if (Auth::check()) {
            $user = auth()->user();
            if ($user->advertiser_status == Status::ADVERTISER_APPROVED) {
                return $next($request);
            } else {
                if ($request->is('api/*')) {
                    $notify[] = 'You need to verify your account first.';
                    return response()->json([
                        'remark'=>'unverified',
                        'status'=>'error',
                        'message'=>['error'=>$notify],
                        'data'=>[
                            'user'=>$user
                        ],
                    ]);
                }else{
                    
                    if ($user->advertiser_status == Status::ADVERTISER_PENDING) {
                        $notify[] = ['warning','Your documents is under review. Please wait for admin approval'];
                        return to_route('user.advertiser.home')->withNotify($notify);
                    }else{
                        $notify[] = ['error','You are not verified. For being verified, please provide these information'];
                        return to_route('user.advertiser.home')->withNotify($notify);

                    }
                    
                }
            }
        }
        abort(403);
    }
}
