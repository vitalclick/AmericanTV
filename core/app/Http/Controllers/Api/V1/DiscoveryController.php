<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\VideoDetailResource;
use App\Http\Resources\VideoSummaryResource;
use App\Models\Category;
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
        $video = $this->baseFeedQuery()
            ->with(
                'user',
                'videoFiles',
                'category',
                'tags',
                'subtitles',
                'userReactions',
            )
            ->withCount('allComments as all_comments_count')
            ->where('slug', $slug)
            ->where('is_shorts_video', Status::NO)
            ->firstOrFail();

        return response()->json([
            'data' => (new VideoDetailResource($video))->toArray($request),
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
