<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\Intended;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuthorizationController extends Controller {
    protected function checkCodeValidity($user, $addMin = 2) {
        if (!$user->ver_code_send_at) {
            return false;
        }
        if ($user->ver_code_send_at->addMinutes($addMin) < Carbon::now()) {
            return false;
        }
        return true;
    }

    public function authorizeForm() {

        $pageTitle = 'Verification';
        $user      = auth()->user();
        if (!$user->status) {
            $pageTitle = 'Banned';
            return view('Template::user.auth.authorization.ban', compact('user', 'pageTitle'));
        } else if (!$user->tv) {
            $pageTitle = '2FA Verification';
            return view('Template::user.auth.authorization.2fa', compact('user', 'pageTitle'));
        } else {
            return view('Template::user.auth.authorization.form', compact('user', 'pageTitle'));
        }
    }

    public function sendVerifyCode($type) {

        $user = auth()->user();

        if ($user->ev && $type == 'email') {
            return response()->json([
                'status' => 'error',
                'notify' => 'The Email already verified.',
            ]);

        } else if ($user->sv && $type == 'sms') {
            return response()->json([
                'status' => 'error',
                'notify' => 'The Mobile already verified.',
            ]);

        }

        if ($this->checkCodeValidity($user)) {
            $targetTime = $user->ver_code_send_at->addMinutes(2)->timestamp;
            $delay      = $targetTime - time();

            return response()->json([
                'status' => 'error',
                'notify' => 'Please try after ' . $delay . ' seconds',
            ]);
        }

        $user->ver_code         = verificationCode(6);
        $user->ver_code_send_at = Carbon::now();
        $user->save();

        if ($type == 'email') {
            $type           = 'email';
            $notifyTemplate = 'EVER_CODE';
        } else {
            $type           = 'sms';
            $notifyTemplate = 'SVER_CODE';
        }

        notify($user, $notifyTemplate, [
            'code' => $user->ver_code,
        ], [$type]);

        return response()->json([
            'status' => 'success',
            'notify' => 'Verification code sent successfully',
        ]);
    }

    public function emailVerification(Request $request) {
        $request->validate([
            'code' => 'required',
        ]);

        $user = auth()->user();

        if ($user->ver_code == $request->code) {
            $user->ev               = Status::VERIFIED;
            $user->ver_code         = null;
            $user->ver_code_send_at = null;
            $user->save();

            return response()->json([
                'status' => 'success',
                'notify' => 'Email successfully verified',
            ]);
        }
        return response()->json([
            'status' => 'error',
            'notify' => 'Verification code didn\'t match!',
        ]);
    }

    public function mobileVerification(Request $request) {
        $request->validate([
            'code' => 'required',
        ]);

        $user = auth()->user();
        if ($user->ver_code == $request->code) {
            $user->sv               = Status::VERIFIED;
            $user->ver_code         = null;
            $user->ver_code_send_at = null;
            $user->save();

            return response()->json([
                'status' => 'success',
                'notify' => 'mobile successfully verified',
            ]);
        }
        return response()->json([
            'status' => 'error',
            'notify' => 'Verification code didn\'t match!',
        ]);
    }

    public function g2faVerification(Request $request) {
        $user = auth()->user();
        $request->validate([
            'code' => 'required',
        ]);
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $redirection = Intended::getRedirection();
            return $redirection ? $redirection : to_route('user.home');
        } else {
            $notify[] = ['error', 'Wrong verification code'];
            return back()->withNotify($notify);
        }
    }
}
