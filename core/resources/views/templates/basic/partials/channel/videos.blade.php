<div class="home-body channel-body">
    @if ($user->id == auth()->id())
        <div class="form-group mt-3">
            <button class="btn addVideo" type="button">
                <span class="icon"><i class="las la-plus"></i></span>
                <span class="text">@lang('Add Video')</span>
            </button>
        </div>
    @endif

    @if (!blank($videos))
        <div class="video-wrapper">
            @include($activeTemplate . 'partials.video.video_list', ['videos' => $videos])
        </div>
    @else
        <div class="empty-container">
            @include('Template::partials.empty')
        </div>
    @endif

    <div class="text-center d-none spinner mt-4 w-100" id="loading-spinner">
        <i class="las la-spinner"></i>
    </div>
</div>


{{-- ads video modal --}}
@if ($user->id == auth()->id())
    <div class="modal custom--modal scale-style fade add-video--modal" id="addVideoModal" data-bs-backdrop="static"
         aria-labelledby="addVideoModal" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-lg  modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <form class="search-form">
                        <div class="form-group mb-0">
                            <input class="form--control" name="search" type="text" placeholder="Search...">
                        </div>
                    </form>
                    <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('user.playlist.add.video') }}" method="post">
                    @csrf
                    <input id="" name="playlist_id" type="number" value="{{ @$playlist->id }}" hidden>
                    <div class="modal-body video-list">
                        <div class="text-center d-none spinner mt-4 w-100" id="load-spinner">
                            <i class="las la-spinner"></i>
                        </div>
                        <div class="videoList-wrapper">
                            @foreach ($videoLists as $videoList)
                                <label class="check-type mb-3" for="flexCheck{{ $videoList->id }}">
                                    <input class="check-type-input" id="flexCheck{{ $videoList->id }}" name="video_id[]"
                                           type="checkbox" value="{{ $videoList->id }}">
                                    <span class="check-type-icon">
                                        <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                             fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor"
                                                  stroke-linecap="round">
                                            </path>
                                        </svg>
                                    </span>
                                    <img class="check-type-img"
                                         src="{{ getImage(getFilePath('thumbnail') . '/thumb_' . @$videoList->thumb_image) }}"
                                         alt="thumb_image">
                                    <span class="form-check-label">
                                        {{ __($videoList->title) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--white btn--sm submitBtn @if ($videoLists->isEmpty()) disabled @endif" type="submit">@lang('Add')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif





@push('style-lib')
    <link href="{{ asset('assets/global/css/plyr.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
@endpush



@push('style')
    <style>
        .video-list {
            max-height: 400px;
            /* Adjust this to the height you want */
            overflow-y: auto;
        }

        .add-video--modal .modal-header,
        .add-playlist--modal .modal-header {
            position: relative;
            padding: 12px 24px 12px;
            border-bottom: 1px solid hsl(var(--white) / 0.1);
        }

        .add-video--modal .modal-footer,
        .add-playlist--modal .modal-footer {
            position: relative;
            padding: 12px 24px 12px;
            border-top: 1px solid hsl(var(--white) / 0.1);
        }

        .add-video--modal .modal-content,
        .add-playlist--modal .modal-content {
            overflow: visible;
        }

        .add-video--modal .search-form,
        .add-playlist--modal .search-form {
            flex-grow: 1;

        }

        .add-video--modal .search-form {
            max-width: 300px;
        }

        .add-playlist--modal .search-form {
            max-width: 200px;
        }

        .add-video--modal .modal-close-btn,
        .add-playlist--modal .modal-close-btn {
            --size: 24px;
            width: var(--size);
            height: var(--size);
            border-radius: 50%;
            position: absolute;
            top: calc((var(--size) / 2) * -1);
            right: calc((var(--size) / 2) * -1);
            color: hsl(var(--black));
            font-size: calc(var(--size) / 2);
            border: 1px solid hsl(var(--black) / 0.15) !important;
            background-color: hsl(var(--black) / 0.1) !important;
            backdrop-filter: blur(5px);
            z-index: 1;
        }


        [data-theme="dark"] .add-video--modal .modal-close-btn,
        [data-theme="dark"] .add-playlist--modal .modal-close-btn {
            color: hsl(var(--white)) !important;
            border: 1px solid hsl(var(--white) / 0.15) !important;
            background-color: hsl(var(--white) / 0.1) !important;
        }

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

@push('script')
    <script>
        (function($) {
            'use strict';

            $('.addVideo').on('click', function() {
                const modal = $('#addVideoModal');
                modal.modal('show');
                modal.find('.modal-title').text('@lang('Add Video')');
            });


            @if ($user->id == auth()->id())
                let currentVideolistPage = "{{ $videoLists->currentPage() }}";
                let lastVideoPage = false;
                var videoList = $('.video-list');
                videoList.scroll(function() {
                    if (videoList.scrollTop() + videoList.height() >= videoList[0].scrollHeight - 50 && !
                        lastVideoPage) {
                        currentVideolistPage++;
                        loadVideoList();
                    }
                });
                let videoSearchTimer;

                $('#addVideoModal').find('input[name="search"]').on('keyup', function() {
                    const searchTerm = $(this).val().trim();

                    clearTimeout(videoSearchTimer);

                    videoSearchTimer = setTimeout(function() {
                        currentVideolistPage = 1;
                        lastVideoPage = false;
                        $('.videoList-wrapper').empty();
                        loadVideoList(searchTerm);
                    }, 500);
                });

                function loadVideoList(searchTerm = '') {
                    const route = "{{ route('user.playlist.video.fetch', $playlist->id) }}";
                    $('#load-spinner').removeClass('d-none');
                    $.ajax({
                        url: `${route}?page=${currentVideolistPage}&search=${searchTerm}`,
                        type: 'GET',
                        success: function(response) {

                            $('#load-spinner').addClass('d-none');

                            if (response.status === 'success' && response.data.videoLists.data.length > 0) {
                                $.each(response.data.videoLists.data, function(index, videoList) {
                                    var imagePath =
                                        "{{ getImage(getFilePath('thumbnail') . '/thumb_' . '12.png') }}";
                                        
                                    imagePath = imagePath.replace('default.png',
                                        'thumbnail/thumb_' + videoList.thumb_image);

                                    var videoHTML = `
                                        <label for="flexCheck${videoList.id}" class="check-type mb-3">
                                            <input class="check-type-input" id="flexCheck${videoList.id}" name="video_id[]" type="checkbox" value="${videoList.id}">
                                            <span class="check-type-icon">
                                                <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor" stroke-linecap="round">
                                                    </path>
                                                </svg>
                                            </span>
                                            <img class="check-type-img" src="${imagePath}" alt="thumb_image">
                                            <span class="check-type-label">
                                                ${videoList.title}
                                            </span>
                                        </label>
                                    `;
                                    $('.video-list .videoList-wrapper').append(videoHTML);
                                });


                                if (currentVideolistPage >= response.data.last_page) {
                                    lastVideoPage = true;
                                }
                            } else {
                                lastVideoPage = true;
                            }
                        },
                        error: function() {
                            $('#loading-spinner').addClass('d-none');
                        }
                    });
                }
            @endif

            let currentPage = "{{ $videos->currentPage() }}";
            let url = "{{ route('user.playlist.video.get', $playlist->id) }}";

            $(window).scroll(function() {
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 5 && !lastPage) {
                    currentPage++;
                    loadMoreVideos(url, currentPage);
                }
            });
        })(jQuery);
    </script>
@endpush
