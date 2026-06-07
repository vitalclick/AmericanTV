<?php

namespace App\Http\Resources;

use App\Constants\Status;
use App\Models\Video;
use Illuminate\Http\Request;

/**
 * Extends VideoSummaryResource with description, taxonomy, engagement
 * counters, subtitle list, and the viewer's relationship to the video
 * (current reaction, paywall eligibility).
 */
class VideoDetailResource extends VideoSummaryResource
{
    public function toArray(Request $request): array
    {
        /** @var Video $video */
        $video = $this->resource;

        $summary = parent::toArray($request);

        $userReaction = 0;
        if ($request->user()) {
            $reaction = $video->userReactions->firstWhere('user_id', $request->user()->id);
            if ($reaction) {
                $userReaction = (int) $reaction->is_like === Status::YES ? 1 : -1;
            }
        }

        $likeCount    = $video->userReactions->where('is_like', Status::YES)->count();
        $dislikeCount = $video->userReactions->where('is_like', Status::NO)->count();

        return $summary + [
            'description'   => (string) ($video->description ?? ''),
            'category'      => $video->category ? [
                'id'   => $video->category->id,
                'slug' => $video->category->slug,
                'name' => $video->category->name,
            ] : null,
            'tags'          => $video->tags->pluck('tag')->toArray(),
            'likes'         => $likeCount,
            'dislikes'      => $dislikeCount,
            'comments'      => $video->all_comments_count ?? $video->comments_count ?? 0,
            'user_reaction' => $userReaction,
            'user_has_access' => $video->showEligible(),
            'is_subscribed' => $this->isSubscribed($video, $request),
            'subtitles'     => $video->subtitles->map(fn ($s) => [
                'language' => $s->language ?? $s->title,
                'url'      => $s->subtitle ? asset(getFilePath('subtitle') . '/' . $s->subtitle) : null,
            ])->values(),
            // Plans (if any) the user could subscribe to in order to unlock
            // this video. Lets the mobile detail screen open the right paywall
            // without a second round-trip.
            'access_plans'  => $this->buildAccessPlans($video),
        ];
    }

    /**
     * Has the caller subscribed to this video's uploader? Returns false when
     * the caller is anonymous or hasn't subscribed; never throws. Uses the
     * eager-loaded user.subscribers relation when present so this stays at
     * one query for the whole detail fetch.
     */
    private function isSubscribed($video, Request $request): bool
    {
        $user = $request->user();
        if (! $user || ! $video->user) return false;
        if ($user->id === $video->user_id) return false; // own channel.

        if ($video->user->relationLoaded('subscribers')) {
            return $video->user->subscribers->contains('following_id', $user->id);
        }
        return $video->user
            ->subscribers()
            ->where('following_id', $user->id)
            ->exists();
    }

    /**
     * Pick the plans that contain this video either directly or via a
     * playlist. Includes both options (web price + mobile / IAP product id)
     * so the client decides whether to show the IAP sheet or fall through to
     * a web-purchase link.
     */
    private function buildAccessPlans($video): array
    {
        $plans = collect();

        if ($video->relationLoaded('plans')) {
            $plans = $plans->merge($video->plans);
        }

        if ($video->relationLoaded('playlists')) {
            foreach ($video->playlists as $playlist) {
                if ($playlist->relationLoaded('plans')) {
                    $plans = $plans->merge($playlist->plans);
                }
            }
        }

        return $plans
            ->unique('id')
            ->filter(fn ($plan) => (int) $plan->status === \App\Constants\Status::ENABLE)
            ->take(3)
            ->map(function ($plan) {
                $iap = \App\Models\IapProduct::where('type', 'plan')
                    ->where('plan_id', $plan->id)
                    ->where('active', true)
                    ->first();

                return [
                    'id'    => $plan->id,
                    'slug'  => $plan->slug,
                    'name'  => $plan->name,
                    'price' => (float) $plan->price,
                    'iap'   => $iap ? [
                        'apple_product_id'  => $iap->apple_product_id,
                        'google_product_id' => $iap->google_product_id,
                        'mobile_price_usd'  => (float) $iap->price_usd_mobile,
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }
}
