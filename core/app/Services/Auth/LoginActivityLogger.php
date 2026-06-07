<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserLogin;

/**
 * Persists a UserLogin row mirroring the web flow's behavior in
 * LoginController::authenticated() and RegisterController::create(). Lifted
 * verbatim so admin reports look identical regardless of channel.
 */
class LoginActivityLogger
{
    public static function record(User $user): UserLogin
    {
        $ip    = getRealIP();
        $exist = UserLogin::where('user_ip', $ip)->first();

        $userLogin = new UserLogin();

        if ($exist) {
            $userLogin->longitude    = $exist->longitude;
            $userLogin->latitude     = $exist->latitude;
            $userLogin->city         = $exist->city;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country      = $exist->country;
        } else {
            $info                    = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude    = @implode(',', $info['long']);
            $userLogin->latitude     = @implode(',', $info['lat']);
            $userLogin->city         = @implode(',', $info['city']);
            $userLogin->country_code = @implode(',', $info['code']);
            $userLogin->country      = @implode(',', $info['country']);
        }

        $userAgent = osBrowser();

        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;
        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os      = @$userAgent['os_platform'];
        $userLogin->save();

        return $userLogin;
    }
}
