@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="dashboard-content">
        <div class="card custom--card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="las la-layer-group me-2"></i>@lang('Plan Details')</h5>
                <div>
                    <button class="btn btn--base btn--sm ms-2 editBtn" type="button" data-plan="{{ $plan }}">
                        <i class="las la-edit"></i> @lang('Edit')
                    </button>
                </div>
            </div>

            <div class="card-body">
                <!-- Plan Basic Info -->
                <div class="row mb-4 gy-4 justify-content-center">
                    <div class="col-xxl-4 col-lg-4  col-sm-12">
                        <div class="plan-info-card text-white rounded p-3">
                            <div class="d-flex flex-wrap-reverse align-items-center justify-content-between mb-2 gap-2">
                                <h2 class="mb-1">{{ __($plan->name) }}</h2>
                                <span
                                      class="badge @if ($plan->status) badge--success @else badge--danger @endif">{{ $plan->status ? __('Enabled') : __('Disabled') }}</span>
                            </div>
                            <h3 class="mb-0">{{ showAmount($plan->price) }}</h3>
                        </div>
                    </div>
                    <div class="col-xxl-2 col-lg-4 col-sm-6 col-xsm-6">
                        <a class="stats-card card custom--card rounded p-3 text-center h-100"
                           href="{{ route('user.manage.plan.details', $plan->slug) }}?tab=videos">
                            <div class="stats-icon">
                                <i class="las la-video"></i>
                            </div>
                            <h4 class="stats-card__title mb-1">{{ $plan->videos->count() }}</h4>
                            <p class="stats-card__label mb-0">@lang('Videos')</p>
                        </a>
                    </div>

                    <div class="col-xxl-2 col-lg-4 col-sm-6 col-xsm-6">
                        <a class="stats-card card custom--card rounded p-3 text-center h-100"
                           href="{{ route('user.manage.plan.details', $plan->slug) }}?tab=playlists">
                            <div class="stats-icon">
                                <i class="las la-list"></i>
                            </div>
                            <h4 class="stats-card__title mb-1">{{ $plan->playlists->count() }}</h4>
                            <p class="stats-card__label mb-0">@lang('Playlists')</p>
                        </a>
                    </div>

                    <div class="col-xxl-2 col-lg-4 col-sm-6 col-xsm-6">
                        <a class="stats-card card custom--card rounded p-3 text-center h-100"
                           href="{{ route('user.plan.sell.history') }}?search={{ $plan->name }}">
                            <div class="stats-icon">
                                <i class="las la-user-friends"></i>
                            </div>
                            <h4 class="stats-card__title mb-1">{{ $plan->purchasedPlans()->count() }}</h4>
                            <p class="stats-card__label mb-0">@lang('Subscribers')</p>
                        </a>
                    </div>

                    <div class="col-xxl-2 col-lg-4 col-sm-6 col-xsm-6">
                        <a class="stats-card card custom--card rounded p-3 text-center h-100"
                           href="{{ route('user.plan.sell.history') }}?search={{ $plan->name }}">
                            <div class="stats-icon">
                                <i class="las la-wallet"></i>
                            </div>
                            <h4 class="stats-card__title mb-1">{{ showAmount($plan->totalEarnings()) }}</h4>
                            <p class="stats-card__label mb-0">@lang('Plan Earnings')</p>
                        </a>
                    </div>

                </div>

                <!-- Nav tabs for Videos and Playlists -->
                <ul class="nav nav-tabs custom-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') !== 'playlists' ? 'active' : '' }}"
                           href="{{ route('user.manage.plan.details', $plan->slug) }}?tab=videos" role="presentation">
                            <i class="las la-video me-1"></i> @lang('Videos')
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') === 'playlists' ? 'active' : '' }}"
                           href="{{ route('user.manage.plan.details', $plan->slug) }}?tab=playlists" role="presentation">
                            <i class="las la-list me-1"></i> @lang('Playlists')
                        </a>
                    </li>
                </ul>

                <!-- Tab panes -->
                <div class="tab-content">
                    <!-- Videos Tab -->
                    <div class="tab-pane {{ request('tab') !== 'playlists' ? 'active' : '' }}" id="videos"
                         role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">@lang('Videos in this Plan')</h5>
                                    <button type="button" class="btn btn--base btn--sm addVideo"
                                            data-action="{{ route('user.manage.plan.add.video', $plan->id) }}"
                                            data-plan_id="{{ $plan->id }}">
                                        <i class="las la-plus"></i> @lang('Add Videos')
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="videos-list">
                            @if (!blank($planVideos))
                                <div class="row gy-4">
                                    @foreach ($planVideos as $video)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="video-card h-100">
                                                <div class="position-relative video-thumb">
                                                    <img src="{{ getImage(getFilePath('thumbnail') . '/' . $video->thumb_image) }}" class="img-fluid rounded" alt="{{ $video->title }}">
                                                    <span class="badge bg-dark position-absolute bottom-0 end-0 m-2">{{ $video->duration }}</span>
                                                </div>
                                                <div class="video-info p-3">
                                                    <a class="channel" href="{{ route('preview.channel', @$video->user->slug) }}">{{ __(@$video->user->channel_name) }}</a>
                                                    <h6 class="video-title mb-2">{{ $video->title }}</h6>
                                                    <div class="mt-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                                                        <div class="meta">
                                                            <span class="view">{{ formatNumber($video->views) }}@lang('views')</span>
                                                            <span class="date">{{ $video->created_at->diffForHumans() }}</span>
                                                        </div>
                                                        <button type="button" class="video-card__btn remove-video confirmationBtn"
                                                           data-action="{{ route('user.manage.plan.remove.video', ['video_id' => $video->id, 'plan_id' => $plan->id]) }}"
                                                           data-question="@lang('Are you sure you want to remove this video from the plan?')">
                                                            <i class="las la-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-container">
                                    @include('Template::partials.empty')
                                </div>
                            @endif
                        </div>
                        @if ($planVideos->hasPages())
                            {{ $planVideos->appends(['tab' => 'videos'])->links() }}
                        @endif
                    </div>


                    <!-- Playlists Tab -->
                    <div class="tab-pane {{ request('tab') === 'playlists' ? 'active' : '' }}" id="playlists"
                         role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">@lang('Playlists in this Plan')</h5>
                                    <button type="button" class="btn btn--base btn--sm addPlaylist"
                                            data-action="{{ route('user.manage.plan.add.playlist', $plan->id) }}"
                                            data-plan_id="{{ $plan->id }}" data-selected='@json($plan->playlists->pluck('id'))'>
                                        <i class="las la-plus"></i> @lang('Add Playlists')
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="playlists-list">
                            @if (!blank($planPlaylists))
                                <div class="row gy-5">
                                    @foreach ($planPlaylists as $playlist)
                                        <div class="col-md-6 col-lg-4 col-xl-3">
                                            <div class="playlist-card">
                                                <a class="playlist-card__wrapper"
                                                   href="{{ route('preview.playlist.videos', [@$playlist->slug, @$playlist->user->slug]) }}">
                                                    <div class="playlist-card__content">
                                                        <div class="playlist-card__thumb playlist-thumb ">
                                                            <img src="{{ getImage(getFilePath('thumbnail') . '/' . @$playlist->videos->first()->thumb_image) }}"
                                                                 class="img-fluid rounded" alt="{{ $playlist->title }}">

                                                            <button class="playlist-count playlist-videos"
                                                                    data-playlist-id="{{ $playlist->id }}"
                                                                    type="button">{{ $playlist->videos->count() }}
                                                                @lang('videos')</button>
                                                        </div>
                                                        <div class="playlist-card__body">
                                                            <h6 class="playlist-title mb-2">
                                                                {{ __($playlist->title) }}</h6>
                                                            <div class="text-end">
                                                                <button type="button"
                                                                        data-action="{{ route('user.manage.plan.remove.playlist', ['playlist_id' => $playlist->id, 'plan_id' => $plan->id]) }}"
                                                                        data-question="@lang('Are you sure you want to remove this playlist from the plan?')"
                                                                        class="remove-playlist confirmationBtn"
                                                                        data-id="{{ $playlist->id }}">
                                                                    <i class="las la-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($planPlaylists->hasPages())
                                    {{ $planPlaylists->appends(['tab' => 'playlists'])->links() }}
                                @endif
                            @else
                                <div class="empty-container">
                                    @include('Template::partials.empty')
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal custom--modal scale-style fade view-video--modal" id="viewVideosModal" data-bs-backdrop="static"
         aria-labelledby="viewVideosModal" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Playlist Videos')</h5>
                    <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body video-view-list videos-lists">
                    <div class="text-center d-none spinner mt-4 w-100" id="videos-loading-spinner">
                        <i class="las la-spinner"></i>
                    </div>
                    <div class="videos-wrapper">

                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-confirmation-modal frontend="true" />
    @include('Template::user.plans.modal')
