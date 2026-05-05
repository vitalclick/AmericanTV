<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\GoogleAuthenticator;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;

class SettingController extends Controller {
    public function accountSetting() {
        $pageTitle = "Account Information";
        $user      = auth()->user();
        return view('Template::user.setting.account', compact('pageTitle', 'user'));
    }

    public function updateAccount(Request $request) {
        $request->validate([
            'channel_name'        => 'required|string|max:255',
            'channel_description' => 'required|string',
            'image'               => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'cover_image'         => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        $user = auth()->user();
        $slug = slug($request->channel_name);

        $user->channel_name        = $request->channel_name;
        $user->slug                = $slug . "-" . $user->id;
        $user->channel_description = $request->channel_description;

        if ($request->hasFile('image')) {
            $old = $user->image;
            try {
                $user->image = fileUploader($request->image, getFilePath('userProfile'), getFileSize('userProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload the logo'];
                return back()->withNotify($notify);
            }
        }

        if ($request->hasFile('cover_image')) {
            $old = $user->cover_image;
            try {
                $user->cover_image = fileUploader($request->cover_image, getFilePath('cover'), getFileSize('cover'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload the logo'];
                return back()->withNotify($notify);
            }
        }

        $user->save();
        $notify[] = ['success', 'Account updated successfully.'];
        return back()->withNotify($notify);
    }

    public function security() {
        $pageTitle = "Security Settings";
        $user      = auth()->user();

        return view('Template::user.setting.security', compact('pageTitle', 'user'));
    }

    public function show2faForm() {
        $ga        = new GoogleAuthenticator();
        $user      = auth()->user();
        $secret    = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . gs('site_name'), $secret);
        $pageTitle = 'Two Factor Authentication';
        return view('Template::user.setting.twofactor', compact('pageTitle', 'secret', 'qrCodeUrl', 'user'));
    }

    public function create2fa(Request $request) {
        $user = auth()->user();
        $request->validate([
            'key'  => 'required',
            'code' => 'required',
        ]);
        $response = verifyG2fa($user, $request->code, $request->key);
        if ($response) {
            $user->tsc = $request->key;
            $user->ts  = Status::ENABLE;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator activated successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong verification code'];
            return back()->withNotify($notify);
        }
    }

    public function disable2fa(Request $request) {
        $request->validate([
            'code' => 'required',
        ]);

        $user     = auth()->user();
        $response = verifyG2fa($user, $request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts  = Status::DISABLE;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator deactivated successfully'];
        } else {
            $notify[] = ['error', 'Wrong verification code'];
        }
        return back()->withNotify($notify);
    }

}