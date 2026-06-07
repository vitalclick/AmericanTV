<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\UserNotification;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

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
        return $this->stub();
    }

    public function reactToComment(Request $request, int $commentId): JsonResponse
    {
        return $this->stub();
    }

    public function subscribeChannel(Request $request, int $channelId): JsonResponse
    {
        return $this->stub();
    }

    public function listWatchLater(Request $request): JsonResponse
    {
        return $this->stub();
    }

    public function addWatchLater(Request $request, int $videoId): JsonResponse
    {
        return $this->stub();
    }

    public function removeWatchLater(Request $request, int $videoId): JsonResponse
    {
        return $this->stub();
    }

    public function listHistory(Request $request): JsonResponse
    {
        return $this->stub();
    }

    public function removeHistory(Request $request, int $videoId): JsonResponse
    {
        return $this->stub();
    }

    public function clearHistory(Request $request): JsonResponse
    {
        return $this->stub();
    }

    private function stub(): JsonResponse
    {
        return response()->json(['message' => 'Endpoint not implemented yet.'], 501);
    }
}
