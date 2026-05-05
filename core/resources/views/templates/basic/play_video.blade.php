@extends($activeTemplate . 'layouts.frontend')
@section('content')

    <div class="play-body">
        <div class="play-video">
            <div class="primary ps-0">
                <div class="primary__videoPlayer video-item__thumb mainVideo" data-price="{{ $video->price }}"
                    data-video-id="{{ $video->id }}" data-item_name="{{ $video->title }}">
                    @if ($purchasedTrue && $video->audience)
                        <div class="hidden-content ">
                            <div class="form-group">
                                <h4>{{ __(gs('vc_warning')->title) }}</h4>
                                <p class="mb-3">{{ __(gs('vc_warning')->description) }} </p>
                                <button class="btn btn--base SeeBtn">@lang('See Video')</button>
                            </div>
                        </div>
                    @endif


                    <video class="video-player" data-amount="{{ $video->price }}" playsinline
                        data-poster="{{ getImage(getFilePath('thumbnail') . '/' . $video->thumb_image) }}" controls
                        @if ($video->stock_video) data-video_id="{{ $video->id }}" @endif>
                        @if ($purchasedTrue)
                            @foreach ($video->videoFiles as $file)
                                <source src="{{ route('video.path', encrypt($file->id)) }}" type="video/mp4"
                                    size="{{ $file->quality }}" />
                            @endforeach

                            @foreach ($video->subtitles as $subtitle)
                                <track src="{{ getImage(getFilePath('subtitle') . '/' . $subtitle->file) }}"
                                    srclang="{{ $subtitle->language_code }}" kind="captions"
                                    label="{{ $subtitle->caption }}" default />
                            @endforeach
                        @endif
                    </video>


                    @include('Template::partials.video.video_loader')

                    @if (!$purchasedTrue)
                        <div class="premium-stock">
                            <div class="premium-stock-lock">
                                <svg class="lucide lucide-lock" xmlns="http://www.w3.org/2000/svg" width="24"
                                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                            </div>

                            <div class="premium-stock-inner">
                                <div class="left">
                                    <div class="premium-stock-price">
                                        {{ gs('cur_sym') }}{{ showAmount($video->price, currencyFormat: false) }}
                                    </div>
                                    <div class="premium-stock-icon">
                                        <svg class="_24ydrq0 _1286nb17o _1286nb12r6" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16"
                                            height="16">
                                            <path
                                                d="M486.2 50.2c-9.6-3.8-20.5-1.3-27.5 6.2l-98.2 125.5-83-161.1C273 13.2 264.9 8.5 256 8.5s-17.1 4.7-21.5 12.3l-83 161.1L53.3 56.5c-7-7.5-17.9-10-27.5-6.2C16.3 54 10 63.2 10 73.5v333c0 35.8 29.2 65 65 65h362c35.8 0 65-29.2 65-65v-333c0-10.3-6.3-19.5-15.8-23.3">
                                            </path>
                                        </svg>
                                        @lang('Premium')
                                    </div>
                                </div>
                                <div class="premium-stock-text">
                                    @lang('Purchase Now')
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="ad-wrapper position-relative adVideo d-none ">
                </div>

                <div class="primary__video-content">
                    <h4 class="primary__vtitle">{{ __($video->title) }}</h4>

                    <div class="primary__videometa">
                        <div class="items">
                            <span class="view"> <span class="icon"><i class="fa-regular fa-eye"></i></span>
                                {{ formatNumber($video->views) }} @lang('views')</span>
                            <span class="date"> <span class="icon"><i class="fa-regular fa-clock"></i></span>
                                {{ $video->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="meta-buttons">

                            <div class="meta-react-wrapper">
                                <div class="meta-react-inner">
                                    <button class="meta-buttons__button reactionBtn" data-reaction="1">
                                        <span class="icon">
                                            @if ($video->isLikedByAuthUser)
                                                <i class="vti-like-fill reactionIcon"></i>
                                            @else
                                                <i class="vti-like reactionIcon"></i>
                                            @endif
                                        </span>
                                        <span class="text likeCount">{{ formatNumber($video->reactionLikeCount) }}</span>
                                    </button>
                                    <button class="meta-buttons__button reactionBtn" data-reaction="0">
                                        <span class="icon">
                                            @if ($video->isUnlikedByAuthUser)
                                                <i class="vti-dislike-fill reactionIcon"></i>
                                            @else
                                                <i class="vti-dislike reactionIcon"></i>
                                            @endif
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <button class="meta-buttons__button shareBtn">
                                <span class="icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" fill="currentColor" clip-rule="evenodd"
                                            d="M4 11c.55228 0 1 .4477 1 1v8a.99997.99997 0 0 0 1 1h12c.2652 0 .5196-.1054.7071-.2929A1.0001 1.0001 0 0 0 19 20v-8c0-.5523.4477-1 1-1s1 .4477 1 1v8a2.9999 2.9999 0 0 1-.8787 2.1213A2.9999 2.9999 0 0 1 18 23H6a3.00006 3.00006 0 0 1-3-3v-8c0-.5523.44772-1 1-1Zm8-10c.2652 0 .5196.10536.7071.29289l4 4c.3905.39053.3905 1.02369 0 1.41422-.3905.39052-1.0237.39052-1.4142 0L12 3.41421l-3.29289 3.2929c-.39053.39052-1.02369.39052-1.41422 0-.39052-.39053-.39052-1.02369 0-1.41422l4.00001-4A.99997.99997 0 0 1 12 1Z">
                                        </path>
                                        <path fill-rule="evenodd"
                                            d="M12 1c.5523 0 1 .44772 1 1v13c0 .5523-.4477 1-1 1s-1-.4477-1-1V2c0-.55228.4477-1 1-1Z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                                <span class="text">@lang('Share')</span>
                            </button>

                            @if ($video->stock_video == Status::NO)
                                <button class="meta-buttons__button embed">
                                    <span class="icon"><svg width="18" height="18" viewBox="0 0 16 16">
                                            <path fill="none" stroke="currentColor" stroke-linecap="round"
                                                stroke-linejoin="round" d="m10.67 12 4-4-4-4M5.33 4l-4 4 4 4"></path>
                                        </svg></span>
                                    <span class="text">@lang('Embed')</span>
                                </button>
                            @endif

                            @auth
                                <button class="meta-buttons__button watchLater">
                                    <span class="icon">
                                        @if ($watchLater)
                                            <svg class="lucide lucide-square-check-big" xmlns="http://www.w3.org/2000/svg"
                                                width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="M21 10.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12.5" />
                                                <path d="m9 11 3 3L22 4" />
                                            </svg>
                                        @else
                                            <svg class="lucide lucide-clock" xmlns="http://www.w3.org/2000/svg"
                                                width="16" height="16" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10" />
                                                <polyline points="12 6 12 12 16 14" />
                                            </svg>
                                        @endif
                                    </span>
                                    <span class="text"> @lang('Watch Later')</span>
                                </button>

                                <button class="meta-buttons__button saveBtn">
                                    <span class="icon">

                                        <svg class="lucide lucide-save" xmlns="http://www.w3.org/2000/svg" width="16"
                                            height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path
                                                d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
                                            <path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7" />
                                            <path d="M7 3v4a1 1 0 0 0 1 1h7" />
                                        </svg>
                                    </span>
                                    <span class="text"> @lang('Save to Playlist')</span>
                                </button>
                            @endauth
                        </div>
                    </div>
                    <div class="primary__channel">
                        <div class="author">

                            <a class="author__thumb" href="{{ route('preview.channel', $video->user->slug) }}">
                                <img src="{{ getImage(getFilePath('userProfile') . '/' . $video->user->image, isAvatar: true) }}"
                                    alt="image">
                            </a>

                            <div class="author__content">
                                <a href="{{ route('preview.channel', $video->user->slug) }}" class="channel-name">
                                    {{ $video->user->channel_name ? $video->user->channel_name : $video->user->fullname }}
                                </a>
                                <span class="author__subscriber"><span
                                        class="subscriberCount">{{ formatNumber($video->user->subscribers()->count()) }}</span>
                                    @lang('Subscriber')</span>
                            </div>
                        </div>


                        @if (@auth()->id() != $video->user_id)
                            @php
                                $subscribed = $video->user
                                    ->subscribers()
                                    ->where('following_id', auth()->id())
                                    ->exists();
                            @endphp

                            <div class="subscriber-btn">
                                <button
                                    class="btn cta @if (!$subscribed) btn--white subcriberBtn @else  btn--white outline unSubcriberBtn @endif">
                                    @if (!$subscribed)
                                        @lang('Subscribe')
                                        <span class="shape">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </span>
                                    @else
                                        @lang('Unsubscribe')
                                    @endif
                                </button>
                            </div>
                            </section>
                        @endif
                    </div>
                    <div class="primary__desc">
                        <div class="primary__desc-text">
                            @php
                                $descriptionLimit = 100;
                                echo $video->description;
                            @endphp
                        </div>
                        @if (strlen($video->description) > $descriptionLimit)
                            <button class="primary__desc-button">@lang('Show More')</button>
                        @endif
                    </div>

                    <div class="primary__comment d-none d-xl-block">
                        <div class="top">
                            <h5 class="comment-number"><span class="commentCount">{{ count($video->allComments) }}</span>
                                @lang('Comments')</h5>

                            <div class="dropdown comment-sort">
                                <button class="btn btn--sm  d-flex align-items-center gap-2" type="button"
                                    id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="me-1">@lang('Sort by')</span>
                                    <i class="fas fa-sort"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                                    <li><a class="dropdown-item sort-comments" href="javascript:void(0)"
                                            data-sort="top">@lang('Top comments')</a></li>
                                    <li><a class="dropdown-item sort-comments" href="javascript:void(0)"
                                            data-sort="newest">@lang('Newest first')</a></li>
                                    <li><a class="dropdown-item sort-comments" href="javascript:void(0)"
                                            data-sort="oldest">@lang('Oldest first')</a></li>
                                </ul>
                            </div>
                        </div>
                        @if (auth()->check())
                            <div class="comment-form-wrapper">
                                <span class="comment-author">
                                    <img class="fir-image"
                                        src="{{ getImage(getFilePath('userProfile') . '/' . auth()->user()->image, isAvatar: true) }}"
                                        alt="image">
                                </span>

                                <form class="comment-form" method="post">
                                    @csrf
                                    <div class="form-group position-relative">

                                        <textarea class="form--control commentBox" name="comment" placeholder="Add a comment"></textarea>

                                        <button class="comment-btn" type="submit">
                                            <svg class="lucide lucide-send-horizontal" xmlns="http://www.w3.org/2000/svg"
                                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path
                                                    d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z" />
                                                <path d="M6 12h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="primary__comment-list comment-box__content d-none d-xl-block">
                    <div class="comment-bow-wrapper">
                        @include($activeTemplate . 'partials.video.comments')
                    </div>
                </div>
                <div class="text-center spinner mt-4 d-none w-100" id="loading-spinner">
                    <i class="las la-spinner"></i>
                </div>
            </div>
            <div class="secondary">

                @if (@$relatedPlaylistVideos)
                    <div class="card custom--card">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                @if (@$plan)
                                    <h4 class="card-title">@lang('Plan'): {{ __($plan->name) }} @if ($palyPlaylist->title)
                                            / <span class="fs-14">@lang('Playlist-')
                                                {{ __($palyPlaylist->title) }}</span>
                                        @endif
                                    </h4>
                                @else
                                    <h4 class="card-title">{{ __($palyPlaylist->title) }}</h4>
                                @endif
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <p>@lang('videos') - {{ request()->index }}/{{ count($relatedPlaylistVideos) }}</p>
                                    @if (
                                        !@$plan &&
                                            $palyPlaylist->playlist_subscription == Status::YES &&
                                            gs('is_playlist_sell') &&
                                            !@$isPurchased &&
                                            (!auth()->user() || $palyPlaylist->user_id !== auth()->id()))
                                        <p class="price-text">@lang('Price') -
                                            <span>{{ gs('cur_sym') }}{{ showAmount($palyPlaylist->price, currencyFormat: false) }}</span>
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if (!@$plan)
                                @if ($palyPlaylist->playlist_subscription == Status::YES && gs('is_playlist_sell'))
                                    @if (@$isPurchased)
                                        @lang('Purchased')
                                    @elseif(!auth()->user() || $palyPlaylist->user_id !== auth()->id())
                                        <button class="btn btn--base btn--sm premium-stock-text purchase-now btn--purchase"
                                            type="button" data-resource="{{ $palyPlaylist }}">
                                            <span>@lang('Purchase Now')</span>
                                        </button>
                                    @endif
                                @endif
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="secondary__playlist  playlist-releted-card mt-0">

                                @include($activeTemplate . 'partials.video.related_playlist_video', [
                                    'relatedVideos' => $relatedPlaylistVideos,
                                    'playlist' => $palyPlaylist,
                                ])
                            </div>
                        </div>
                    </div>
                @endif


                <div class="tag_sliders owl-carousel">
                    <a class="tag-item" href="{{ route('category.video', 'all') }}">@lang('All')</a>
                    @foreach ($categories as $category)
                        <a class="tag-item"
                            href="{{ route('category.video', $category->slug) }}">{{ __($category->name) }}</a>
                    @endforeach
                </div>

                @if (@$planPlaylists)
                    <div class="card custom--card">
                        <div class="card-header">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <h4 class="card-title mb-0">@lang('Plan'): {{ __($plan->name) }} -
                                    {{ $planPlaylists->count() }} @lang('Playlists')</h4>
                                @if ($palyPlaylist->title)
                                    <a class="see-plan-video-link" href="{{ getPlanVideoUrl($plan) }}">
                                        @lang('See Plan Videos')
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <div class=" playlist-wrapper playlist-releted-card">
                                @include($activeTemplate . 'partials.video.plan_playlist')
                            </div>
                        </div>
                    </div>
                @endif
                <div class="secondary__playlist">
                    @include($activeTemplate . 'partials.video.related_video')
                </div>
            </div>
            <div class="primary__comment d-xl-none d-block mt-5 mb-4">
                <div class="top mb-3">
                    <h5 class="comment-number"><span class="commentCount">{{ count($video->allComments) }}</span>
                        @lang('Comments')</h5>

                    <div class="dropdown comment-sort">
                        <button class="btn btn--sm text-white d-flex align-items-center gap-2" type="button"
                            id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-1">@lang('Sort by')</span>
                            <i class="fas fa-sort"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item sort-comments" href="javascript:void(0)"
                                    data-sort="top">@lang('Top comments')</a></li>
                            <li><a class="dropdown-item sort-comments" href="javascript:void(0)"
                                    data-sort="newest">@lang('Newest first')</a></li>
                            <li><a class="dropdown-item sort-comments" href="javascript:void(0)"
                                    data-sort="oldest">@lang('Oldest first')</a></li>
                        </ul>
                    </div>
                </div>
                @if (auth()->check())
                    <div class="primary__comment">
                        <div class="comment-form-wrapper">
                            <span class="comment-author">
                                <img class="fir-image"
                                    src="{{ getImage(getFilePath('userProfile') . '/' . auth()->user()->image, isAvatar: true) }}"
                                    alt="image">
                            </span>

                            <form class="comment-form" method="post">
                                @csrf
                                <div class="form-group position-relative">
                                    <textarea class="form--control" name="comment" placeholder="Add a comment"></textarea>
                                    <button class="comment-btn" type="submit">
                                        <svg class="lucide lucide-send-horizontal" xmlns="http://www.w3.org/2000/svg"
                                            width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path
                                                d="M3.714 3.048a.498.498 0 0 0-.683.627l2.843 7.627a2 2 0 0 1 0 1.396l-2.842 7.627a.498.498 0 0 0 .682.627l18-8.5a.5.5 0 0 0 0-.904z" />
                                            <path d="M6 12h16" />
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

            </div>
            <div class="primary d-xl-none d-block comment-box__content">
                <div class="comment-bow-wrapper">
                    @include($activeTemplate . 'partials.video.comments')
                </div>
            </div>
        </div>
    </div>


    {{-- all modal --}}

    @include($activeTemplate . 'partials.play_video_page_modal')

    {{-- login modal --}}
    @include($activeTemplate . 'partials.login_alert_modal')






@endsection

@push('style')
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/play-video.css') }}">
@endpush

@push('style-lib')
    <link href="{{ asset('assets/global/css/plyr.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            let itemPrice = 0;
            let amount = parseFloat($('.amount').val() || 0);
            $(document).on('click', 'button.cta', function() {
                $(this).addClass('active');
                setTimeout(() => {
                    $(this).removeClass('active');
                }, 300);
            });

            $(document).ready(function() {
                $('.primary__desc-button').on('click', function() {
                    var descText = $('.primary__desc-text');
                    if (descText.hasClass('expanded')) {
                        descText.removeClass('expanded').css('max-height', '100px');
                        $(this).text('@lang('Show More')');
                    } else {
                        var scrollHeight = descText.prop('scrollHeight');
                        descText.addClass('expanded').css('max-height', scrollHeight + 'px');
                        $(this).text('@lang('Show Less')');
                    }
                });
            });


            $(document).ready(function() {
                $(document).on('input', '.commentBox', function() {
                    $(this).css('height', 'auto');
                    $(this).css('height', this.scrollHeight + 'px');

                });
            });


            const auth = "{{ auth()->user() }}";
            $('.submitBtn').on('click', function(e) {
                e.preventDefault();
                const url = "{{ route('user.video.add.playlist') }}";
                const formData = $('.add-video-form').serialize();
                $.ajax({
                    type: "POST",
                    url: url,
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        $('#addVideoModal').modal('hide');


                        if (response.error) {
                            notify('error', response.error)
                        } else {
                            notify('success', response.success)
                        }
                    }

                });
            });


            $(document).ready(function() {
                // for vidoe player
                const stockVideo = "{{ $video->stock_video }}";
                const purchasedTrue = "{{ $purchasedTrue }}"
                const authVideo = "{{ $video->user_id == auth()->id() }}"

                var controls = [];
                if (stockVideo == 0 || purchasedTrue || authVideo) {
                    controls = [
                        'rewind',
                        'play',
                        'fast-forward',
                        'progress',
                        'current-time',
                        'duration',
                        'mute',
                        'settings',
                        'fullscreen',
                        'pip',

                    ];
                } else if (!auth) {
                    controls = [
                        'play-large',
                    ];
                    $(document).on('click', '.plyr__control--overlaid, .primary__videoPlayer ', function() {
                        singleplayer.pause();
                        $('#existModalCenter').modal('show');
                    });

                } else if (stockVideo == 1 && !purchasedTrue || authVideo) {
                    controls = [
                        'play-large',
                    ];
                    $(document).on('click', '.plyr__control--overlaid, .primary__videoPlayer ', function() {
                        itemPrice = Number($(this).data('price'));
                        amount = itemPrice;
                        singleplayer.pause();
                        const modal = $('#paymentConfirmationModal');
                        modal.find('[name=amount]').val(itemPrice);
                        modal.find('.modal-title').text('Purchase this video to access its content');
                        modal.find('.item-name').text($(this).data('item_name'));
                        modal.find('.item-price').text(`${itemPrice} {{ gs('cur_text') }}`);
                        modal.find('[name=playlist_id]').val(0);
                        modal.find('[name=video_id]').val($(this).data('video-id'));
                        calculation();
                        modal.modal('show')
                    });
                }

                const singleplayer = new Plyr('.video-player', {
                    controls,
                    ratio: '16:9',
                    autoplay: true,



                });


                const loader = document.getElementById('loader');


                $(document).ready(function() {
    // Attempt to autoplay with sound
    const playPromise = singleplayer.play();
    
    if (playPromise !== undefined) {
        playPromise.then(() => {
            // Autoplay with sound succeeded
            singleplayer.muted = false;
            console.log('Autoplay with sound succeeded');
        }).catch(error => {
            // Autoplay with sound was blocked, try muted autoplay
            console.log('Autoplay with sound blocked, playing muted');
            singleplayer.muted = true;
            singleplayer.play().then(() => {
                // Show unmute button after muted autoplay starts
                showUnmuteButton();
            }).catch(err => {
                console.error('Autoplay failed entirely:', err);
            });
        });
    }
    
    const palyPlaylist = @json(!blank($palyPlaylist));
    const relatedVideo = @json(@$relatedVideos[0]);

    singleplayer.once('ended', function() {
        if (palyPlaylist) {
            const currentIndex = "{{ request()->index }}";
            const index = parseInt(currentIndex);
            const relatedPlaylistVideos = @json($relatedPlaylistVideos);

            if (index - 1 < relatedPlaylistVideos.length) {
                const nextVideo = relatedPlaylistVideos[index];

                if (`{{ $plan && $plan->count() > 0 }}`) {
                    if (`{{ !@$palyPlaylist->title }}`) {
                        window.location.href =
                            "{{ route('video.play', ['', '']) }}/" +
                            nextVideo.id + "/" + nextVideo.slug +
                            "?plan={{ @$plan->slug }}&index=" + (index + 1);
                    } else {
                        window.location.href =
                            "{{ route('video.play', ['', '']) }}/" +
                            nextVideo.id + "/" + nextVideo.slug +
                            "?list={{ @$palyPlaylist->slug }}&index=" + (
                                index + 1) + "&plan={{ @$plan->slug }}";
                    }
                } else {
                    window.location.href =
                        "{{ route('video.play', ['', '']) }}/" +
                        nextVideo.id + "/" + nextVideo.slug +
                        "?list={{ @$palyPlaylist->slug }}&index=" + (index + 1);
                }
            }
        } else {
            if (relatedVideo && Array(relatedVideo).length > 0) {
                window.location.href = "{{ route('video.play', ['', '']) }}/" +
                    relatedVideo?.id + "/" + relatedVideo?.slug;
            }
        }
    });
});


                let adPlayer = ''

                function adVideoPlayer() {
                    adPlayer = new Plyr('.ad-player', {
                        controls: [

                        ],
                        ratio: '16:9',
                    });
                }

                $(document).ready(function() {
                    let adTriggers = @json($adsDurations).map(Number);
                    let currentAdIndex = 0;
                    let adPlaying = false;

                    let requestPending = false;
                    let adVideo = $('.adVideo');
                    let slug = "{{ $video->slug }}"

                    function playAd(response) {


                        const adId = response.data.ad_id;
                        const encryptedVideoId = "{{ encrypt(@$video->id) }}";
                        adPlaying = true;
                        singleplayer.pause();
                        $('.mainVideo').addClass('d-none');
                        adVideo.html(`
                                <video class="ad-player" playsinline  controls>
                                    <source src="${response.data.ad_video_src}" type="video/mp4" />
                                </video>
                                    ${(response.data.ad_type == 2 || response.data.ad_type == 3 || response.data.is_clickable == 1) ?
                                    `
                                        <div class="ad-info"><div class="ad-info__thumb"><img src="${response.data.ad_logo}">
                                            </div><div class="ad-info__content"><p>${response.data.ad_url}</p>
                                            <a href="{{ route('redirect.ad', ['', '']) }}/${adId}/${encryptedVideoId}" class="text-white" target="_blank" >${response.data.button_label}</a>
                                            </div></div>` : '' }
                                        ${
                                        response?.data?.ad_type != 3 ?
                                        `<button class="skip-btn btn btn--base btn--sm ad-btn" type="button"></button>` : ''
                                    }   
                                `);


                        adVideo.removeClass('d-none');
                        adVideoPlayer();
                        adPlayer.play();
                        adPlayer.on('timeupdate', function() {
                            const adDuration = 5;
                            const currentAdTime = Math.floor(adPlayer.currentTime);
                            let remainingTime = adDuration - currentAdTime;
                            if (remainingTime > 0) {
                                $('.skip-btn').attr('disabled', true).removeClass('d-none');
                                $('.skip-btn').text(`Skip in ${remainingTime} seconds`)
                                    .removeClass('btn--base');
                            } else {
                                $('.skip-btn').attr('disabled', false).addClass('skipAd')
                                    .addClass('btn--base');
                                $('.skip-btn').text('Skip');
                            }
                        });

                        adPlayer.once('ended', function() {
                            adPlayer.pause();
                            adVideo.addClass('d-none');
                            adVideo.empty();
                            $('.mainVideo').removeClass('d-none');
                            singleplayer.play();
                            adPlaying = false;
                        });
                    }

                    function requestAd() {
                        requestPending = true;
                        $.ajax({
                            type: "get",
                            url: "{{ route('fetch.ad') }}",
                            data: {
                                video_id: "{{ encrypt($video->id) }}"
                            },
                            dataType: "json",
                            success: function(response) {
                                if (response.status == 'success') {
                                    playAd(response);
                                }
                            },
                            complete: function() {
                                requestPending = false;
                            }
                        });
                    }

                    function checkAdTrigger() {
                        const currentTime = Math.floor(singleplayer.currentTime);
                        if (!adPlaying && !requestPending && adTriggers.includes(currentTime)) {
                            requestAd();
                            adTriggers.splice(adTriggers.indexOf(currentTime), 1);
                        }
                    }

                    let debounceTimer;
                    singleplayer.on('timeupdate', function() {

                        // Show loader when video is buffering
                        singleplayer.on('waiting', () => {
                            loader.style.display = 'block';
                        });

                        // Hide loader when playback starts or resumes
                        singleplayer.on('playing', () => {
                            loader.style.display = 'none';
                        });

                        // Hide loader on video end
                        singleplayer.on('ended', () => {
                            loader.style.display = 'none';
                        });


                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(checkAdTrigger, 100);

                    });

                    $(document).on('click', '.skipAd', function() {
                        adPlayer.pause();
                        $('.adVideo').addClass('d-none');
                        $('.primary_ad_player').empty();
                        $('.mainVideo').removeClass('d-none');
                        singleplayer.play();
                        adPlaying = false;
                    })
                });

                const players = Plyr.setup('.related-video-player', {
                    controls: [],
                    ratio: '16:9',
                    muted: true,
                });



                const audience = "{{ $video->audience }}"
                if (audience == 0) {
                    if (stockVideo == 0 || purchasedTrue || authVideo) {
                        singleplayer.play();
                    }

                }

                $('.SeeBtn').on('click', function() {
                    $('.hidden-content').addClass('d-none');
                    singleplayer.play();
                });



                $('.reactionBtn').on('click', function() {
                    if (!auth) {
                        $('#existModalCenter').modal('show');
                        return;
                    }
                    const value = $(this).data('reaction');
                    const button = $(this);

                    $.ajax({
                        type: "post",
                        url: "{{ route('user.reaction', $video->id) }}",
                        dataType: "json",
                        data: {
                            is_like: value,
                        },
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            const likeButton = $('.reactionBtn[data-reaction="1"]');
                            const dislikeButton = $('.reactionBtn[data-reaction="0"]');
                            const likeIcon = likeButton.find('.reactionIcon');
                            const dislikeIcon = dislikeButton.find('.reactionIcon');


                            if (response.remark == 'like') {
                                likeIcon.removeClass('vti-like').addClass('vti-like-fill');
                                $('.likeCount').text(response.data.like_count);

                                dislikeIcon.removeClass('vti-dislike-fill').addClass(
                                    'vti-dislike');

                            } else if (response.remark == 'like_remove') {
                                likeIcon.removeClass('vti-like-fill').addClass('vti-like');
                                $('.likeCount').text(response.data.like_count);

                            } else if (response.remark == 'dislike') {
                                dislikeIcon.removeClass('vti-dislike').addClass(
                                    'vti-dislike-fill');
                                likeIcon.removeClass('vti-like-fill').addClass('vti-like');
                                $('.likeCount').text(response.data.like_count);

                            } else if (response.remark == 'dislike_remove') {
                                dislikeIcon.removeClass('vti-dislike-fill').addClass(
                                    'vti-dislike');

                            } else if (response.status == 'status') {
                                notify('error', response.message.error);
                            } else {
                                notify('error', 'Failed to update reaction');
                                return;
                            }

                        }
                    });
                });
                // end reacrtion
            });


            // for subscribe

            $(document).on('click', '.unSubcriberBtn', function() {
                $('#unSubcriberModal').modal('show');
            });


            $(document).on('click', '.confirmUnsubscribe', function() {
                subscribers();
                $('#unSubcriberModal').modal('hide');
            });


            $(document).on('click', '.subcriberBtn', function() {
                subscribers();
            });




            function subscribers() {

                if (!auth) {
                    $('#existModalCenter').modal('show');
                    return;
                }

                $.ajax({
                    type: "post",
                    url: "{{ route('user.subscribe.channel', $video->user_id) }}",
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    success: function(response) {


                        $('.subscriberCount').text(response.data.subscriber_count);

                        if (response.remark === 'subscribed') {
                            $('.subscriber-btn').html(`
                  <button class="btn btn--white outline unSubcriberBtn"> @lang('Unsubscribe')</button> `)

                        } else if (response.remark === 'unsubscribe') {
                            $('.subscriber-btn').html(`
                 <button class="btn cta btn--white  subcriberBtn">@lang('Subscribe')
                                        <span class="shape">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </span></button>
                                    `)
                        } else {
                            notify('error', response.message);
                        }
                    }

                });
            }

            // end subscribe

            // for watch later

            $('.watchLater').on('click', function() {
                if (!auth) {
                    $('#existModalCenter').modal('show');
                    return;
                }
                var button = $(this);

                $.ajax({
                    type: "post",
                    url: "{{ route('user.watch.later', $video->id) }}",
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.remark == 'add_watch_later') {
                            button.find('.icon').html(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-check-big">
                        <path d="M21 10.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12.5" />
                        <path d="m9 11 3 3L22 4" />
                    </svg>
                `);
                        } else if (response.remark == 'watch_later_remove') {
                            button.find('.icon').html(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                `);
                        }
                    }
                });
            });


            // end watch later

            // for share


            $('.shareBtn').on('click', function() {
                $('#shareModal').modal('show');
            });



            $(document).on('click', '.copyBtn', function(e) {

                var input = $(this).parent('.share-embed').find('.copyText');
                if (input && input.select) {
                    input.select();
                    try {
                        document.execCommand('SelectAll')
                        document.execCommand('Copy', false, null);
                        input.blur();
                        notify('success', `Copied successfully`);
                    } catch (err) {
                        alert('Please press Ctrl/Cmd + C to copy');
                    }
                }
            });

            // end share


            // for comment
            let currentPage = 1;

            let lastPage = false;

            let currentSort = 'newest';

            let isLoading = false;

            $('.dropdown-menu .sort-comments').on('click', function() {
                const sortBy = $(this).data('sort');
                currentSort = sortBy;
                currentPage = 1;
                lastPage = false;
                isLoading = false;

                $('.sort-comments').removeClass('active');
                $(this).addClass('active');

                $('.comment-box__content').empty();
                $('#loading-spinner').removeClass('d-none');

                loadMoreComments();

            });

            $('.comment-form').on('submit', function(e) {
                e.preventDefault();

                if (!auth) {
                    $('#existModalCenter').modal('show');
                    return;
                }

                $.ajax({
                    type: "post",
                    url: "{{ route('user.comment.submit', $video->id) }}",
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

            $('.comment-box__content').on('scroll', function() {
                if (isLoading) return;
                let commentBox = $(this);
                let scrollTop = commentBox.scrollTop();
                let boxHeight = commentBox.outerHeight();
                let contentHeight = commentBox[0].scrollHeight;
                if (scrollTop + boxHeight >= contentHeight - 2 && !lastPage) {
                    currentPage++;
                    loadMoreComments();
                }
            });

            function loadMoreComments() {

                if (isLoading) return;
                isLoading = true;

                const commentsRoute = "{{ route('user.comment.get', $video->id) }}";
                $('#loading-spinner').removeClass('d-none');
                $.ajax({
                    url: `${commentsRoute}?page=${currentPage}&sort_by=${currentSort}`,
                    type: 'GET',
                    success: function(response) {
                        $('#loading-spinner').addClass('d-none');
                        if (response.status == 'success') {
                            $('.comment-box__content').append(response.data.commentHtml);
                            $('.commentCount').text(response.data.comment_count);
                            if (currentPage >= response.data.last_page) {
                                lastPage = true;
                            }
                        } else {
                            notify('error', response.message.error);
                        }
                    },
                    complete: function() {
                        isLoading = false;
                    }
                });
            }

            $(document).on('click', '.reply', function() {
                const replyForm = $(this).closest('.comment-box-item__content').find('.reply-form').first();
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


            // for reaction
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
                            button.find('.reactionIcon').removeClass('vti-like').addClass(
                                'vti-like-fill');
                            button.siblings('.commentReaction').find('.reactionIcon').removeClass(
                                'vti-dislike-fill').addClass('vti-dislike');
                            button.find('.likeCount').text(response.data.like_count);

                        } else if (response.remark === 'like_remove') {
                            button.find('.reactionIcon').removeClass('vti-like-fill').addClass(
                                'vti-like');
                            button.find('.likeCount').text(response.data.like_count);

                        } else if (response.remark === 'dislike') {
                            button.find('.reactionIcon').removeClass('vti-dislike').addClass(
                                'vti-dislike-fill');
                            button.siblings('.commentReaction').find('.reactionIcon').removeClass(
                                'vti-like-fill').addClass('vti-like');
                            button.siblings('.commentReaction').find('.likeCount').text(response
                                .data.like_count);

                        } else if (response.remark === 'dislike_remove') {
                            button.find('.reactionIcon').removeClass('vti-dislike-fill').addClass(
                                'vti-dislike');

                        } else if (response.remark === 'video_not_found') {
                            notify('error', response.message.error);
                        } else {
                            notify('error', 'Failed to update reaction');
                        }

                    }
                });
            });
            // end comment

            //embed

            $('.embed').on('click', function() {
                $('#embedModal').modal('show');
            });
            $('.saveBtn').on('click', function() {
                const modal = $('#addVideoModal');
                modal.find('.modal-title').text('Playlists');
                modal.modal('show')
            });

            if (!auth) {
                $(document).on('click', '.purchase-now', function(e) {
                    $('#existModalCenter').modal('show');
                });

            } else {

                $(document).on('click', '.purchase-now', function(e) {
                    e.preventDefault();
                    const modal = $('#paymentConfirmationModal');
                    const playlist = $(this).data('resource');
                    modal.find('[name=playlist_id]').val(playlist.id);
                    modal.find('[name=video_id]').val(0);
                    modal.find('.modal-title').text('Purchase this playlist to access its content');
                    modal.find('input[name="amount"]').val(parseFloat(playlist.price).toFixed(2)).trigger(
                        'input');
                    modal.find('.item-price').text(`${parseFloat(playlist.price)} {{ gs('cur_text') }}`);
                    modal.find('.item-name').text(`${playlist.title}`);
                    calculation();
                    modal.modal('show');
                });
            }

            var gateway, minAmount, maxAmount;

            $('.amount').on('input', function(e) {
                amount = parseFloat($(this).val());
                if (!amount) {
                    amount = 0;
                }
                calculation();
            });


            $('.gateway-input').on('change', function(e) {
                gatewayChange();
            });

            function gatewayChange() {
                let gatewayElement = $('.gateway-input:checked');
                let methodCode = gatewayElement.val();

                gateway = gatewayElement.data('gateway');
                minAmount = gatewayElement.data('min-amount');
                maxAmount = gatewayElement.data('max-amount');

                let processingFeeInfo =
                    `${parseFloat(gateway?.percent_charge).toFixed(2)}% with ${parseFloat(gateway?.fixed_charge).toFixed(2)} {{ __(gs('cur_text')) }} charge for payment gateway processing fees`
                $(".proccessing-fee-info").attr("data-bs-original-title", processingFeeInfo);
                calculation();
            }

            gatewayChange();

            $(".more-gateway-option").on("click", function(e) {
                let paymentList = $(".gateway-option-list");
                paymentList.find(".gateway-option").removeClass("d-none");
                $(this).addClass('d-none');
                paymentList.animate({
                    scrollTop: (paymentList.height() - 60)
                }, 'slow');
            });


            function calculation() {
                if (!gateway) return;
                $(".gateway-limit").text(minAmount + " - " + maxAmount);

                let percentCharge = 0;
                let fixedCharge = 0;
                let totalPercentCharge = 0;

                if (amount) {
                    percentCharge = parseFloat(gateway?.percent_charge);
                    fixedCharge = parseFloat(gateway?.fixed_charge);
                    totalPercentCharge = parseFloat(amount / 100 * percentCharge);
                }

                let totalCharge = parseFloat(totalPercentCharge + fixedCharge);
                let totalAmount = parseFloat((amount || 0) + totalPercentCharge + fixedCharge);

                $(".final-amount").text(totalAmount.toFixed(2));
                $(".processing-fee").text(totalCharge.toFixed(2));
                $("input[name=currency]").val(gateway.currency);
                $(".gateway-currency").text(gateway.currency);

                if (amount < Number(gateway.min_amount) || amount > Number(gateway.max_amount)) {
                    $(".deposit-form button[type=submit]").attr('disabled', true);
                } else {
                    $(".deposit-form button[type=submit]").removeAttr('disabled');
                }

                if (gateway.currency != "{{ gs('cur_text') }}" && gateway.method.crypto != 1) {
                    $('.deposit-form').addClass('adjust-height')

                    $(".gateway-conversion, .conversion-currency").removeClass('d-none');
                    $(".gateway-conversion").find('.deposit-info__input .text').html(
                        `1 {{ __(gs('cur_text')) }} = <span class="rate">${parseFloat(gateway.rate).toFixed(2)}</span>  <span class="method_currency">${gateway.currency}</span>`
                    );
                    $('.in-currency').text(parseFloat(totalAmount * gateway.rate).toFixed(gateway.method.crypto == 1 ?
                        8 : 2))
                } else {
                    $(".gateway-conversion, .conversion-currency").addClass('d-none');
                    $('.deposit-form').removeClass('adjust-height')
                }

                if (gateway.method.crypto == 1) {
                    $('.crypto-message').removeClass('d-none');
                } else {
                    $('.crypto-message').addClass('d-none');
                }
            }

            // Function to show unmute button
            function showUnmuteButton() {
                const unmuteBtn = $('<button>', {
                    class: 'unmute-overlay-btn',
                    html: '<i class="fas fa-volume-up"></i> Tap to Unmute',
                    css: {
                        position: 'absolute',
                        bottom: '80px',
                        right: '20px',
                        zIndex: 999,
                        padding: '12px 24px',
                        background: 'rgba(0, 0, 0, 0.85)',
                        color: 'white',
                        border: '2px solid white',
                        borderRadius: '25px',
                        cursor: 'pointer',
                        fontSize: '14px',
                        fontWeight: 'bold',
                        backdropFilter: 'blur(10px)'
                    }
                });
                
                $('.primary__videoPlayer').append(unmuteBtn);
                
                unmuteBtn.on('click', function() {
                    singleplayer.muted = false;
                    singleplayer.volume = 1;
                    $(this).fadeOut(300, function() { $(this).remove(); });
                });
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    unmuteBtn.fadeOut(300, function() { $(this).remove(); });
                }, 5000);
            }

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
            $('.gateway-input').change();
        })(jQuery);
    </script>
@endpush
