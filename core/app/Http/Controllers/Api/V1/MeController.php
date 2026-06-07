<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        return $this->stub();
    }

    public function changePassword(Request $request): JsonResponse
    {
        return $this->stub();
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
