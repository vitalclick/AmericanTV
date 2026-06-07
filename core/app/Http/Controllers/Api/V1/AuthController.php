<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\AdminNotification;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\Auth\LoginActivityLogger;
use App\Services\Auth\SocialIdentity;
use App\Services\Auth\SocialIdentityVerifier;
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

    public function socialLogin(Request $request, string $provider, SocialIdentityVerifier $verifier): JsonResponse
    {
        abort_unless(in_array($provider, ['apple', 'google'], true), 404);

        $data = $request->validate([
            'id_token'    => ['required', 'string'],
            'nonce'       => ['sometimes', 'nullable', 'string'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:64'],
        ]);

        try {
            $identity = $provider === 'apple'
                ? $verifier->verifyApple($data['id_token'], $data['nonce'] ?? null)
                : $verifier->verifyGoogle($data['id_token']);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['id_token' => [$e->getMessage()]]);
        }

        $user = $this->findOrCreateSocialUser($identity);

        if ((int) $user->status === Status::USER_BAN) {
            abort(403, 'This account has been suspended.');
        }

        LoginActivityLogger::record($user);

        return $this->authResponse($user, $data['device_name'] ?? null);
    }

    private function findOrCreateSocialUser(SocialIdentity $identity): User
    {
        // Prefer the provider_id binding (web's SocialLogin uses the same key).
        $user = User::where('provider_id', $identity->providerId)
            ->where('provider', $identity->provider)
            ->first();
        if ($user) {
            return $user;
        }

        // If the email is already on a local account, link it. Apple may
        // withhold the email on subsequent logins (the user gets it once),
        // so this is best-effort.
        if ($identity->email) {
            $user = User::where('email', strtolower($identity->email))->first();
            if ($user) {
                $user->provider_id = $identity->providerId;
                $user->provider    = $identity->provider;
                $user->save();
                return $user;
            }
        }

        abort_unless((bool) gs('registration'), 403, 'Registration is disabled.');
        abort_unless($identity->email, 422, 'The provider did not return an email; cannot create account.');

        $user            = new User();
        $user->email     = strtolower($identity->email);
        $user->firstname = $identity->firstName ?? 'New';
        $user->lastname  = $identity->lastName ?? 'User';
        $user->password  = Hash::make(Str::random(40)); // unusable; user signs in via the provider.
        $user->provider_id = $identity->providerId;
        $user->provider    = $identity->provider;
        $user->ev          = $identity->emailVerified ? Status::VERIFIED : (gs('ev') ? Status::UNVERIFIED : Status::VERIFIED);
        $user->sv          = gs('sv') ? Status::UNVERIFIED : Status::VERIFIED;
        $user->kv          = gs('kv') ? Status::NO : Status::YES;
        $user->ts          = Status::DISABLE;
        $user->tv          = Status::ENABLE;
        $user->save();

        return $user;
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        return $this->verifyCodeAgainstUser($request, field: 'ev', label: 'email');
    }

    public function verifyMobile(Request $request): JsonResponse
    {
        return $this->verifyCodeAgainstUser($request, field: 'sv', label: 'mobile');
    }

    public function verify2fa(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Two-factor verification is not implemented yet.',
        ], 501);
    }

    public function sendEmailCode(Request $request): JsonResponse
    {
        return $this->sendVerificationCode($request, type: 'email');
    }

    public function sendMobileCode(Request $request): JsonResponse
    {
        return $this->sendVerificationCode($request, type: 'sms');
    }

    /**
     * Mirrors User\AuthorizationController::sendVerifyCode — 2-minute rate
     * limit between sends, 6-digit code stamped onto users.ver_code, and
     * the same EVER_CODE / SVER_CODE notify templates the web flow uses so
     * admins don't need to maintain a parallel template set.
     */
    private function sendVerificationCode(Request $request, string $type): JsonResponse
    {
        $user = $request->user();

        if ($type === 'email' && (int) $user->ev === Status::VERIFIED) {
            return response()->json(['message' => 'Email is already verified.'], 422);
        }
        if ($type === 'sms' && (int) $user->sv === Status::VERIFIED) {
            return response()->json(['message' => 'Mobile is already verified.'], 422);
        }
        if ($type === 'sms' && empty($user->mobile)) {
            return response()->json(['message' => 'Add a mobile number before requesting a code.'], 422);
        }

        if ($user->ver_code_send_at && $user->ver_code_send_at->addMinutes(2)->isFuture()) {
            $retryIn = $user->ver_code_send_at->addMinutes(2)->diffInSeconds(Carbon::now());
            return response()->json([
                'message'        => "Please wait {$retryIn} seconds before requesting another code.",
                'retry_after_s'  => $retryIn,
            ], 429);
        }

        $user->ver_code         = verificationCode(6);
        $user->ver_code_send_at = Carbon::now();
        $user->save();

        notify($user, $type === 'email' ? 'EVER_CODE' : 'SVER_CODE', [
            'code' => $user->ver_code,
        ], [$type]);

        return response()->json([], 204);
    }

    private function verifyCodeAgainstUser(Request $request, string $field, string $label): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();
        if (! $user->ver_code || ! hash_equals((string) $user->ver_code, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => ["Invalid {$label} verification code."],
            ]);
        }

        $user->{$field}         = Status::VERIFIED;
        $user->ver_code         = null;
        $user->ver_code_send_at = null;
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
