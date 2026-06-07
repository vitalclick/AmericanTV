<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserReaction;
use App\Http\Resources\VideoSummaryResource;
use App\Models\Video;
use App\Models\WatchHistory;
use App\Models\WatchLater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Engagement endpoints surfaced to the mobile client. Implemented in this PR:
 * comments listing + posting. Reactions, watch-later, history, and channel
 * subscription are still stubbed in routes/api.php — they'll land next.
 */
class EngagementController extends Controller
{
    public function listComments(Request $request, int $videoId): ResourceCollection
    {
        $video = Video::published()
            ->public()
            ->whereHas('user', fn (Builder $q) => $q->active())
            ->findOrFail($videoId);

        $comments = Comment::with('user')
            ->where('video_id', $video->id)
            ->whereNull('parent_id')
            ->orderByDesc('id')
            ->paginate(20);

        return CommentResource::collection($comments);
    }

    public function postComment(Request $request, int $videoId): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $video = Video::published()
            ->public()
            ->whereHas('user', fn (Builder $q) => $q->active())
            ->findOrFail($videoId);

        $comment           = new Comment();
        $comment->user_id  = $request->user()->id;
        $comment->video_id = $video->id;
        $comment->comment  = $data['body'];
        $comment->save();

        // Notify the video owner — same shape the web CommentController uses,
        // so notification feeds look identical across web and mobile.
        if ($video->user_id !== $request->user()->id) {
            $notification            = new UserNotification();
            $notification->user_id   = $video->user_id;
            $notification->title     = $request->user()->fullname . ' commented on your video';
            $notification->click_url = urlPath('video.play', [$video->id, $video->slug]);
            $notification->save();
        }

        $comment->load('user');