@endsection

@push('script')
    <script>
        (function($) {
            "use strict";

            let currentVideosPage = 1;
            let lastVideosPage = false;

            $('.playlist-videos').on('click', function() {
                const modal = $('#viewVideosModal');
                const playlistId = $(this).data('playlist-id');

                modal.modal('show');

                $('.videos-wrapper').empty();
                currentVideosPage = 1;
                lastVideosPage = false;

                modal.data('playlist-id', playlistId);

                loadPlaylistVideos();
            });

            var videoViewList = $('.video-view-list');

            videoViewList.scroll(function() {
                if (videoViewList.scrollTop() + videoViewList.height() >= videoViewList[0].scrollHeight - 50 &&
                    !lastVideosPage) {
                    currentVideosPage++;
                    loadPlaylistVideos();
                }
            });

            function loadPlaylistVideos() {
                const modal = $('#viewVideosModal');
                const playlistId = modal.data('playlist-id');

                const route = "{{ route('plan.playlist.videos', ':id') }}".replace(':id', playlistId);
                $('#videos-loading-spinner').removeClass('d-none');

                $.ajax({
                    url: `${route}?page=${currentVideosPage}`,
                    type: 'GET',
                    success: function(response) {
                        $('#videos-loading-spinner').addClass('d-none');

                        if (response.status === 'success' && response.data.videos.data.length > 0) {
                            $.each(response.data.videos.data, function(index, video) {

                                var imagePath =
                                    "{{ asset(getFilePath('thumbnail') . '/thumb_' . '12.png') }}";
                                imagePath = imagePath.replace('12.png', video.thumb_image);

                                var videoHTML = `
                            <div class="d-flex align-items-center plan-video p-3 border-bottom">
                                <div class="video-thumb me-3">
                                    <img src="${imagePath}" alt="thumb_image" class="check-type-img">
                                </div>
                                <div class="video-info">
                                    <h5 class="mb-1">${video.title}</h5>
                                </div>
                            </div>
                        `;

                                $('.videos-wrapper').append(videoHTML);
                            });

                            if (currentVideosPage >= response.data.last_page) {
                                lastVideosPage = true;
                            }
                        } else {
                            lastVideosPage = true;

                            if ($('.videos-wrapper').is(':empty') && currentVideosPage === 1) {
                                $('.videos-wrapper').html(
                                    '<div class="text-center py-4">@lang('No videos found')</div>');
                            }
                        }
                    },
                    error: function() {
                        $('#videos-loading-spinner').addClass('d-none');
                        $('.videos-wrapper').html(
                            '<div class="text-center py-4">Error loading videos</div>');
                    }
                });
            }

            $(document).on('click', '.playlist-videos, .remove-playlist', function(e) {
                e.stopPropagation();
                e.preventDefault();
            });

        })(jQuery);
    </script>
