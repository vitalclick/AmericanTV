<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
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
        return $this->stub();
    }

    public function unregisterDeviceToken(Request $request): JsonResponse
    {
        return $this->stub();
    }

    private function stub(): JsonResponse
    {
        return response()->json(['message' => 'Endpoint not implemented yet.'], 501);
    }
}
