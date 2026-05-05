<div class="reaction-btn__like  commentReaction" data-comment_id="{{ $comment->id }}" data-reaction="1">
    @if (@$comment->isLikedByAuthUser)
        <i class="vti-like-fill  reactionIcon"></i>
    @else
        <i class="vti-like reactionIcon"></i>
    @endif
    <span class="likeCount">{{ @$comment->reactionLikeCount }}</span>
</div>
<div class="reaction-btn__dislike  commentReaction" data-comment_id="{{ $comment->id }}" data-reaction="0">
    @if (@$comment->isUnlikedByAuthUser)
        <i class="vti-dislike-fill reactionIcon"></i>
    @else
        <i class="vti-dislike reactionIcon"></i>
    @endif
</div>
