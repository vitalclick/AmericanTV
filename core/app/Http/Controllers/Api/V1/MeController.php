<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\DeviceToken;
use App\Models\Transaction;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
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
        $user = $request->user();
        return response()->json([
            'balance'  => (float) $user->balance,
            'currency' => (string) (gs('cur_text') ?? 'USD'),
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['sometimes', 'in:deposit,purchase,earning,withdraw'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = Transaction::where('user_id', $request->user()->id)
            ->orderByDesc('id');

        // The Transaction.remark column carries a free-form string set by the
        // gateway / earnings pipelines (see PaymentController::userDataUpdate).
        // We group those into four buckets for the mobile filter.
        if ($type = $request->query('type')) {
            $query->whereIn('remark', match ($type) {
                'deposit'  => ['deposit'],
                'purchase' => ['purchased_video', 'purchased_playlist', 'purchased_plan', 'purchased_monetization'],
                'earning'  => ['earn_from_video', 'earn_from_playlist', 'earn_from_plan', 'ads_revenue'],
                'withdraw' => ['withdraw', 'withdraw_charge'],
                default    => [],
            });
        }

        $page = $query->paginate(20);

        return response()->json([
            'data' => $page->getCollection()->map(fn (Transaction $t) => [
                'id'           => $t->id,
                'trx'          => $t->trx,
                'trx_type'     => $t->trx_type,
                'amount'       => (float) $t->amount,
                'charge'       => (float) $t->charge,
                'post_balance' => (float) $t->post_balance,
                'details'      => (string) $t->details,
                'remark'       => (string) $t->remark,
                'created_at'   => $t->created_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
                'last_page'    => $page->lastPage(),
            ],
        ]);
    }

    public function earnings(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $totals = Transaction::where('user_id', $userId)
            ->selectRaw("
                SUM(CASE WHEN remark IN ('earn_from_video','earn_from_playlist','earn_from_plan','ads_revenue') AND trx_type='+' THEN amount ELSE 0 END) as total,
                SUM(CASE WHEN remark='earn_from_video' AND trx_type='+' THEN amount ELSE 0 END) as videos,
                SUM(CASE WHEN remark='earn_from_playlist' AND trx_type='+' THEN amount ELSE 0 END) as playlists,
                SUM(CASE WHEN remark='earn_from_plan' AND trx_type='+' THEN amount ELSE 0 END) as plans,
                SUM(CASE WHEN remark='ads_revenue' AND trx_type='+' THEN amount ELSE 0 END) as ads
            ")
            ->first();

        return response()->json([
            'data' => [
                'total'     => (float) ($totals->total ?? 0),
                'videos'    => (float) ($totals->videos ?? 0),
                'playlists' => (float) ($totals->playlists ?? 0),
                'plans'     => (float) ($totals->plans ?? 0),
                'ads'       => (float) ($totals->ads ?? 0),
                'currency'  => (string) (gs('cur_text') ?? 'USD'),
            ],
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $page = UserNotification::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(20);

        $unread = UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', Status::NO)
            ->count();

        return response()->json([
            'data' => $page->getCollection()->map(fn (UserNotification $n) => [
                'id'         => $n->id,
                'title'      => $n->title,
                'click_url'  => $n->click_url === '#' ? null : $n->click_url,
                'is_read'    => (int) $n->is_read === Status::YES,
                'created_at' => $n->created_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page'  => $page->currentPage(),
                'per_page'      => $page->perPage(),
                'total'         => $page->total(),
                'last_page'     => $page->lastPage(),
                'unread_count'  => $unread,
            ],
        ]);
    }

    public function markNotificationRead(Request $request, int $id): JsonResponse
    {
        $notification = UserNotification::where('user_id', $request->user()->id)
            ->findOrFail($id);
        $notification->is_read = Status::YES;
        $notification->save();
        return response()->json([], 204);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        UserNotification::where('user_id', $request->user()->id)
            ->where('is_read', Status::NO)
            ->update(['is_read' => Status::YES]);
        return response()->json([], 204);
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
