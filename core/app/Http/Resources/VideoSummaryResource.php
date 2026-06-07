<?php

namespace App\Http\Resources;

use App\Constants\Status;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape used in feed tiles and search results. Mirrors VideoSummary in
 * core/docs/api/openapi-v1.yaml.
 */
class VideoSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Video $video */
        $video = $this->resource;
        $user  = $video->user;

        return [
            'id'               => $video->id,
            'slug'             => $video->slug,
            'title'            => $video->title,
            'thumbnail'        => $video->thumb_image
                ? asset(getFilePath('thumbnail') . '/' . $video->thumb_image)
                : null,
            'duration_seconds' => null, // populated once video transcoding records duration; see TODO in PlaybackController.
            'views'            => (int) ($video->views ?? 0),
            'is_paid'          => (int) $video->stock_video === Status::YES,
            'price'            => $video->stock_video ? (float) $video->price : null,
            'created_at'       => $video->created_at?->toIso8601String(),
            'channel'          => $user ? [
                'id'     => $user->id,
                'slug'   => $user->username,
                'name'   => trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? '')) ?: $user->username,
                'avatar' => null,
            ] : null,
        ];
    }
}
