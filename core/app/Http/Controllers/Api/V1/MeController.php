<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Lib\GoogleAuthenticator;
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

    /**
     * Generates a fresh TOTP secret and returns the otpauth:// URL the mobile
     * client can render as a QR code. The secret is NOT persisted yet — only
     * the verify call commits it, so an abandoned enrollment doesn't lock
     * the user out.
     */
    public function init2fa(Request $request): JsonResponse
    {
        $ga     = new GoogleAuthenticator();
        $secret = $ga->createSecret();

        $label  = $request->user()->username ?: $request->user()->email;
        $issuer = rawurlencode((string) (gs('site_name') ?? 'AmericanTV'));
        $otpauth = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            $issuer,
            rawurlencode($label),
            $secret,
            $issuer,
        );

        return response()->json([
            'data' => [
                'secret'   => $secret,
                'otpauth'  => $otpauth,
                'issuer'   => gs('site_name') ?? 'AmericanTV',
                'label'    => $label,
            ],
        ]);
    }

    public function enable2fa(Request $request): JsonResponse
    {
        $data = $request->validate([
            'secret' => ['required', 'string', 'size:16'],
            'code'   => ['required', 'string', 'size:6'],
        ]);

        if (! verifyG2fa($request->user(), $data['code'], $data['secret'])) {
            throw ValidationException::withMessages([
                'code' => ['Verification code does not match.'],
            ]);
        }

        $user = $request->user();
        $user->tsc = $data['secret'];
        $user->ts  = Status::ENABLE;
        $user->save();

        return response()->json([], 204);
    }

    public function disable2fa(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        if (! verifyG2fa($user, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Verification code does not match.'],
            ]);
        }

        $user->tsc = null;
        $user->ts  = Status::DISABLE;
        $user->save();

        return response()->json([], 204);
    }

    /**
     * Reuses VideoManager's web upload chunked-merge flow so the storage
     * pipeline (local / Wasabi / S3 / FTP / Cloudflare Stream handoff) stays
     * identical between web and mobile. Forwards the request after promoting
     * the auth user to the request's session so the Trait's auth() calls
     * resolve to the Sanctum user.
     */
    public function uploadVideoChunk(Request $request): JsonResponse
    {
        \Auth::guard('web')->setUser($request->user());
        return app(\App\Http\Controllers\User\VideoController::class)
            ->uploadFile($request);
    }

    public function mergeVideoChunks(Request $request): JsonResponse
    {
        \Auth::guard('web')->setUser($request->user());
        return app(\App\Http\Controllers\User\VideoController::class)
            ->mergeChunks($request);
    }

    public function submitVideoDetails(Request $request, int $id): JsonResponse
    {
        \Auth::guard('web')->setUser($request->user());
        return app(\App\Http\Controllers\User\VideoController::class)
            ->detailsSubmit($request, $id);
    }

    /**
     * Returns which chunk indices have already been received for a given
     * uploader-supplied uniqueId. Lets the mobile client resume after
     * background pause or network drop without re-sending everything.
     */
    /**
     * Mobile-native publish: sets category + visibility + tags + status =
     * PUBLISHED in one call. Mirrors VideoManager::visibilitySubmit but
     * accepts JSON (rather than a form post) and allows zero tags so
     * mobile creators can publish without forcing a tag taxonomy on them.
     */
    public function publishVideo(Request $request, int $id): JsonResponse
    {
        \Auth::guard('web')->setUser($request->user());

        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'visibility'  => ['required', 'in:0,1'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:32'],
        ]);

        $video = \App\Models\Video::where('step', '>=', Status::SECOND_STEP)
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $video->category_id = (int) $data['category_id'];
        $video->visibility  = (int) $data['visibility'];

        // Lift the step + flip status to PUBLISHED if this is the first
        // time the video is being made publishable.
        if ((int) $video->status === Status::NO || $video->step <= Status::THIRD_STEP) {
            $video->step   = Status::FOURTH_STEP;
            $video->status = Status::PUBLISHED;
        }
        $video->save();

        // Replace tags atomically so the API call is idempotent.
        $video->tags()->delete();
        foreach (($data['tags'] ?? []) as $tag) {
            $row = new \App\Models\VideoTag();
            $row->video_id = $video->id;
            $row->tag = $tag;
            $row->save();
        }

        return response()->json([
            'data' => [
                'id'         => $video->id,
                'slug'       => $video->slug,
                'visibility' => (int) $video->visibility,
                'status'     => (int) $video->status,
            ],
        ]);
    }

    public function inventoryUploadChunks(Request $request, string $uniqueId): JsonResponse
    {
        $request->validate([
            'file_name' => ['required', 'string', 'max:255'],
        ]);

        // uniqueId can only contain safe characters — prevents path traversal.
        if (! preg_match('/^[A-Za-z0-9_\-]{8,64}$/', $uniqueId)) {
            return response()->json(['message' => 'Invalid uniqueId'], 422);
        }

        $fileName = basename($request->query('file_name') ?: $request->input('file_name'));
        $tempDir  = storage_path("app/temp/{$uniqueId}");

        $present = [];
        if (is_dir($tempDir)) {
            $pattern = "{$tempDir}/{$fileName}.part";
            foreach (glob($pattern . '*') ?: [] as $path) {
                $suffix = substr($path, strlen($pattern));
                if (is_numeric($suffix)) {
                    $present[] = (int) $suffix;
                }
            }
            sort($present);
        }

        return response()->json([
            'data' => [
                'unique_id'      => $uniqueId,
                'file_name'      => $fileName,
                'chunks_present' => $present,
            ],
        ]);
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
