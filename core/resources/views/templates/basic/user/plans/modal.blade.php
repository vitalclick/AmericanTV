<div id="createModal" class="modal fade custom--modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <span type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </span>
            </div>
            <div class="modal-body">
                <form method="post">
                    @csrf
                    <div class="form-group">
                        <label class="form--label">@lang('Name')</label>
                        <input type="text" class="form-control form--control" name="name" required
                               placeholder="@lang('Enter plan name')">
                    </div>
                    <div class="form-group">
                        <label class="form--label">@lang('Price')</label>
                        <div class="input-group">
                            <input class="form--control form-control" name="price" type="number"
                                   placeholder="@lang('Enter Price')" step="any" required>
                            <span class="input-group-text btn--base border-0">{{ __(gs('cur_text')) }}</span>
                        </div>
                    </div>

                    <div class="text-end mt-5">
                        <button type="submit" class="btn btn--white">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- add video modal  --}}
<div class="modal custom--modal add-video--modal scale-style fade" id="addVideoModal" data-bs-backdrop="static"
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
            <form method="post">
                @csrf
                <input name="plan_id" type="number" value="" hidden>
                <div class="modal-body video-list">
                    <div class="text-center d-none spinner mt-4 w-100" id="loading-spinner">
                        <i class="las la-spinner"></i>
                    </div>
                    <div class="videoList-wrapper">

                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn--white btn--sm submitBtn" type="submit">@lang('Add Video')</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add playlist modal  --}}
