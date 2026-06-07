<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => (new UserResource($request->user()))->toArray($request),
        ]);
    }

    // The remaining /me routes are scaffolded but not yet implemented —
    // intentionally returning 501 so the routes don't 404 and the mobile
    // client surfaces a clear "not yet available" message.
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'firstname' => ['sometimes', 'string', 'max:60'],
            'lastname'  => ['sometimes', 'string', 'max:60'],
            'username'  => ['sometimes', 'nullable', 'string', 'min:3', 'max:60', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'bio'       => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        foreach (['firstname', 'lastname', 'username', 'bio'] as $key) {
            if (array_key_exists($key, $data)) {
                $user->{$key} = $data[$key];
            }
        }
        $user->save();

        return response()->json([
            'data' => (new UserResource($user))->toArray($request),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $rule = Password::min(6);
        if (gs('secure_password')) {
            $rule = $rule->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $data = $request->validate([
            'current' => ['required', 'string'],
            'new'     => ['required', 'string', $rule, 'different:current'],
        ]);

        $user = $request->user();
        if (!Hash::check($data['current'], $user->password)) {
            throw ValidationException::withMessages([
                'current' => ['Current password is incorrect.'],
            ]);
        }

        $user->password = Hash::make($data['new']);
        $user->save();

        // Keep the calling token alive but revoke all others — security
        // best practice on password change.
        $callingId = $user->currentAccessToken()?->id;
        $user->tokens()
            ->when($callingId, fn ($q) => $q->where('id', '!=', $callingId))
            ->delete();

        return response()->json([], 204);
    }

    public function wallet(Request $request): JsonResponse
    {
        return $this->stub();
    }

    public function transactions(Request $request): JsonResponse
    {
        return $this->stub();
    }

    public function earnings(Request $request): JsonResponse
    {
        return $this->stub();
    }

    public function notifications(Request $request): JsonResponse
    {
        return $this->stub();
    }

    public function markNotificationRead(Request $request, int $id): JsonResponse
    {
        return $this->stub();
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        return $this->stub();
    }

    public function registerDeviceToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'in:ios,android'],
        ]);

        // Re-bind an existing row to the current user — multiple users on the
        // same device should still get push for the active account.
        $deviceToken = DeviceToken::firstOrNew(['token' => $data['token']]);
        $deviceToken->user_id = $request->user()->id;
        $deviceToken->is_app  = Status::YES;
        $deviceToken->save();

        return response()->json([], 204);
    }

    public function unregisterDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        DeviceToken::where('token', $request->input('token'))
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([], 204);
    }

    private function stub(): JsonResponse
    {
        return response()->json(['message' => 'Endpoint not implemented yet.'], 501);
    }
}
