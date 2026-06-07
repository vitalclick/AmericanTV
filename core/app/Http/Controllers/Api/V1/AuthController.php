<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\AdminNotification;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\Auth\LoginActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        abort_unless((bool) gs('registration'), 403, 'Registration is disabled.');

        $passwordRule = Password::min(6);
        if (gs('secure_password')) {
            $passwordRule = $passwordRule->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:60'],
            'lastname'  => ['required', 'string', 'max:60'],
            'email'     => ['required', 'string', 'email', 'max:160', Rule::unique('users', 'email')],
            'username'  => ['nullable', 'string', 'min:3', 'max:60', 'alpha_dash', Rule::unique('users', 'username')],
            'mobile'    => ['nullable', 'string', 'max:32'],
            'dial_code' => ['nullable', 'string', 'max:8'],
            'country'   => ['nullable', 'string', 'size:2'],
            'password'  => ['required', 'string', $passwordRule],
        ]);

        $user            = new User();
        $user->email     = strtolower($data['email']);
        $user->username  = $data['username'] ?? null;
        $user->firstname = $data['firstname'];
        $user->lastname  = $data['lastname'];
        $user->mobile    = $data['mobile'] ?? null;
        $user->dial_code = $data['dial_code'] ?? null;
        $user->password  = Hash::make($data['password']);
        $user->kv        = gs('kv') ? Status::NO : Status::YES;
        $user->ev        = gs('ev') ? Status::NO : Status::YES;
        $user->sv        = gs('sv') ? Status::NO : Status::YES;
        $user->ts        = Status::DISABLE;
        $user->tv        = Status::ENABLE;
        $user->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = 'New member registered from mobile app';
        $adminNotification->click_url = urlPath('admin.users.detail', $user->id);
        $adminNotification->save();

        LoginActivityLogger::record($user);

        return $this->authResponse($user, $request->input('device_name'), status: 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'identifier'  => ['required', 'string'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:64'],
        ]);

        $field = filter_var($data['identifier'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user  = User::where($field, $data['identifier'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['These credentials do not match our records.'],
            ]);
        }

        if ((int) $user->status === Status::USER_BAN) {
            abort(403, 'This account has been suspended.');
        }

        LoginActivityLogger::record($user);

        return $this->authResponse($user, $data['device_name'] ?? null);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoke only the calling token; other devices keep their sessions.
        $request->user()->currentAccessToken()?->delete();
        return response()->json([], 204);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user  = $request->user();
        $label = $request->user()->currentAccessToken()?->name ?? 'Mobile';

        $request->user()->currentAccessToken()?->delete();
        return $this->authResponse($user, $label);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Idempotent shape — never disclose whether the email exists.
        $user = User::where('email', strtolower($request->input('email')))->first();
        if (!$user) {
            return response()->json([], 204);
        }

        PasswordReset::where('email', $user->email)->delete();

        $code              = verificationCode(6);
        $reset             = new PasswordReset();
        $reset->email      = $user->email;
        $reset->token      = $code;
        $reset->created_at = Carbon::now();
        $reset->save();

        $browser = osBrowser();
        $ip      = getIpInfo();

        notify($user, 'PASS_RESET_CODE', [
            'code'             => $code,
            'operating_system' => @$browser['os_platform'],
            'browser'          => @$browser['browser'],
            'ip'               => @$ip['ip'],
            'time'             => @$ip['time'],
        ], ['email']);

        return response()->json([], 204);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code'  => ['required', 'string'],
        ]);

        $code  = str_replace(' ', '', $data['code']);
        $reset = PasswordReset::where('email', $data['email'])->where('token', $code)->first();

        if (!$reset) {
            throw ValidationException::withMessages([
                'code' => ['Verification code does not match.'],
            ]);
        }

        // Replace the one-shot code with a longer reset token the next call uses.
        $resetToken   = Str::random(64);
        $reset->token = $resetToken;
        $reset->save();

        return response()->json(['reset_token' => $resetToken]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $passwordRule = Password::min(6);
        if (gs('secure_password')) {
            $passwordRule = $passwordRule->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $data = $request->validate([
            'reset_token' => ['required', 'string', 'size:64'],
            'password'    => ['required', 'string', $passwordRule],
        ]);

        $reset = PasswordReset::where('token', $data['reset_token'])->first();
        if (!$reset || Carbon::parse($reset->created_at)->lt(now()->subHour())) {
            throw ValidationException::withMessages([
                'reset_token' => ['Reset token is invalid or has expired.'],
            ]);
        }

        $user = User::where('email', $reset->email)->first();
        if (!$user) {
            return response()->json([], 204);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        PasswordReset::where('email', $reset->email)->delete();

        // Revoke all outstanding tokens so a stolen device can't keep a session
        // after a password reset.
        $user->tokens()->delete();

        return response()->json([], 204);
    }

    public function socialLogin(string $provider): JsonResponse
    {
        // TODO(phase 1): exchange Apple/Google ID token for Sanctum token.
        // - Apple: verify JWS against keys at https://appleid.apple.com/auth/keys
        // - Google: verify ID token via Firebase Auth or google-api-php-client.
        // - Look up existing user by `social_id`/`email`; create one if absent.
        return response()->json([
            'message' => "Social login ({$provider}) is not implemented yet.",
        ], 501);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        return $this->stubVerification($request, field: 'ev', label: 'email');
    }

    public function verifyMobile(Request $request): JsonResponse
    {
        return $this->stubVerification($request, field: 'sv', label: 'mobile');
    }

    public function verify2fa(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Two-factor verification is not implemented yet.',
        ], 501);
    }

    private function stubVerification(Request $request, string $field, string $label): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();
        if (! hash_equals((string) $user->ver_code, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => ["Invalid {$label} verification code."],
            ]);
        }

        $user->{$field}     = Status::VERIFIED;
        $user->ver_code     = null;
        $user->save();

        return response()->json([], 204);
    }

    private function authResponse(User $user, ?string $deviceName, int $status = 200): JsonResponse
    {
        $name  = $deviceName ?: 'Mobile';
        $token = $user->createToken($name);

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user'       => (new UserResource($user))->toArray(request()),
        ], $status);
    }
}
