<?php

namespace App\Http\Resources;

use App\Constants\Status;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Comment $comment */
        $comment = $this->resource;
        $user    = $comment->user;

        $userReaction = 0;
        $likes        = 0;
        if ($comment->relationLoaded('userReactions')) {
            $likes = $comment->userReactions->where('is_like', Status::YES)->count();
            if ($request->user()) {
                $mine = $comment->userReactions->firstWhere('user_id', $request->user()->id);
                if ($mine) {
                    $userReaction = (int) $mine->is_like === Status::YES ? 1 : -1;
                }
            }
        }

        return [
            'id'          => $comment->id,
            'body'        => $comment->comment,
            'parent_id'   => $comment->parent_id ? (int) $comment->parent_id : null,
            'created_at'  => $comment->created_at?->toIso8601String(),
            'author'      => $user ? [
                'id'     => $user->id,
                'slug'   => $user->username,
                'name'   => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: $user->username,
                'avatar' => null,
            ] : null,
            'reply_count'   => $comment->relationLoaded('replies') ? $comment->replies->count() : 0,
            'likes'         => $likes,
            'user_reaction' => $userReaction,
            'replies'       => $this->whenLoaded(
                'replies',
                fn () => CommentResource::collection($comment->replies),
            ),
        ];
    }
}