@endpush
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

        .video-view-list {
            max-height: 450px;
            overflow-y: auto;
        
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .custom-tabs {
            border-bottom: 1px solid hsl(var(--white)/.1);
        }

        .custom-tabs .nav-link {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            color: hsl(var(--white));
            border: 0;
            border-bottom: 2px solid transparent;
        }

        @media (max-width: 424px) {
            .custom-tabs .nav-link {
                padding: 7px 12px;
            }
        }

        .custom-tabs .nav-link:focus,
        .custom-tabs .nav-link:hover {
            border-color: transparent;
            color: hsl(var(--base)) !important;
        }

        .custom-tabs .nav-link.active {
            background-color: hsl(var(--base)) !important;
            color: hsl(var(--static-white)) !important;
            border-bottom: 2px solid hsl(var(--base)) !important;
        }

        .stats-card,
        .plan-info-card {
            transition: all 0.3s ease;
            border: 1px solid transparent;
            background-color: var(--header-search-bg) !important;
            color: hsl(var(--white)) !important;
            height: 100%;
            position: relative;
        }

        .stats-card:hover,
        .stats-card:focus {
            border-color: hsl(var(--base));
        }

        .stats-card:hover .stats-icon,
        .stats-card:focus .stats-icon {
            background-color: hsl(var(--base));
        }

        .stats-card:hover .stats-card__title,
        .stats-card:focus .stats-card__title {
            color: hsl(var(--base));
        }

        /* .plan-info-card .badge {
                                                                                                                                                                                                                                            position: absolute;
                                                                                                                                                                                                                                            top: 12px;
                                                                                                                                                                                                                                            right: 12px;
                                                                                                                                                                                                                                        } */

        .btn.addVideo {
            color: hsl(var(--static-white)) !important
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: hsl(var(--white)/.08);
            border-radius: 50%;
            margin: 0 auto;
            font-size: 20px;
            margin-bottom: 10px;
            transition: all 0.2s linear;
        }

        .video-info .channel {
            font-size: 0.875rem;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .video-title {
            display: block;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 8px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .video-info .meta {
            font-size: 0.75rem;
            font-weight: 500;
        }

        .video-info .meta .view {
            position: relative;
            padding-right: 20px;
        }

        .video-info .meta .view::after {
            position: absolute;
            content: "";
            background: hsl(var(--body-color));
            width: 2px;
            height: 2px;
            border-radius: 50%;
            right: 8px;
            top: 0;
            bottom: 0;
            margin: auto 0;
        }

        .video-card {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: hsl(var(--dark)/.8);
        }

        .video-card__btn,
        .remove-playlist {
            --size: 28px;
            width: var(--size);
            height: var(--size);
            border-radius: 50%;
            font-size: calc(var(--size) * 0.5);
            color: hsl(var(--danger));
            border: 1px solid hsl(var(--danger));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all .2s ease;
        }

        .video-card__btn:hover,
        .video-card__btn:focus,
        .remove-playlist:hover,
        .remove-playlist:focus {
            color: hsl(var(--white));
            background-color: hsl(var(--danger));
        }


        .playlist-card {
            width: 100%;
            display: block;
        }

        .playlist-count {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: hsl(var(--bg-color));
            color: hsl(var(--white));
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .empty-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            position: unset;
            top: 50%;
            left: 50%;
            transform: unset;
            width: unset;
            height: unset;
        }

        .playlist-card__content {
            background-color: hsl(var(--dark));
        }
    </style>
@endpush
