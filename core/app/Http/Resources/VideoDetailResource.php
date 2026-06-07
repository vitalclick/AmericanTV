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
            'subtitles'     => $video->subtitles->map(fn ($s) => [
                'language' => $s->language ?? $s->title,
                'url'      => $s->subtitle ? asset(getFilePath('subtitle') . '/' . $s->subtitle) : null,
            ])->values(),
        ];
    }
}
