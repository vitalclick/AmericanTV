<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\VideoDetailResource;
use App\Http\Resources\VideoSummaryResource;
use App\Models\Category;
use App\Models\IapProduct;
use App\Models\Plan;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DiscoveryController extends Controller
{
    public function feed(Request $request): ResourceCollection
    {
        $videos = $this->baseFeedQuery()
            ->with('user', 'videoFiles')
            ->where('is_shorts_video', Status::NO)
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return VideoSummaryResource::collection($videos);
    }

    public function listVideos(Request $request): ResourceCollection
    {
        $query = $this->baseFeedQuery()
            ->with('user', 'videoFiles')
            ->where('is_shorts_video', Status::NO);

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $category));
        }

        if ($q = $request->query('q')) {
            $needle = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function (Builder $b) use ($needle) {
                $b->where('title', 'like', $needle)
                    ->orWhere('description', 'like', $needle)
                    ->orWhereHas('tags', fn (Builder $t) => $t->where('tag', 'like', $needle));
            });
        }

        match ($request->query('sort', 'recent')) {
            'trending' => $query->where(function (Builder $b) {
                $b->whereDate('created_at', '>=', now()->subDays(7))
                    ->orWhere('is_trending', Status::YES);
            })->orderByDesc('views'),
            'top'      => $query->orderByDesc('views'),
            default    => $query->orderByDesc('id'),
        };

        return VideoSummaryResource::collection($query->paginate($this->perPage($request)));
    }

    public function listShorts(Request $request): ResourceCollection
    {
        $videos = $this->baseFeedQuery()
            ->with('user')
            ->shorts()
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return VideoSummaryResource::collection($videos);
    }

    public function showVideo(Request $request, string $slug): JsonResponse
    {
        $userId = $request->user()?->id;

        $video = $this->baseFeedQuery()
            ->with([
                'user' => function ($q) use ($userId) {
                    if ($userId) {
                        // Bring just the caller's subscriber row through so
                        // VideoDetailResource::isSubscribed can answer without
                        // a second query.
                        $q->with(['subscribers' => fn ($s) => $s->where('following_id', $userId)]);
                    }
                },
                'videoFiles',
                'category',
                'tags',
                'subtitles',
                'userReactions',
                'plans',
                'playlists.plans',
            ])
            ->withCount('allComments as all_comments_count')
            ->where('slug', $slug)
            ->where('is_shorts_video', Status::NO)
            ->firstOrFail();

        return response()->json([
            'data' => (new VideoDetailResource($video))->toArray($request),
        ]);
    }

    public function plan(Request $request, string $slug): JsonResponse
    {
        $plan = Plan::where('slug', $slug)
            ->where('status', Status::ENABLE)
            ->with('user')
            ->withCount(['videos', 'playlists'])
            ->firstOrFail();

        $iap = IapProduct::where('type', 'plan')
            ->where('plan_id', $plan->id)
            ->where('active', true)
            ->first();

        return response()->json([
            'data' => [
                'id'             => $plan->id,
                'slug'           => $plan->slug,
                'name'           => $plan->name,
                'price'          => (float) $plan->price,
                'creator'        => $plan->user ? [
                    'id'   => $plan->user->id,
                    'slug' => $plan->user->username,
                    'name' => trim(($plan->user->firstname ?? '') . ' ' . ($plan->user->lastname ?? '')),
                ] : null,
                'video_count'    => (int) ($plan->videos_count ?? 0),
                'playlist_count' => (int) ($plan->playlists_count ?? 0),
                'iap' => $iap ? [
                    'apple_product_id'  => $iap->apple_product_id,
                    'google_product_id' => $iap->google_product_id,
                    'mobile_price_usd'  => (float) $iap->price_usd_mobile,
                ] : null,
            ],
        ]);
    }

    public function categories(Request $request): ResourceCollection
    {
        $categories = Category::active()
            ->withCount(['videos' => fn (Builder $q) => $this->applyVideoVisibility($q)])
            ->orderByDesc('videos_count')
            ->get();

        return CategoryResource::collection($categories);
    }

    private function baseFeedQuery(): Builder
    {
        return Video::published()
            ->public()
            ->withoutOnlyPlaylist()
            ->whereHas('user', fn (Builder $q) => $q->active());
    }

    private function applyVideoVisibility(Builder $q): Builder
    {
        return $q->where('status', Status::PUBLISHED)
            ->where('visibility', Status::PUBLIC)
            ->where('is_only_playlist', Status::NO);
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', (int) getPaginate());
        return max(1, min(50, $perPage));
    }
}
