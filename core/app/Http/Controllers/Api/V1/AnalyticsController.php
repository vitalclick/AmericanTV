<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Video;
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

    /**
     * Per-video watch-duration aggregation. Returns:
     *  - sessions: distinct play sessions
     *  - completion_rate: fraction of sessions that fired video_finished
     *  - p50 / p90 watched_seconds across all sessions
     *  - dropoff: bucketed counts (0-10s, 10-30s, 30s-1m, 1-5m, 5-15m, 15m+)
     *
     * Owner-scoped: the requester must own the video or be an admin.
     * Creator dashboards (mobile + web) call this; admins consume the same
     * payload from the admin panel.
     */
    public function videoWatchSummary(Request $request, int $videoId): JsonResponse
    {
        $request->validate([
            'window_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $video = Video::findOrFail($videoId);
        $user  = $request->user();
        if ($user->id !== $video->user_id) {
            // Admin guard: re-uses the same admin guard the web panel binds.
            abort_unless(\Auth::guard('admin')->check(), 403);
        }

        $windowDays = (int) ($request->query('window_days') ?: 30);
        $since = Carbon::now()->subDays($windowDays);

        // One row per (session_id, video_id) — take the latest
        // watched_seconds from whichever terminal event fired.
        $sessions = DB::table('app_events')
            ->select([
                'session_id',
                DB::raw("MAX(CASE WHEN name='video_finished' THEN 1 ELSE 0 END) as completed"),
                DB::raw("MAX(CASE WHEN name IN ('video_finished','video_play_session_ended','video_progress') THEN CAST(JSON_EXTRACT(payload, '$.watched_seconds') AS UNSIGNED) ELSE NULL END) as watched_seconds"),
            ])
            ->where('video_id', $videoId)
            ->where('occurred_at', '>=', $since)
            ->whereNotNull('session_id')
            ->groupBy('session_id')
            ->get();

        $count = $sessions->count();
        if ($count === 0) {
            return response()->json([
                'data' => [
                    'video_id'        => $videoId,
                    'window_days'     => $windowDays,
                    'sessions'        => 0,
                    'completion_rate' => 0,
                    'p50'             => 0,
                    'p90'             => 0,
                    'dropoff'         => [],
                ],
            ]);
        }

        $durations = $sessions
            ->map(fn ($r) => (int) ($r->watched_seconds ?? 0))
            ->filter(fn ($v) => $v > 0)
            ->sort()
            ->values();

        $completedCount = $sessions->where('completed', 1)->count();

        return response()->json([
            'data' => [
                'video_id'        => $videoId,
                'window_days'     => $windowDays,
                'sessions'        => $count,
                'completion_rate' => round($completedCount / $count, 4),
                'p50'             => $this->percentile($durations, 0.5),
                'p90'             => $this->percentile($durations, 0.9),
                'dropoff'         => $this->bucket($durations),
            ],
        ]);
    }

    private function percentile($durations, float $p): int
    {
        if ($durations->isEmpty()) return 0;
        $idx = (int) round(($durations->count() - 1) * $p);
        return (int) $durations[$idx];
    }

    private function bucket($durations): array
    {
        $buckets = [
            '0-10s'   => 0,
            '10-30s'  => 0,
            '30s-1m'  => 0,
            '1-5m'    => 0,
            '5-15m'   => 0,
            '15m+'    => 0,
        ];
        foreach ($durations as $d) {
            $key = match (true) {
                $d < 10                 => '0-10s',
                $d < 30                 => '10-30s',
                $d < 60                 => '30s-1m',
                $d < 300                => '1-5m',
                $d < 900                => '5-15m',
                default                 => '15m+',
            };
            $buckets[$key]++;
        }
        return $buckets;
    }
}
