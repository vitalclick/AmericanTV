<div class="home-body channel-body">

    @if (request()->routeIs('preview.playlist') && $user->id == auth()->id())
        <div class="form-group mt-3">
            <button class="btn addPlaylist" type="button">
                <span class="icon"><i class="las la-plus"></i></span>
                <span class="text">@lang('Add Playlist')</span>
            </button>
        </div>
    @endif

    <div class="playlist-card-wrapper mt-5">

        @if ($playlists->count() > 0)
            @include($activeTemplate . 'partials.playlist_list', ['playlists' => $playlists])
        @else
            <div class="empty-container">
                @include('Template::partials.empty')
            </div>
        @endif
    </div>

    <div class="text-center d-none spinner mt-4 w-100" id="loading-spinner">
        <i class="las la-spinner"></i>
    </div>
</div>

<!-- Modal -->
@include($activeTemplate . 'partials.playlist_modal')



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
    <link href="{{ asset('assets/global/css/select2.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';
            $('.addPlaylist').on('click', function() {
                const modal = $('#playlistModal');
                const url = "{{ route('user.playlist.save') }}";
                modal.find('form').attr('action', url);
                modal.find('.modal-title').text('@lang('Create a new playlist')');
                modal.find('.submitBtn').text('@lang('Create')');
                modal.find('form')[0].reset();
                modal.modal('show');
            })

            $('.editPlaylist').on('click', function(e) {
                e.preventDefault();
                const modal = $('#playlistModal');
                var data = $(this).data('playlist');

                const url = "{{ route('user.playlist.save') }}/" + data.id;
                modal.find('form').attr('action', url);
                modal.find('[name="title"]').val(data.title);
                modal.find('[name="slug"]').val(data.slug);
                modal.find('[name="price"]').val(parseFloat(data.price).toFixed(2));
                modal.find('[name="description"]').val(data.description);
                modal.find('[name="visibility"]').prop('checked', false); // Uncheck all first
                modal.find(`[name="visibility"][value="${data.visibility}"]`).prop('checked', true);
                modal.find('[name="playlist_subscription"]').prop('checked', false);
                modal.find('[name="playlist_subscription"]').prop('checked', data.playlist_subscription == 1);

                modal.find('.modal-title').text('@lang('Edit playlist')')
                modal.find('.submitBtn').text('@lang('Update')');
                modal.modal('show');
            });

            // for playlists
            let currentPage = "{{ $playlists->currentPage() }}";
            currentPage = parseInt(currentPage) + 1;
            let lastPage = false;

            $(window).scroll(function() {
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 0 && !lastPage) {
                    currentPage++;
                    loadPlaylist();
                }
            });

            function loadPlaylist() {
                const route = "{{ route('user.channel.playlist.fetch',$user->id) }}";
                $('#loading-spinner').removeClass('d-none');
                $.ajax({
                    url: `${route}?page=${currentPage}`,
                    type: 'GET',
                    success: function(response) {
                        $('#loading-spinner').addClass('d-none');
                        if (response.status === 'success') {
                            $('.playlist-card-wrapper').append(response.data.playlists);
                            if (currentPage >= response.data.last_page) {
                                lastPage = true;
                            }
                        }
                    }
                });
            }

        })(jQuery)
    </script>
@endpush
