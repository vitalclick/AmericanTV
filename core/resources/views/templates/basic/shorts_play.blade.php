@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="short-play-body">
        <div class="short-video-wrapper">
            <div class="shorts_video_sliders slick-slider">
                <!-- Tag Slider -->
                <div class="video-wrapper">
                    <video class="video-player" playsinline preload="metadata" data-video_id="{{ $short->id }}" controls>
                        <source src="{{ route('short.path', encrypt($short->id)) }}" type="video/mp4" />
                    </video>
                    <div class="action-container">
                        <div class="cmn-button-item">
                            <button class="like-button  button-item reactionBtn" data-video_id="{{ $short->id }}"
                                data-reaction="1">
                                @if ($short->userReactions()->where('user_id', auth()->id())->where('is_like', Status::YES)->exists())
                                    <i class="vti-like-fill reactionIcon"></i>
                                @else
                                    <i class="vti-like reactionIcon"></i>
                                @endif
                            </button>
                            <span
                                class="buton-text likeCount">{{ formatNumber($short->userReactions()->like()->count()) }}</span>
                        </div>
                        <div class="cmn-button-item">
                            <button class="dislike-button  button-item reactionBtn" data-video_id="{{ $short->id }}"
                                data-reaction="0">
                                @if ($short->userReactions()->where('user_id', auth()->id())->where('is_like', Status::NO)->exists())
                                    <i class="vti-dislike-fill reactionIcon"></i>
                                @else
                                    <i class="vti-dislike reactionIcon"></i>
                                @endif
                            </button>

                        </div>
                        <div class="cmn-button-item comment">
                            <button class="button-item cmn-btn" data-video_id="{{ $short->id }}">
                                <i class="fa-solid fa-message"></i>
                            </button>
                        </div>
                        <div class="cmn-button-item">
                            <button class="button-item shareBtn" data-video="{{ $short }}">
                                <i class="fa-solid fa-share"></i>
                            </button>
                        </div>
                        <a class="action-container__thumb" href="{{ route('preview.channel', $short->user->slug) }}">
                            <img src="{{ getImage(getFilePath('userProfile') . '/' . $short->user->image, isAvatar: true) }}"
                                alt="@lang('image')">
                        </a>
                    </div>
                </div>


                @foreach ($relatedVideos as $relatedVideo)
                    <div class="video-wrapper">

                        <video class="video-player" playsinline data-video_id="{{ $relatedVideo->id }}" controls>
                            <source src="{{ route('short.path', encrypt($relatedVideo->id)) }}" type="video/mp4" />
                        </video>

                        <div class="action-container">
                            <div class="cmn-button-item">
                                <button class="like-button  button-item reactionBtn"
                                    data-video_id="{{ $relatedVideo->id }}" data-reaction="1">
                                    @if ($relatedVideo->userReactions()->where('user_id', auth()->id())->where('is_like', Status::YES)->exists())
                                        <i class="vti-like-fill reactionIcon"></i>
                                    @else
                                        <i class="vti-like reactionIcon"></i>
                                    @endif
                                </button>
                                <span
                                    class="buton-text likeCount">{{ formatNumber($relatedVideo->userReactions()->like()->count()) }}</span>
                            </div>
                            <div class="cmn-button-item">
                                <button class="dislike-button  button-item reactionBtn"
                                    data-video_id="{{ $relatedVideo->id }}" data-reaction="0">
                                    @if ($relatedVideo->userReactions()->where('user_id', auth()->id())->where('is_like', Status::NO)->exists())
                                        <i class="vti-dislike-fill reactionIcon"></i>
                                    @else
                                        <i class="vti-dislike reactionIcon"></i>
                                    @endif
                                </button>

                            </div>
                            <div class="cmn-button-item comment">
                                <button class="button-item cmn-btn" data-video_id="{{ $relatedVideo->id }}">
                                    <i class="fa-solid fa-message"></i>
                                </button>
                            </div>
                            <div class="cmn-button-item">
                                <button class="button-item shareBtn" data-video="{{ $relatedVideo }}">
                                    <i class="fa-solid fa-share"></i>
                                </button>

                            </div>
                            <a class="action-container__thumb"
                                href="{{ route('preview.channel', $relatedVideo->user->slug) }}">
                                <img src="{{ getImage(getFilePath('userProfile') . '/' . $relatedVideo->user->image, isAvatar: true) }}"
                                    alt="@lang('image')">
                            </a>
                        </div>
                    </div>
                @endforeach

            </div>
            <div class="comment-box">
                <div class="comment-box__header">
                    <h5 class="comment-box__title">@lang('Comments'): (<span class="buton-text commentCount">0</span>)</h5>
                    <button class="comment-box__close-icon">
                        <i class="las la-times"></i>
                    </button>

                </div>
                <div class="comment-box__content">

                </div>

                <form class="commnet-form comment-form">
                    <textarea class="form--control reply-form__textarea commentBox" name="comment" placeholder="Add a comment"></textarea>

                    <div class="reply-form__input-btn">
                        <button class="reply-form__btn submit-reply" type="submit">
                            <svg class="lucide lucide-send-horizontal" xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z">
                                </path>
                                <path d="M6 12h16"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    @include('Template::partials.share')
    @include('Template::partials.login_alert_modal')