        return response()->json([
            'data' => (new CommentResource($comment))->toArray($request),
        ], 201);
    }

    public function replyToComment(Request $request, int $commentId): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $parent = Comment::with('video')->findOrFail($commentId);

        $reply                   = new Comment();
        $reply->user_id          = $request->user()->id;
        $reply->video_id         = $parent->video_id;
        $reply->parent_id        = $parent->id;
        $reply->replier_user_id  = $parent->user_id;
        $reply->comment          = $data['body'];
        $reply->save();

        if ($parent->user_id !== $request->user()->id) {
            $notification            = new UserNotification();
            $notification->user_id   = $parent->user_id;
            $notification->title     = $request->user()->fullname . ' replied to your comment';
            $notification->click_url = urlPath('video.play', [$parent->video->id, $parent->video->slug]);
            $notification->save();
        }

        $reply->load('user');

        return response()->json([
            'data' => (new CommentResource($reply))->toArray($request),
        ], 201);
    }

    public function reactToVideo(Request $request, int $videoId): JsonResponse
    {
        $data = $request->validate([
            'is_like' => ['required', Rule::in([0, 1])],
        ]);

        $video = Video::published()
            ->public()
            ->findOrFail($videoId);

        $isLike   = (int) $data['is_like'];
        $existing = $video->userReactions()
            ->where('user_id', $request->user()->id)
            ->first();

        $state = $this->toggleReaction(
            existing: $existing,
            isLike: $isLike,
            create: fn () => UserReaction::create([
                'user_id'        => $request->user()->id,
                'video_id'       => $video->id,
                'video_owner_id' => $video->user_id,
                'is_like'        => $isLike,
            ]),
        );

        return response()->json([
            'data' => [
                'user_reaction' => $state, // 1 = like, -1 = dislike, 0 = none
                'likes'         => $video->userReactions()->where('is_like', Status::YES)->count(),
                'dislikes'      => $video->userReactions()->where('is_like', Status::NO)->count(),
            ],
        ]);
    }

    public function reactToComment(Request $request, int $commentId): JsonResponse
    {
        $data = $request->validate([
            'is_like' => ['required', Rule::in([0, 1])],
        ]);

        $comment  = Comment::findOrFail($commentId);
        $isLike   = (int) $data['is_like'];
        $existing = $comment->userReactions()
            ->where('user_id', $request->user()->id)
            ->first();

        $state = $this->toggleReaction(
            existing: $existing,
            isLike: $isLike,
            create: fn () => UserReaction::create([
                'user_id'    => $request->user()->id,
                'comment_id' => $comment->id,
                'is_like'    => $isLike,
            ]),
        );

        return response()->json([
            'data' => [
                'user_reaction' => $state,
                'likes'         => $comment->userReactions()->where('is_like', Status::YES)->count(),
                'dislikes'      => $comment->userReactions()->where('is_like', Status::NO)->count(),
            ],
        ]);
    }

    public function subscribeChannel(Request $request, int $channelId): JsonResponse
    {
        $channel = User::active()->findOrFail($channelId);

        if ($channel->id === $request->user()->id) {
            return response()->json(['message' => "You can't subscribe to your own channel."], 422);
        }

        $existing = $channel->subscribers()
            ->where('following_id', $request->user()->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'data' => [
                    'subscribed'      => false,
                    'subscriber_count' => $channel->subscribers()->count(),
                ],
            ]);
        }

        $subscriber               = new Subscriber();
        $subscriber->user_id      = $channel->id;
        $subscriber->following_id = $request->user()->id;
        $subscriber->save();

        $notification            = new UserNotification();
        $notification->user_id   = $channel->id;
        $notification->title     = $request->user()->fullname . ' subscribed to your channel.';
        $notification->click_url = urlPath('user.channel');
        $notification->save();

        return response()->json([
            'data' => [
                'subscribed'      => true,
                'subscriber_count' => $channel->subscribers()->count(),
            ],
        ]);
    }

    /**
     * Apply the same toggle/swap/remove semantics the web UserController uses:
     * tapping the same direction twice clears the reaction; tapping the other
     * direction swaps it; absent => create.
     *
     * Returns 1/-1/0 to match the user_reaction shape on VideoDetailResource.
     */
    private function toggleReaction(?UserReaction $existing, int $isLike, callable $create): int
    {
        if ($existing && (int) $existing->is_like === $isLike) {
            $existing->delete();
            return 0;
        }
        if ($existing) {
            $existing->is_like = $isLike;
            $existing->save();
            return $isLike === Status::YES ? 1 : -1;
        }
        $create();
        return $isLike === Status::YES ? 1 : -1;
    }

    public function listWatchLater(Request $request): ResourceCollection
    {
        $videos = Video::with('user', 'videoFiles')
            ->whereIn(
                'id',
                WatchLater::where('user_id', $request->user()->id)->pluck('video_id'),
            )
            ->published()
            ->whereHas('user', fn (Builder $q) => $q->active())
            ->orderByDesc('id')
            ->paginate(20);

        return VideoSummaryResource::collection($videos);
    }

    public function addWatchLater(Request $request, int $videoId): JsonResponse
    {
        $video = Video::published()
            ->whereHas('user', fn (Builder $q) => $q->active())
            ->findOrFail($videoId);

        WatchLater::firstOrCreate([
            'user_id'  => $request->user()->id,
            'video_id' => $video->id,
        ]);

        return response()->json([], 204);
    }

    public function removeWatchLater(Request $request, int $videoId): JsonResponse
    {
        WatchLater::where('user_id', $request->user()->id)
            ->where('video_id', $videoId)
            ->delete();

        return response()->json([], 204);
    }

    public function listHistory(Request $request): ResourceCollection
    {
        // Order by last_view rather than primary key — re-watches should
        // bubble back to the top, matching how the web `history` page renders.
        $videos = Video::with('user', 'videoFiles')
            ->join('watch_histories', 'watch_histories.video_id', '=', 'videos.id')
            ->where('watch_histories.user_id', $request->user()->id)
            ->whereHas('user', fn (Builder $q) => $q->active())
            ->orderByDesc('watch_histories.last_view')
            ->select('videos.*', 'watch_histories.last_view as _last_view')
            ->paginate(20);

        return VideoSummaryResource::collection($videos);
    }

    public function removeHistory(Request $request, int $videoId): JsonResponse
    {
        WatchHistory::where('user_id', $request->user()->id)
            ->where('video_id', $videoId)
            ->delete();

        return response()->json([], 204);
    }

    public function clearHistory(Request $request): JsonResponse
    {
        WatchHistory::where('user_id', $request->user()->id)->delete();
        return response()->json([], 204);
    }

    private function stub(): JsonResponse
    {
        return response()->json(['message' => 'Endpoint not implemented yet.'], 501);
    }
}
