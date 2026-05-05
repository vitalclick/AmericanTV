@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="home-body">
        @if (!blank($trendingVideos))
            <x-home-body-title icon="vti-top" title="Trending" />
            <section class="trending-section">
                <div class="video-item-wrapper">
                    @if ($inFeedAd && gs('ads_module'))
                        @php
                            $video = $inFeedAd->video;
                            if ($video) {
                                $videoFile = $video->videoFiles()->first();
                            }
                        @endphp
                        <div class="video-item">
                            <a class="video-item__thumb"
                                @if (@$video) target="_blank" href="{{ route('redirect.ad', $inFeedAd->id) }}" @endif>
                                <video class="video-player" controls playsinline>
                                    @if ($video)
                                        <source src="{{ getVideo($videoFile->file_name, $video) }}" type="video/mp4" />
                                    @else
                                        <source src="{{ getAd($inFeedAd->ad_file, $inFeedAd) }}" type="video/mp4" />
                                    @endif
                                </video>
                                @include('Template::partials.video.video_loader')
                            </a>
                            <div class="video-item__content">
                                @if ($inFeedAd->is_clickable)
                                    <div class="video-item__channel-author">
                                        <img class="fit-image"
                                            src="{{ getImage(getFilePath('adLogo') . '/' . $inFeedAd->logo) }}"
                                            alt="logo">
                                    </div>
                                @endif

                                <h5 class="title">
                                    {{ __($inFeedAd->title) }}
                                </h5>
                                @if($inFeedAd->is_clickable)
                                <div class="meta">
                                    <span>@lang('Sponsored'): {{ $inFeedAd->url }} </span>
                                </div>
                                @endif
                                <div class="meta d-flex justify-content-between gap-3">

                                    @if (@$video)
                                        <a class="action-btn info-btn w-100 text-center"
                                            href="{{ route('video.play', [$video->id, $video->slug]) }}">@lang('Watch')</a>
                                    @endif

                                    @if ($inFeedAd->is_clickable)
                                        <a class="action-btn active-btn w-100 text-center" target="_blank"
                                            href="{{ route('redirect.ad', $inFeedAd->id) }}">@lang('Visit Site')</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    @include($activeTemplate . 'partials.video.video_list', ['videos' => $trendingVideos])
                </div>
            </section>
        @endif

        @if (!blank($shortVideos))
            <x-home-body-title icon="vti-short" title="Shorts" />
            <section class="shorts-section">
                <div class="row gy-4">
                    <div class="col-lg-12">
                        <div class="short_slider owl-carousel">
                            @include($activeTemplate . 'partials.video.shorts_list', [
                                'shortVideos' => $shortVideos,
                            ])
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if (!blank($videos))
            <x-home-body-title icon="vti-video" title="Videos" />

            <div class="video-wrapper">
                @include($activeTemplate . 'partials.video.video_list', ['videos' => $videos])
            </div>
            <div class="text-center d-none spinner mt-4 w-100" id="loading-spinner">
                <i class="las la-spinner"></i>
            </div>
        @endif

        @if (blank($trendingVideos) && blank($shortVideos) && blank($videos))
            <div class="empty-container ">
                @include('Template::partials.empty')
            </div>
        @endif


    </div>
@endsection

@push('style')
    <style>
        .spinner {
            text-align: center;
            margin-top: 20px;
        }

        .spinner i {
            font-size: 45px;
            color: #ff0000;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/plyr.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
@endpush

@push('script')
    <script>
        'use strict';

        const controls = [];

        $(document).ready(function() {
            playersInitiate()
        });

        function playersInitiate() {
            const players = Plyr.setup('.video-player', {
                controls,
                ratio: '16:9',
                muted: true,
            });
        }

        $(document).ready(function() {
            const shortPlayers = Plyr.setup('.shorts-video-player', {
                controls,
                ratio: '9:16',
                muted: true,
            });
        });

        let currentPage = "{{ $videos->currentPage() }}";
        let url = "{{ route('video.get') }}";

        $(window).scroll(function() {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 5 && !lastPage) {
                currentPage++;
                loadMoreVideos(url, currentPage);
            }
        });
    </script>
@endpush