@endsection
@push('style')
    <style>
        .spinner {
            text-align: center;
            margin-top: 20px;
        }

        .spinner i {
            font-size: 45px;
            color: hsl(var(--base));
            animation: spin 1s linear infinite;
        }

        .comment-form {
            position: relative;
        }

        .commentBox {
            display: block;
            overflow: hidden;
            resize: none;

        }

        textarea.form--control {
            height: unset;
        }


        .comment-form::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0px;
            height: 1px;
            background-color: hsl(var(--white));
            transition: .1s linear;

        }

        .comment-form:has(.form--control:focus)::after {
            width: 100%;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .short-play-body {
            z-index: 1;
        }

        .cmn-button-item {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .action-container__thumb img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        button.reply-form__btn.submit-reply svg {
            height: 16px;
            width: 16px;
        }

        .short-video-wrapper,
        .shorts_video_sliders,
        .short-video-wrapper .slick-list.draggable {
            height: 100% !important;
            border-radius: 8px;
        }

        .commnet-form {
            flex-shrink: 0;
            background: hsl(var(--bg-color));
            width: 100%;
            z-index: 999;
            border-top: 1px solid hsl(var(--white)/.1);
            position: relative;
        }

        .commnet-form .form--control {
            background-color: transparent;
            border: 0;
            border-radius: 0;
        }


        .button-item {
            color: hsl(var(--static-white));
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            background-color: hsl(var(--static-black)/.25);
        }

        .reply-form__input-btn {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .reply-form {
            position: relative;
            margin-top: 12px;
        }

        .reply-form::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0px;
            height: 1px;
            background-color: hsl(var(--white));
            transition: .1s linear;
        }

        .reply-form:has(.form--control:focus)::after {
            width: 100%;
        }

        .reply-form__btn {
            color: hsl(var(--white));
            background: transparent;
            font-size: 1rem;
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            width: 40px;
        }

        .short-video-wrapper {
            max-width: 470px;
            margin: 0 auto;
            position: relative;
            z-index: 99;
        }

        @media (max-width: 1199px) {
            .short-video-wrapper {
                overflow: hidden;
            }
        }

        .short-video-wrapper .plyr--video {
            max-width: 470px;
            border-radius: 6px
        }


        .video-wrapper {
            position: relative;
            z-index: 9991;
        }

        .action-container {
            position: absolute;
            bottom: 180px;
            right: 20px;
            display: flex;
            flex-direction: column;
            width: 50px;
            gap: 14px;
            z-index: 9999;
            align-items: center;
        }

        .buton-text {
            color: hsl(var(--static-white));
        }

        /* comment box css start here  */
        .comment-box__header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 20px;
            padding-bottom: 0;
            flex-shrink: 0
        }

        .reply-form__textarea {
            padding-right: 40px;
        }

        .comment-box__title {
            margin-bottom: 0;
        }

        .comment-box__close-icon {
            color: hsl(var(--white));
        }

        .comment-box {
            position: absolute;
            width: 380px;
            right: 0;
            top: var(--inner-p);
            height: calc(100% - var(--inner-p) * 2);
            background-color: hsl(var(--bg-color));
            visibility: hidden;
            opacity: 0;
            transform: translateX(0);
            transition: .3s linear;
            z-index: -1;
            overflow-y: hidden;
            border: 1px solid hsl(var(--white)/.1);
            border-radius: 6px;
            display: flex;
            flex-direction: column
        }

        .comment-box::-webkit-scrollbar {
            width: 0;
            height: 0;
        }


        @media (max-width:1499px) {
            .comment-box {
                width: 320px;
            }
        }

        .comment-box.show-comment {
            visibility: visible;
            opacity: 1;
            transform: translateX(102%);
        }

        @media (max-width:1199px) {
            .comment-box {
                transform: translateX(120%) !important;
                background: hsl(var(--body-background));
            }

            .comment-box.show-comment {
                visibility: visible;
                opacity: 1;
                transform: translateX(0%) !important;
                z-index: 999;
            }

            .commnet-form {
                width: 320px !important;
            }
        }

        .reply {
            cursor: pointer;
            font-size: 14px;
            color: hsl(var(--white));
        }

        .reply-wrapper {
            margin-top: 10px;
        }

        .reply-form .form--control {
            border: 0;
            padding: 0;
            border-bottom: 1px solid hsl(var(--white)/.2);
            border-radius: 0;
            padding-bottom: 3px;
            background-color: transparent;
            padding-right: 60px;
        }

        .comment-box-item__content {
            width: calc(100% - 40px);
        }

        .comment-box-item__name {
            color: hsl(var(--white));
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;

        }

        .comment-box-item__name .time {
            font-size: 12px;
            color: hsl(var(--body-color));
        }

        .reaction-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }

        .comment-box-item {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .comment-box-item:last-child {
            margin-bottom: 0;
        }

        .comment-box-item__thumb {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
        }

        .comment-box__content {
            overflow-y: scroll;
            flex: 1;
            padding: 30px;

        }

        .comment-box__content::-webkit-scrollbar {
            width: 5px;
        }

        .comment-box__content::-webkit-scrollbar-thumb {
            background: hsl(var(--white) / .2);
            border-radius: 10px;
        }

        .comment-box__content::-webkit-scrollbar-track {
            background: transparent;
        }

        .comment-box-item__thumb img {
            width: 100%;
            height: 100%;
        }

        .slick-arrow.slick-next::after {
            display: none !important;
        }

        .slick-arrow::after {
            display: none !important;
        }

        .short-play-body .slick-arrow {
            position: fixed;
            right: 20px;
            transform: translateY(-50%);
            display: flex;
            gap: 20px;
        }

        .short-play-body .slick-arrow.slick-prev {
            right: 20px;
            left: unset;
            width: 45px;
            height: 45px;
            background: hsl(var(--white) / .1);
            border-radius: 50%;
            font-size: 12px;
            top: 48%;
            border: 1px solid hsl(var(--white) / .2);
            color: hsl(var(--white));
        }


        .slick-next.slick-arrow {
            top: 54%;
            width: 45px;
            height: 45px;
            background: hsl(var(--white) / .1);
            border-radius: 50%;
            font-size: 12px;
            border: 1px solid hsl(var(--white) / .2);
            color: hsl(var(--white));
        }

        .slick-arrow:hover {
            color: hsl(var(--black)) !important;
            border-color: hsl(var(--white)) !important;
            background: hsl(var(--white)) !important;
        }

        .short-play-body .slick-arrow.slick-prev {
            right: 20px;
            left: unset;
        }

        .show-reply {
            cursor: pointer;
        }

        .home-fluid .home__right {
            display: flex;
            flex-direction: column;
        }

        .short-play-body {
            --inner-p: 50px;
            height: calc(100vh - var(--header-h));
        }


        @media (max-width: 575px) {
            .short-play-body {
                --inner-p: 0px;
                height: calc(100vh - (var(--header-h) + 59px));
            }
        }

        @media (max-width: 424px) {
            .short-play-body {
                height: calc(100vh - (var(--header-h) + 50px));
            }
        }

        .short-play-body .slick-slide>div {
            height: 100% !important;
        }

        .short-video-wrapper {
            padding-block: var(--inner-p);
        }

        .video-wrapper {
            height: 100%;
        }

        .short-video-wrapper .plyr--video {
            max-width: 470px;
            border-radius: 6px;
            height: 100%;
        }

        .slick-vertical .slick-slide {
            height: calc(100vh - (var(--header-h) + (var(--inner-p) * 2))) !important;
        }

        @media (max-width: 575px) {
            .slick-vertical .slick-slide {
                height: calc(100vh - (var(--header-h) + (var(--inner-p) * 2) + 59px)) !important;
            }
        }

        @media (max-width: 424px) {
            .slick-vertical .slick-slide {
                height: calc(100vh - (var(--header-h) + (var(--inner-p) * 2) + 50px)) !important;
            }
        }


        .plyr__video-embed iframe,
        .plyr__video-wrapper--fixed-ratio video {
            object-fit: cover;
        }

        .buton-text.commentCount {
            color: hsl(var(--white)) !important;
        }
    </style>
@endpush
@push('style-lib')
    <!-- Slick Slider -->
    <link href="{{ asset($activeTemplateTrue . 'css/slick.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/global/css/plyr.css') }}" rel="stylesheet">
@endpush
@push('script-lib')
    <!-- Slick js -->
    <script src="{{ asset($activeTemplateTrue . 'js/slick.min.js') }}"></script>

    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
@endpush
@push('script')
    <script>
        (function($) {
            'use strict';

            $(document).ready(function() {
                const auth = "{{ auth()->user() }}";
                var videoId;

                var shortCurrectPage = 2;
                let shortLastPage = false;
                var itemCount = 0;
                var player = '';

                var slick_2_is_animating = false;

                var slick = $('.shorts_video_sliders').slick({

                    infinite: false,
                    dots: false,

                    vertical: true,
                    verticalSwiping: true,
                    prevArrow: '<button type="button" class="slick-prev"><i class="fas fa-long-arrow-alt-up"></i></button>',
                    nextArrow: '<button type="button" class="slick-next"><i class="fas fa-long-arrow-alt-down"></i></button>',
                    responsive: [{
                        breakpoint: 575,
                        settings: {
                            arrows: false,
                        }
                    }]

                });

                slick.on("afterChange", function(index) {

                    slick_2_is_animating = false;

                    playVideo();



                });

                slick.on("beforeChange", function(index) {

                    slick_2_is_animating = true;

                    playVideo();



                });

                slick.on("wheel", function(e) {
                    slick_handle_wheel_event_debounced(e.originalEvent, slick, slick_2_is_animating);
                });




                function debounce(func, wait, immediate) {
                    var timeout;
                    return function() {
                        var context = this,
                            args = arguments;
                        var later = function() {
                            timeout = null;
                            if (!immediate) func.apply(context, args);
                        };
                        var callNow = immediate && !timeout;
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                        if (callNow) func.apply(context, args);
                    };
                };


                function slick_handle_wheel_event(e, slick_instance, slick_is_animating) {

                    if (!slick_is_animating) {

                        var direction =
                            Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY;

                        if (direction > 0) {

                            slick_instance.slick("slickNext");
                            shortCount();
                            viewsCount();

                        } else {

                            slick_instance.slick("slickPrev");
                            playVideo();
                        }
                    }
                }


                var slick_handle_wheel_event_debounced = debounce(
                    slick_handle_wheel_event, 100, true

                );



                $(document).ready(function() {
                    viewsCount();

                });


                function viewsCount() {

                    const videoId = $('.slick-active').find('video').data('video_id');
                    $.ajax({
                        type: "post",
                        url: "{{ route('short.view', '') }}/" + videoId,
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.satatus == 'success') {
                                return;
                            }
                        }
                    });
                }



                $(document).on('click', '.slick-next', function() {
                    shortCount();
                    viewsCount();

                });

                function shortCount() {

                    itemCount++;
                    if ($('.video-wrapper').length - itemCount == 1) {

                        loadMoreVideos();
                    }
                }


                function loadMoreVideos() {


                    const route = "{{ route('load.shorts.video') }}";
                    $.ajax({
                        url: `${route}?play_short=false&page=${shortCurrectPage}`,
                        type: 'GET',
                        success: function(response) {

                            if (response.status === 'success') {

                                $('.shorts_video_sliders').slick('slickAdd', response.data.html);

                                $('.shorts_video_sliders').slick('setPosition');

                                initializePlayer();

                                shortCurrectPage++;

                                if (shortCurrectPage >= response.data.last_page) {
                                    shortLastPage = true;
                                }
                            } else {
                                notify('error', response.message.error);
                            }
                        }
                    });
                }


                function initializePlayer() {
                    player = Plyr.setup('.video-player', {
                        controls: ['play', 'mute', 'volume', 'progress'],
                        autoplay: false,
                        ratio: '9:16',
                    });
                }

                function playVideo() {
                    $('video').each(function() {
                        this.pause();

                    });

                    let player = $('.slick-active').find('video')[0];
                    if (player) {
                        player.play();
                    }
                }


                $(document).on('click', '.plyr__video-wrapper', function() {
                    let player = $('.slick-active').find('video')[0];
                    if (player.paused) {
                        player.play();
                    } else {
                        player.pause();
                    }
                });



                // comment js start here




                // for comment
                let commentCurrentPage = 1;
                let commentLastPage = false;
                $(document).on('click', '.comment', function() {

                    $('.comment-box').addClass('show-comment');
                })
                $('.comment-box__close-icon').on('click', function() {
                    $('.comment-box').removeClass('show-comment');
                    commentLastPage = false;
                    commentCurrentPage = 1;
                })
                // comment js end here


                $(document).ready(function() {
                    $(document).on('input', '.commentBox', function() {
                        $(this).css('height', 'auto');
                        $(this).css('height', this.scrollHeight + 'px');

                    });
                });



                initializePlayer();


                $('.shorts_video_sliders').on('afterChange', function(event, slick, currentSlide) {
                    $('.comment-box__content').empty();
                    $('.comment-box').removeClass('show-comment');
                    commentLastPage = false;
                    commentCurrentPage = 1;
                });


                $(document).on('click', '.cmn-btn', function() {
                    videoId = $(this).data('video_id');

                    loadMoreComments(1, videoId);
                })


                $('.comment-form').on('submit', function(e) {
                    e.preventDefault();

                    if (!auth) {
                        $('#existModalCenter').modal('show');
                        return;
                    }

                    $.ajax({
                        type: "post",
                        url: "{{ route('user.comment.submit', '') }}/" + videoId,
                        data: $(this).serialize(),
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                $('.commentBox').css('height', '');
                                $('.comment-box__content').prepend(response.data.comment);
                                $('.comment-form').trigger('reset');
                                $('.commentCount').text(response.data.comment_count);

                            } else {
                                notify('error', response.message.error);
                            }
                        }
                    });
                });


                $(document).ready(function() {

                    $('.comment-box__content').on('scroll', function() {
                        let commentBox = $(this);
                        let scrollTop = commentBox.scrollTop();
                        let boxHeight = commentBox.outerHeight();
                        let contentHeight = commentBox[0].scrollHeight;

                        if (scrollTop + boxHeight >= contentHeight && !commentLastPage) {
                            commentCurrentPage++;
                            loadMoreComments(null, videoId);
                        }
                    });
                });


                function loadMoreComments(changeVideo = null, videoId) {
                    const commentsRoute = "{{ route('user.comment.get', '') }}/" + videoId;
                    $('#loading-spinner').removeClass('d-none');
                    $.ajax({
                        url: `${commentsRoute}?page=${commentCurrentPage}`,
                        type: 'GET',
                        success: function(response) {
                            $('#loading-spinner').addClass('d-none');

                            if (response.status === 'success') {

                                if (changeVideo) {
                                    $('.comment-box__content').empty()
                                }
                                $('.comment-box__content').append(response.data.commentHtml);
                                $('.commentCount').text(response.data.comment_count);


                                if (commentCurrentPage >= response.data.last_page) {
                                    commentLastPage = true;
                                }
                            } else {
                                notify('error', response.message.error);
                            }
                        }
                    });
                }

                $(document).on('click', '.show-reply', function() {
                    var replies = $(this).next('.append-reply');

                    if (replies.hasClass('d-none')) {
                        replies.removeClass('d-none').hide().slideDown();
                        $(this).find('.text').text('Hide Replies');
                        $(this).addClass('active');

                    } else {
                        replies.slideUp(function() {
                            replies.addClass('d-none').show();
                        });
                        $(this).find('.text').text('Show Replies');
                        $(this).removeClass('active');
                    }
                });


                $(document).on('click', '.reply', function() {
                    const replyForm = $(this).closest('.comment-box-item__content').find('.reply-form')
                        .first();
                    replyForm.toggleClass('d-none');
                });



                $(document).on('submit', '.reply-form', function(e) {
                    e.preventDefault();

                    if (!auth) {
                        $('#existModalCenter').modal('show');
                        return;
                    }

                    const form = $(this);

                    $.ajax({
                        type: "post",
                        url: "{{ route('user.comment.reply') }}",
                        data: form.serialize(),
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status === 'success') {
                                form.trigger('reset');
                                $('.commentBox').css('height', '');
                                var repliesContainer = form.closest('.parentComment').find(
                                    '.reply-wrapper').first();

                                if (repliesContainer.length) {
                                    repliesContainer.append(response.data.reply);
                                }

                                $('.commentCount').text(response.data.comment_count);
                            } else {
                                notify('error', response.message.error);
                            }
                        }
                    });
                });


                // for comment reaction
                $(document).on('click', '.commentReaction', function() {
                    if (!auth) {
                        $('#existModalCenter').modal('show');
                        return;
                    }

                    const value = $(this).data('reaction');
                    const commentId = $(this).data('comment_id');
                    const button = $(this);

                    $.ajax({
                        type: "post",
                        url: "{{ route('user.comment.like.dislike') }}/" + commentId,
                        dataType: "json",
                        data: {
                            is_like: value,
                            comment_id: commentId,
                        },
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.remark === 'like') {
                                button.find('.reactionIcon').removeClass('vti-like')
                                    .addClass('vti-like-fill');
                                button.siblings('.commentReaction').find('.reactionIcon')
                                    .removeClass('vti-dislike-fill').addClass(
                                        'vti-dislike');
                                button.find('.likeCount').text(response.data.like_count);

                            } else if (response.remark === 'like_remove') {
                                button.find('.reactionIcon').removeClass('vti-like-fill')
                                    .addClass('vti-like');
                                button.find('.likeCount').text(response.data.like_count);

                            } else if (response.remark === 'dislike') {
                                button.find('.reactionIcon').removeClass('vti-dislike')
                                    .addClass('vti-dislike-fill');
                                button.siblings('.commentReaction').find('.reactionIcon')
                                    .removeClass('vti-like-fill').addClass('vti-like');
                                button.siblings('.commentReaction').find('.likeCount').text(
                                    response.data.like_count);

                            } else if (response.remark === 'dislike_remove') {
                                button.find('.reactionIcon').removeClass('vti-dislike-fill')
                                    .addClass('vti-dislike');

                            } else if (response.remark === 'video_not_found') {
                                notify('error', response.message.error);
                            } else {
                                notify('error', 'Failed to update reaction');
                            }

                        }
                    });
                });

                //end comment reaction


                // for reaction


                $('.reactionBtn').on('click', function() {
                    if (!auth) {
                        $('#existModalCenter').modal('show');
                        return;
                    }
                    const button = $(this);
                    const value = button.data('reaction');
                    videoId = button.data('video_id');


                    const likeButton = button.closest('.action-container').find('.like-button');
                    const dislikeButton = button.closest('.action-container').find('.dislike-button');
                    const likeCountElem = likeButton.siblings('.likeCount');

                    $.ajax({
                        type: "POST",
                        url: "{{ route('user.reaction', '') }}/" + videoId,
                        dataType: "json",
                        data: {
                            is_like: value,
                        },
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.remark == 'like') {
                                likeButton.find('.reactionIcon').removeClass('vti-like')
                                    .addClass('vti-like-fill');
                                dislikeButton.find('.reactionIcon').removeClass(
                                    'vti-dislike-fill').addClass('vti-dislike');

                                likeCountElem.text(response.data.like_count);
                            } else if (response.remark == 'like_remove') {

                                likeButton.find('.reactionIcon').removeClass(
                                    'vti-like-fill').addClass('vti-like');
                                likeCountElem.text(response.data.like_count);
                            } else if (response.remark == 'dislike') {
                                dislikeButton.find('.reactionIcon').removeClass(
                                    'vti-dislike').addClass('vti-dislike-fill');
                                likeButton.find('.reactionIcon').removeClass(
                                    'vti-like-fill').addClass('vti-like');

                                likeCountElem.text(response.data.like_count);
                            } else if (response.remark == 'dislike_remove') {

                                dislikeButton.find('.reactionIcon').removeClass(
                                    'vti-dislike-fill').addClass('vti-dislike');
                            } else if (response.status == 'error') {
                                notify('error', response.message.error);
                            } else {
                                notify('error', 'Failed to update reaction');
                            }
                        },
                        error: function() {
                            notify('error',
                                'An error occurred while processing the request');
                        }
                    });
                });
                // end reacrtion


                $(document).ready(function() {
                    $(document).on('click', '.shareBtn', function() {
                        const video = $(this).data('video');


                        const baseUrl = "{{ route('short.play', '') }}";
                        const videoUrl =
                            `${baseUrl}/${video.id}/${encodeURIComponent(video.slug)}`;
                        const videoTitle = encodeURIComponent(video.title);

                        const url = `
            <a class="share-item whatsapp" href="https://api.whatsapp.com/send?text=${encodeURIComponent(videoUrl)}" target="_blank">
                <i class="lab la-whatsapp"></i>
            </a>
            <a class="share-item facebook" href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(videoUrl)}" target="_blank">
                <i class="lab la-facebook-f"></i>
            </a>
            <a class="share-item twitter" href="https://twitter.com/intent/tweet?url=${encodeURIComponent(videoUrl)}&text=${videoTitle}" target="_blank">
                <i class="fa-brands fa-x-twitter"></i>
            </a>
            <a class="share-item envelope" href="mailto:?subject=${videoTitle}&body=${encodeURIComponent(videoUrl)}">
                <i class="las la-envelope"></i>
            </a>
        `;
                        $('.share-items').html(url);
                        $('.copyText').val(videoUrl)

                        $('#shareModal').modal('show');
                    });
                });
            });
        })(jQuery);
    </script>
@endpush
