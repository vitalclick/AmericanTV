@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="home-body">
        <div class="wh-page-header home-body__item">
            <h3 class="page-title">{{ __($pageTitle) }}</h3>
        </div>

        @if (!blank($videosHistories))
            <div class="wh-search-clear">
                <button class="wh-sm-search"><i class="vti-search"></i></button>
                <form class="watch-history-search">
                    <div class="form-group">
                        <input class="form--control" name="search" type="text" value="{{ request()->search }}"
                            placeholder="Search watch history">

                        <button class="btn" type="submit"><i class="vti-search"></i></button>
                    </div>
                </form>
                <button class="clear-history-btn confirmationBtn" data-action="{{ route('user.remove.all.history') }}"
                    data-question="@lang('Are you sure you want to remove all history')?"><i class="vti-trash"></i>
                    <span class="text">@lang('Remove all watch history')</span>
                </button>
            </div>
        @endif

        @forelse ($videosHistories as $videosHistory)
            <div class="video-wh-item">
                <a  data-video_id="{{ $videosHistory->video->id }}" class="video-wh-item__thumb   @if ($videosHistory->video->showEligible()) autoPlay @endif"
                    href="{{ route('video.play', [$videosHistory->video->id, $videosHistory->video->slug]) }}">
                    @if ($videosHistory->video->showEligible())
                        <video class="video-player"
                            data-poster="{{ getImage(getFilePath('thumbnail') . '/thumb_' . $videosHistory->video->thumb_image) }}"
                            controls>

                        </video>
                       @include('Template::partials.video.video_loader')
                    @else
                        <img src="{{ getImage(getFilePath('thumbnail') . '/thumb_' . $videosHistory->video->thumb_image) }}"
                            alt="@lang('video_thumb')">
                        <span
                            class="video-item__price">{{ gs('cur_sym') }}{{ showAmount($videosHistory->video->price, currencyFormat: false) }}</span>
                    @endif
                </a>

                <div class="video-wh-item__content">
                    <div class="video-wh-item__left">
                        <h5 class="video-wh-item__title mb-0">
                            <a
                                href="{{ route('video.play', [$videosHistory->video->id, $videosHistory->video->slug]) }}">{{ __($videosHistory->video->title) }}</a>
                        </h5>
                        <a class="video-wh-item__channel"
                            href="{{ route('preview.channel', $videosHistory->video->user->slug) }}">{{ __($videosHistory->video->user->channel_name) }}</a>
                        <span class="video-wh-item__view">{{ formatNumber($videosHistory->video->views) }}
                            @lang('views')</span>
                    </div>
                    <div class="video-wh-item__action">
                        <button class="ellipsis-list__btn confirmationBtn"
                            data-action="{{ route('user.remove.history', $videosHistory->id) }}"
                            data-question="@lang('Are you sure you want to remove this history')?">

                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-x">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                        <button class="ellipsis-list__btn shareBtn " data-video="{{ $videosHistory->video }}"
                            data-url="{{ route('video.play', [$videosHistory->video->id, $videosHistory->video->slug]) }}"
                            type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-share-2">
                                <circle cx="18" cy="5" r="3" />
                                <circle cx="6" cy="12" r="3" />
                                <circle cx="18" cy="19" r="3" />
                                <line x1="8.59" x2="15.42" y1="13.51" y2="17.49" />
                                <line x1="15.41" x2="8.59" y1="6.51" y2="10.49" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-container">
                @include('Template::partials.empty')
            </div>
        @endforelse
        {{ paginateLinks($videosHistories) }}
    </div>

    @include('Template::partials.share')

    <x-confirmation-modal frontend='true' />
@endsection

@push('style-lib')
    <!-- Slick Slider -->
    <link href="{{ asset($activeTemplateTrue . 'css/slick.css') }}" rel="stylesheet">
    <!-- Owl Carousel -->
    <link href="{{ asset($activeTemplateTrue . 'css/owl.theme.default.min.css') }}" rel="stylesheet">
    <link href="{{ asset($activeTemplateTrue . 'css/owl.carousel.min.css') }}" rel="stylesheet">

    <link href="{{ asset('assets/global/css/plyr.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <!-- Owl Carousel js -->
    <script src="{{ asset($activeTemplateTrue . 'js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset($activeTemplateTrue . 'js/owl.carousel.filter.js') }}"></script>

    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
@endpush
@push('script')
    <script>
        (function($) {
            'use strict';

            $(document).ready(function() {

                const controls = [
                   
                ];
                const players = Plyr.setup('.video-player', {
                    controls,
                    ratio: '16:9',
                    muted: true
                });


            });

            $('.confirmationBtn').on('click', function() {
                const modal = $('#confirmationModal');
                const action = $(this).data('action');
                const question = $(this).data('question');
                modal.find('.question').text(question);
                modal.find('form').attr('action', action);
                modal.modal('show')
            });

            $('.shareBtn').on('click', function() {
                const video = $(this).data('video');
                const url = $(this).data('url');

                let shareLink = `
        <a class="share-item whatsapp" href="https://api.whatsapp.com/send?text=${encodeURIComponent(url)}" target="_blank">
            <i class="lab la-whatsapp"></i>
        </a>
        <a class="share-item facebook" href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}" target="_blank">
            <i class="lab la-facebook-f"></i>
        </a>
        <a class="share-item twitter" href="https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(video.title)}" target="_blank">
            <i class="fa-brands fa-x-twitter"></i>
        </a>
        <a class="share-item envelope" href="mailto:?subject=${encodeURIComponent(video.title)}&body=${encodeURIComponent(url)}">
            <i class="las la-envelope"></i>
        </a>
    `;

                $('#shareModal').find('.share-items').html(shareLink);
                $('.copyText').val(url);
                $('#shareModal').modal('show');
            });


        })(jQuery);
    </script>
@endpush
