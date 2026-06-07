<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Receives batched engagement events from the mobile client. One row per
 * event, deliberately schema-light so we don't have to migrate every time
 * we add a new tracked interaction.
 *
 * The interesting downstream queries:
 *  - tile-tap CTR on the feed
 *  - watch-duration distribution per video
 *  - paywall conversion (paywall_impression -> purchase via Deposit.trx)
 *
 * Existing tables (Impression, AdvertisementReached) cover ad-specific
 * funnel metrics; this is for non-ad telemetry.
 */
class AnalyticsController extends Controller
{
    public function ingest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'events'                         => ['required', 'array', 'min:1', 'max:100'],
            'events.*.name'                  => ['required', 'string', 'max:64'],
            'events.*.occurred_at'           => ['required', 'date'],
            'events.*.platform'              => ['nullable', Rule::in(['ios', 'android'])],
            'events.*.session_id'            => ['nullable', 'string', 'max:64'],
            'events.*.video_id'              => ['nullable', 'integer'],
            'events.*.payload'               => ['nullable', 'array'],
        ]);

        $now = Carbon::now();
        $userId = $request->user()?->id;

        $rows = collect($data['events'])->map(fn (array $e) => [
            'user_id'     => $userId,
            'name'        => $e['name'],
            'platform'    => $e['platform']    ?? null,
            'session_id'  => $e['session_id']  ?? null,
            'video_id'    => $e['video_id']    ?? null,
            'payload'     => isset($e['payload']) ? json_encode($e['payload']) : null,
            'occurred_at' => Carbon::parse($e['occurred_at']),
            'created_at'  => $now,
        ])->all();

        DB::table('app_events')->insert($rows);

        return response()->json(['received' => count($rows)], 202);
    }
}
