@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="card custom--card">
            <div class="card-header">
                <h3 class="card-title">{{ __($pageTitle) }}</h3>
            </div>
            <div class="card-body">
                @if (!blank($videos))
                    <div class="dashboard-video">
                        @foreach ($videos as $video)
                            <div class="video-item">
                                <a data-video_id="{{ $video->id }}" class="video-item__thumb  autoPlay"
                                    href="{{ route('video.play', [$video->id, $video->slug]) }}" target="__blank">
                                    <video class="video-player"  controls
                                        @if ($video->thumb_image) data-poster="{{ getImage(getFilePath('thumbnail') . '/' . $video->thumb_image) }}" @endif>

                                    </video>
                                    @include('Template::partials.video.video_loader')
                                </a>
                                <div class="video-item__content">
                                    <div class="d-flex justify-content-between gap-3 mb-3">
                                        <p class="video-status-badge">
                                            @php
                                                echo $video->statusBadge;
                                            @endphp
                                        </p>
                                        <div class="video-item__manage">
                                            <a class="video-item__edit"
                                                href="{{ route('user.video.edit', encrypt(@$video->id)) }}"><i
                                                    class="las la-edit"></i></a>

                                            <a class="video-item__edit @if ($video->status != Status::PUBLISHED) disabled-link @endif  "
                                                href="{{ route('user.ad.setting', @$video->slug) }}"><i
                                                    class="las la-ad"></i></a>
                                            <a class="video-item__edit @if ($video->status != Status::PUBLISHED) disabled-link @endif  "
                                                href="{{ route('user.video.analytics', @$video->slug) }}"><i
                                                    class="las la-chart-pie"></i></a>

                                        </div>

                                    </div>


                                    <h5 class="title">
                                        <a
                                            href="{{ route('video.play', [$video->id, $video->slug]) }}">{{ __($video->title) }}</a>
                                    </h5>

                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="meta">
                                            <span class="view">{{ formatNumber($video->views) }} @lang('views')</span>
                                            <span
                                                class="like">{{ formatNumber($video->userReactions()->like()->count()) }}
                                                @lang('Likes')</span>
                                        </div>
                                        <span class="fs-12 fw-bold">
                                            @php
                                                echo $video->visibilityStatus;
                                            @endphp
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="row py-60">
                        <div class="empty-container empty-card-two">
                            @include('Template::partials.empty')
                        </div>
                    </div>
                @endif
                @if ($videos->hasPages())
                    {{ paginateLinks($videos) }}
                @endif
            </div>
        </div>
    </div>
@endsection


@push('style')
    <style>
        .disabled-link {
            pointer-events: none;
            cursor: not-allowed;
            color: #6c757d;
            /* Bootstrap's disabled color */
            text-decoration: none;
            /* Remove underline if needed */
        }
    </style>
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

            $(document).ready(function() {

                const controls = [
                  
                ];
                const players = Plyr.setup('.video-player', {
                    controls,
                    ratio: '16:9',

                });




            });


        })(jQuery);
    </script>
@endpush
