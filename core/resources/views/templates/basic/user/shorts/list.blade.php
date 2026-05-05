@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="card custom--card">
            <div class="card-header">
                <h5 class="card-title">{{ __($pageTitle) }}</h5>
            </div>
            <div class="card-body">
                @if (!blank($shorts))
                    <div class="dashboard-video">
                        @foreach ($shorts as $video)
                            <div class="video-item">
                                <a class="video-item__thumb playModal shortsAutoPlay" href="{{ route('short.play', [$video->id, $video->slug]) }}"
                                   target="__blank">
                                    <video class="shorts-video-player" controls>
                                        <source src="{{ getVideo($video->video, $video) }}"
                                                type="video/mp4" />
                                    </video>
                                </a>
                                <div class="video-item__manage mt-3 me-3">
                                    <a class="video-item__edit" href="{{ route('user.shorts.edit', $video->id) }}"><i
                                           class="las la-edit"></i></a>
                                </div>
                                <div class="video-item__content">
                                    <h5 class="title">
                                        <a href="{{ route('video.play', [$video->id, $video->slug]) }}">{{ __($video->title) }}</a>
                                    </h5>
                                    <div class="meta d-flex justify-content-between ">
                                        <div>
                                            <span class="view">{{ formatNumber($video->views) }} @lang('views')</span>
                                            <span
                                                  class="like">{{ formatNumber($video->userReactions()->like()->count()) }}
                                                @lang('Likes')</span>
                                        </div>
                                        <div>
                                            @php
                                                echo $video->statusBadge;
                                            @endphp
                                        </div>
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
                @php
                    echo paginateLinks($shorts);
                @endphp
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .dashboard-video {
            grid-template-columns: repeat(4, 1fr);
        }

        @media (max-width: 1199px) {
            .dashboard-video {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 767px) {
            .dashboard-video {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 575px) {
            .dashboard-video {
                grid-template-columns: repeat(1, 1fr);
            }
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
                    'duration',
                ];
                const players = Plyr.setup('.shorts-video-player', {
                    controls,
                    ratio: '9:16',

                });

                $('.shortsAutoPlay').each(function() {
                    const player = $(this).find('.shorts-video-player')[0];

                    $(this).on('mouseenter', function() {
                        player.muted = true;
                        player.play();

                    });

                    $(this).on('mouseleave', function() {
                        player.pause();
                        player.currentTime = 0;

                    });
                });


            });




        })(jQuery);
    </script>
@endpush