<div class="modal custom--modal add-playlist--modal scale-style fade" id="addPlaylistModal" data-bs-backdrop="static"
     aria-labelledby="addPlaylistModal" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
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
            <form method="post">
                @csrf
                <input name="plan_id" type="number" value="" hidden>
                <div class="modal-body playlist-list">

                    <div class="playlistList-wrapper">

                    </div>
                    <div class="text-center d-none spinner mt-4 w-100" id="playlist-loading-spinner">
                        <i class="las la-spinner"></i>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn--white btn--sm submitBtn" type="submit">@lang('Add Playlist')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('style')
    <style>
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

        .add-video--modal .video-list,
        .add-playlist--modal .video-list {
            max-height: 450px;
            overflow-y: auto;
        }

        .playlist-list {
            max-height: 450px;
            overflow-y: auto;
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
            "use strict";
            $('.createBtn').on('click', function() {
                var modal = $('#createModal');
                const url = "{{ route('user.manage.plan.save') }}"
                modal.find('form').attr('action', url);
                modal.find('.modal-title').text("@lang('Add Plan')");

                modal.find('[name="name"]').val('')
                modal.find('[name="price"]').val('')

                modal.modal('show');
            });

            $('.editBtn').on('click', function() {
                var modal = $('#createModal');
                var data = $(this).data('plan');
                var url = "{{ route('user.manage.plan.save') }}/" + data.id;
                modal.find('form').attr('action', url);
                modal.find('.modal-title').text("@lang('Edit Plan')");
                modal.find('[name="name"]').val(data.name);
                modal.find('[name="price"]').val(parseFloat(data.price).toFixed(2));
                modal.modal('show');
            });

            $('.addVideo').on('click', function() {
                const modal = $('#addVideoModal');
                const actionUrl = $(this).data('action');
                const selectedVideos = $(this).data('selected') || [];

                modal.find('[name="plan_id"]').val($(this).data('plan_id'));
                modal.find('form').attr('action', actionUrl);
                modal.modal('show');
                modal.find('.modal-title').text('@lang('Add Video')');

                modal.find('input[name="search"]').val('');
                $('.videoList-wrapper').empty();

                currentVideoPage = 1;
                lastVideoPage = false;

                loadVideoList();
            });

            $('#addVideoModal').on('hidden.bs.modal', function() {
                const modal = $(this);
                modal.find('form')[0].reset();
                modal.find('.modal-title').text('');
                $('.videoList-wrapper').empty();
            });


            let currentVideoPage = 1;
            let lastVideoPage = false;
            let videoSearchTimer;

            const videoList = $('.video-list');

            videoList.scroll(function() {
                if (videoList.scrollTop() + videoList.height() >= videoList[0].scrollHeight - 50 && !
                    lastVideoPage) {
                    currentVideoPage++;
                    loadVideoList();
                }
            });

            $('#addVideoModal').find('input[name="search"]').on('keyup', function() {
                const searchTerm = $(this).val().trim();

                clearTimeout(videoSearchTimer);

                videoSearchTimer = setTimeout(function() {
                    currentVideoPage = 1;
                    lastVideoPage = false;
                    $('.videoList-wrapper').empty();
                    loadVideoList(searchTerm);
                }, 500);
            });

            function loadVideoList(searchTerm = '') {
                const modal = $('#addVideoModal');
                let planId = modal.find('[name="plan_id"]').val();
                const route = "{{ route('user.manage.plan.video.fetch', ':id') }}".replace(':id', planId);
                $('#loading-spinner').removeClass('d-none');

                $.ajax({
                    url: `${route}?page=${currentVideoPage}&search=${searchTerm}`,
                    type: 'GET',
                    success: function(response) {
                        $('#loading-spinner').addClass('d-none');

                        if (response.status === 'success' && response.data.videoLists.data.length > 0) {

                            $.each(response.data.videoLists.data, function(index, video) {
                                
                                var imagePath =
                                    "{{ asset(getFilePath('thumbnail') . '/thumb_' . '12.png') }}";
                                imagePath = imagePath.replace('12.png', video.thumb_image);

                                var videoHTML = `
                                    <label class="check-type mb-3" for="flexCheck${video.id}">
                                        <input class="check-type-input" id="flexCheck${video.id}" name="video_id[]"
                                            type="checkbox" value="${video.id}">
                                        <span class="check-type-icon">
                                            <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor"
                                                    stroke-linecap="round">
                                                </path>
                                            </svg>
                                        </span>
                                        <img class="check-type-img" src="${imagePath}" alt="thumb_image">
                                        <span class="form-check-label">
                                            ${video.title}
                                        </span>
                                    </label>
                                `;

                                $('.videoList-wrapper').append(videoHTML);
                            });

                            if (currentVideoPage >= response.data.last_page) {
                                lastVideoPage = true;
                            }

                            if ($('.videoList-wrapper').children().length > 0) {
                                modal.find('.submitBtn').removeClass('disabled');
                            } else {
                                modal.find('.submitBtn').addClass('disabled');
                            }

                        } else {
                            lastVideoPage = true;

                            if (currentVideoPage === 1 && $('.videoList-wrapper').children().length === 0) {
                                var emptyHTML = `
                                    <div class="text-muted text-center empty-msg">
                                        <div class="empty-container empty-card-two">
                                            @include('Template::partials.empty')
                                        </div>
                                    </div>
                                `;
                                $('.videoList-wrapper').html(emptyHTML);
                                modal.find('.submitBtn').addClass('disabled');
                            }
                        }
                    },
                    error: function() {
                        $('#loading-spinner').addClass('d-none');

                        var errorHTML = `
                            <div class="text-muted text-center">
                                <p>@lang('Error loading videos. Please try again.')</p>
                            </div>
                        `;
                        $('.videoList-wrapper').html(errorHTML);
                        modal.find('.submitBtn').addClass('disabled');
                    }
                });
            }


            $('.addPlaylist').on('click', function() {
                const modal = $('#addPlaylistModal');
                const actionUrl = $(this).data('action');
                const selectedPlaylists = $(this).data('selected');

                modal.find('[name="plan_id"]').val($(this).data('plan_id'));
                modal.find('form').attr('action', actionUrl);
                modal.modal('show');
                modal.find('.modal-title').text('@lang('Add Playlist')');

                modal.find('input[name="playlist_id[]"]').prop('checked', false);

                if (Array.isArray(selectedPlaylists)) {
                    selectedPlaylists.forEach(id => {
                        modal.find(`input[name="playlist_id[]"][value="${id}"]`).closest(
                            'label.check-type').addClass('d-none');
                    });
                }

            });



            $('.addPlaylist').on('click', function() {
                const modal = $('#addPlaylistModal');
                const actionUrl = $(this).data('action');
                const selectedPlaylists = $(this).data('selected') || [];

                modal.find('[name="plan_id"]').val($(this).data('plan_id'));
                modal.find('form').attr('action', actionUrl);
                modal.modal('show');
                modal.find('.modal-title').text('@lang('Add Playlist')');

                modal.find('input[name="search"]').val('');
                $('.playlistList-wrapper').empty();

                currentPlaylistPage = 1;
                lastPlaylistPage = false;

                loadPlaylistList();
            });

            $('#addPlaylistModal').on('hidden.bs.modal', function() {
                const modal = $(this);
                modal.find('form')[0].reset();
                modal.find('.modal-title').text('');
                $('.playlistList-wrapper').empty();
            });

            let currentPlaylistPage = 1;
            let lastPlaylistPage = false;
            let playlistSearchTimer;

            const playlistList = $('.playlist-list');

            playlistList.scroll(function() {
                if (playlistList.scrollTop() + playlistList.height() >= playlistList[0].scrollHeight - 50 && !
                    lastPlaylistPage) {
                    currentPlaylistPage++;
                    loadPlaylistList();
                }
            });

            $('#addPlaylistModal').find('input[name="search"]').on('keyup', function() {
                const searchTerm = $(this).val().trim();

                clearTimeout(playlistSearchTimer);

                playlistSearchTimer = setTimeout(function() {
                    currentPlaylistPage = 1;
                    lastPlaylistPage = false;
                    $('.playlistList-wrapper').empty();
                    loadPlaylistList(searchTerm);
                }, 500);
            });

            function loadPlaylistList(searchTerm = '') {
                const modal = $('#addPlaylistModal');
                let planId = modal.find('[name="plan_id"]').val();
                const route = "{{ route('user.manage.plan.playlist.fetch', ':id') }}".replace(':id', planId);
                $('#playlist-loading-spinner').removeClass('d-none');

                $.ajax({
                    url: `${route}?page=${currentPlaylistPage}&search=${searchTerm}`,
                    type: 'GET',
                    success: function(response) {
                        $('#playlist-loading-spinner').addClass('d-none');

                        if (response.status === 'success' && response.data.playlistLists.data.length > 0) {
                            const selectedPlaylists = modal.find('.addPlaylist').data('selected') || [];

                            $.each(response.data.playlistLists.data, function(index, playlist) {
                                if (Array.isArray(selectedPlaylists) && selectedPlaylists.includes(
                                        playlist.id)) {
                                    return;
                                }

                                var imagePath = playlist.image_path;

                                var playlistHTML = `
                                    <label class="check-type mb-3" for="flexCheck_${playlist.id}">
                                        <input class="check-type-input" id="flexCheck_${playlist.id}"
                                            name="playlist_id[]" type="checkbox" value="${playlist.id}">
                                        <span class="check-type-icon">
                                            <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor"
                                                    stroke-linecap="round">
                                                </path>
                                            </svg>
                                        </span>
                                        <img class="check-type-img" src="${imagePath}" alt="playlist_thumb">
                                        <span class="form-check-label">
                                            ${playlist.title} <br>
                                            <p class="text-muted mb-0 small">${playlist.videos_count} videos</p>
                                        </span> 
                                    </label>
                                `;

                                $('.playlistList-wrapper').append(playlistHTML);
                            });

                            if (currentPlaylistPage >= response.data.last_page) {
                                lastPlaylistPage = true;
                            }

                            if ($('.playlistList-wrapper').children().length > 0) {
                                modal.find('.submitBtn').removeClass('disabled');
                            } else {
                                modal.find('.submitBtn').addClass('disabled');
                            }

                        } else {
                            lastPlaylistPage = true;

                            if (currentPlaylistPage === 1 && $('.playlistList-wrapper').children()
                                .length === 0) {
                                var emptyHTML = `
                                    <div class="text-muted text-center empty-msg">
                                        <div class="empty-container empty-card-two">
                                            @include('Template::partials.empty')
                                        </div>
                                    </div>
                                `;
                                $('.playlistList-wrapper').html(emptyHTML);
                                modal.find('.submitBtn').addClass('disabled');
                            }
                        }
                    },
                    error: function() {
                        $('#playlist-loading-spinner').addClass('d-none');

                        var errorHTML = `
                            <div class="text-muted text-center">
                                <p>@lang('Error loading playlists. Please try again.')</p>
                            </div>
                        `;
                        $('.playlistList-wrapper').html(errorHTML);
                        modal.find('.submitBtn').addClass('disabled');
                    }
                });
            }

        })(jQuery);
    </script>
@endpush
