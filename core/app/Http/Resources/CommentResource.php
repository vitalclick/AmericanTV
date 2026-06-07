<?php

namespace App\Http\Resources;

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

        return [
            'id'         => $comment->id,
            'body'       => $comment->comment,
            'parent_id'  => $comment->parent_id ? (int) $comment->parent_id : null,
            'created_at' => $comment->created_at?->toIso8601String(),
            'author'     => $user ? [
                'id'     => $user->id,
                'slug'   => $user->username,
                'name'   => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: $user->username,
                'avatar' => null,
            ] : null,
            'reply_count' => $this->whenLoaded('replies', fn () => $comment->replies->count()),
        ];
    }
}
