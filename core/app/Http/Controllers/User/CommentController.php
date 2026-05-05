<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\UserNotification;
use App\Models\UserReaction;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller {

    public function commentSubmit(Request $request, $id) {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $video = Video::published()->public()->whereHas('user', function ($query) {
            $query->active();
        })->find($id);

        if (!$video) {
            return response()->json([
                'status'  => 'error',
                'message' => ['error' => 'Video not found'],
            ]);
        }

        $comment           = new Comment();
        $comment->user_id  = auth()->id();
        $comment->video_id = $video->id;
        $comment->comment  = $request->comment;
        $comment->save();

        $comment->user_image = $comment->user->image;
        $comment->user_name  = $comment->user->fullname;

        if ($video->user_id != auth()->id()) {

            $userNotification            = new UserNotification();
            $userNotification->user_id   = $video->user_id;
            $userNotification->title     = auth()->user()->fullname . " Comment your video";
            $userNotification->click_url = urlPath('video.play', [$video->id, $video->slug]);
            $userNotification->save();

        }

        $html = view('Template::partials.video.comment', compact('comment'))->render();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'comment'       => $html,
                'comment_count' => $video->allComments->count(),
            ],
        ]);

    }

    public function replySubmit(Request $request) {

        $validator = Validator::make($request->all(), [
            'comment'  => 'required',
            'reply_to' => 'required|exists:comments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $parentComment = Comment::find($request->reply_to);

        if (!$parentComment) {
            return response()->json([
                'status'  => 'error',
                'message' => ['error' => 'Reply comment not found'],
            ]);
        }

        $comment                  = new Comment();
        $comment->user_id         = auth()->id();
        $comment->replier_user_id = $parentComment->user_id;
        $comment->video_id        = $parentComment->video_id;
        $comment->parent_id       = $parentComment->parent_id == 0 ? $parentComment->id : $parentComment->parent_id;
        $comment->comment         = $request->comment;
        $comment->save();

        $comment->user_image = $comment->user->image;
        $comment->user_name  = $comment->user->fullname;

        $comment->replier_user_name = $comment->replierUser->username;

        if ($parentComment->user_id != auth()->id()) {
            $userNotification            = new UserNotification();
            $userNotification->user_id   = $parentComment->user->id;
            $userNotification->title     = auth()->user()->fullname . " Reply your comment";
            $userNotification->click_url = urlPath('video.play', [$comment->video->id, $comment->video->slug]);
            $userNotification->save();
        }

        if ($comment->user_id != auth()->id()) {
            $userNotification            = new UserNotification();
            $userNotification->user_id   = $parentComment->video->user_id;
            $userNotification->title     = auth()->user()->fullname . ' Reply to ' . $parentComment->user->fullname . ' comment.';
            $userNotification->click_url = urlPath('video.play', [$parentComment->video->id, $parentComment->video->slug]);
            $userNotification->save();

        }

        $html = view('Template::partials.video.comment', compact('comment'))->render();

        return response()->json([
            'status'  => 'success',
            'message' => ['error' => 'Reply successfully submitted'],
            'data'    => [
                'reply'         => $html,
                'comment_count' => $parentComment->video->allComments->count(),
            ],
        ]);

    }

    public function getComment($id) {

        $video = Video::published()->whereHas('user', function ($query) {
            $query->active();
        })->find($id);

        if (!$video) {
            return response()->json([

                'status'  => 'error',
                'message' => ['error' => 'Video not found'],
            ]);
        }

        $sortBy = request()->input('sort_by', 'newest');

        $commentsQuery = $video->comments()
            ->with([
                'user',
                'replies.user',
                'replies.userReactions',
                'userReactions',
            ]);

        switch ($sortBy) {
            case 'oldest':
                $commentsQuery->orderBy('id', 'ASC');
                break;
            case 'top':
                $commentsQuery->withCount([
                    'userReactions as like_count' => function ($query) {
                        $query->where('is_like', Status::YES);
                    },
                    'replies as replies_count'
                ])
                    ->orderByRaw('(like_count * 3) + replies_count DESC')
                    ->orderBy('id', 'DESC');
                break;
            case 'newest':
            default:
                $commentsQuery->orderBy('id', 'DESC');
                break;
        }

        $comments = $commentsQuery->paginate(getPaginate());

        $html = view('Template::partials.video.comments', compact('comments'))->render();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'commentHtml'   => $html,
                'current_page'  => $comments->currentPage(),
                'last_page'     => $comments->lastPage(),
                'total'         => $comments->total(),
                'comment_count' => $video->allComments->count(),
            ],
        ]);
    }

    public function likeDislike(Request $request, $id) {

        $validator = Validator::make($request->all(), [
            'is_like' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([

                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([

                'status'  => 'error',
                'message' => ['error', 'The requested comment could not be found'],
            ]);
        }

        $isLike = $request->is_like;
        $userId = auth()->id();

        $existingReaction = $comment->userReactions()->where('user_id', $userId)->first();

        if ($existingReaction) {
            if ($existingReaction->is_like == $isLike) {
                $existingReaction->delete();
                return response()->json([
                    'remark' => $isLike == Status::YES ? 'like_remove' : 'dislike_remove',
                    'status' => 'success',
                    'data'   => [
                        'like_count' => $comment->userReactions()->like()->count(),
                    ],
                ]);
            } else {
                $existingReaction->is_like = $isLike;
                $existingReaction->save();

                if ($comment->user_id != auth()->id()) {
                    $userNotification          = new UserNotification();
                    $userNotification->user_id = $comment->user->id;
                    if ($existingReaction->is_like == Status::YES) {
                        $userNotification->title = auth()->user()->fullname . ' like your comment.';
                    } else {
                        $userNotification->title = auth()->user()->fullname . ' dislike your comment.';
                    }
                    $userNotification->click_url = urlPath('video.play', [$comment->video->id, $comment->video->slug]);
                    $userNotification->save();
                }

                return response()->json([
                    'remark' => $isLike == Status::YES ? 'like' : 'dislike',
                    'status' => 'success',
                    'data'   => [
                        'like_count' => $comment->userReactions()->like()->count(),
                    ],
                ]);
            }
        } else {

            $reaction             = new UserReaction();
            $reaction->user_id    = $userId;
            $reaction->comment_id = $comment->id;
            $reaction->is_like    = $isLike;
            $reaction->save();

            if ($comment->user_id != auth()->id()) {
                $userNotification          = new UserNotification();
                $userNotification->user_id = $comment->user->id;
                if ($reaction->is_like == Status::YES) {

                    $userNotification->title = auth()->user()->fullname . ' like your comment.';
                } else {
                    $userNotification->title = auth()->user()->fullname . ' dislike your comment.';

                }

                $userNotification->click_url = urlPath('video.play', [$comment->video->id, $comment->video->slug]);
                $userNotification->save();
            }

            return response()->json([
                'remark' => $isLike == Status::YES ? 'like' : 'dislike',
                'status' => 'success',
                'data'   => [
                    'like_count' => $comment->userReactions()->like()->count(),
                ],
            ]);
        }

    }

}
