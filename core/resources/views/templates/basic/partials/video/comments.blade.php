@foreach ($comments as $comment)
    <div class="comment-box-item comment-item @if ($comment->parent_id == 0) parentComment @endif">
        <div class="comment-box-item__thumb">
            <img src="{{ getImage(getFilePath('userProfile') . '/' . $comment->user->image, isAvatar: true) }}" alt="User Image">
        </div>
        <div class="comment-box-item__content">
            <p class="comment-box-item__name">{{$comment->user->channel_name ? $comment->user->channel_name : $comment->user->fullname }}
                <span class="time">{{ $comment->created_at->diffForHumans() }}</span>
            </p>
            <p class="comment-box-item__text">
                {{ $comment->comment }}
            </p>

            <div class="reaction-btn">
                <div class="reaction-btn-inner">
                    @include('Template::partials.comment_reaction')
                </div>
                <div class="reaction-btn__reply">
                    <button class="reply">
                        <span class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" class="lucide lucide-message-square-quote">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                <path d="M8 12a2 2 0 0 0 2-2V8H8" />
                                <path d="M14 12a2 2 0 0 0 2-2V8h-2" />
                            </svg>
                        </span>
                        @lang('Reply')
                    </button>
                </div>
            </div>

            @if ($comment->parent_id == 0)
                <div class="reply-wrapper">
            @endif
            <form class="reply-form d-none mb-3">
                <input name="reply_to" type="hidden" value="{{ $comment->id }}" />

                <textarea class="form--control reply-form__textarea commentBox" name="comment" placeholder="Add a comment"></textarea>
                <div class="reply-form__input-btn">
                    <button class="reply-form__btn submit-reply" type="submit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" class="lucide lucide-send-horizontal">
                            <path
                                  d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z">
                            </path>
                            <path d="M6 12h16"></path>
                        </svg>
                    </button>
                </div>
            </form>
            @if (!blank($comment->replies))
                <span class="show-reply">
                    <span class="icon">
                        <i class="fas fa-chevron-down"></i>
                    </span>
                    <span class="text">@lang('Show Reply')</span>
                </span>
            @endif
            <div class="append-reply d-none">
                @foreach ($comment->replies ?? [] as $reply)
                    @include($activeTemplate . 'partials.video.comment', ['comment' => $reply])
                @endforeach
            </div>
            @if ($comment->parent_id == 0)
        </div>
@endif
</div>
</div>
@endforeach

<div class="text-center d-none spinner mt-4" id="loading-spinner">
    <i class="las la-spinner"></i>
</div>
