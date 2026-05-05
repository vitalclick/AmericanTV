@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="home-body">

        <x-home-body-title icon="vti-top" title="{{ $pageTitle }}" />

        <div class="row gy-3 playlists-wrapper">
            @if ($playlists->count() > 0)
                <div class="playlist-card-wrapper mt-5">
                    @include($activeTemplate . 'partials.playlist_list', ['playlists' => $playlists])
                </div>

                <div class="text-center d-none spinner mt-4 w-100" id="loading-spinner">
                    <i class="las la-spinner"></i>
                </div>
            @else
                <div class="empty-container">
                    @include('Template::partials.empty')
                </div>
            @endif

        </div>
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


@push('script')
    <script>
        (function($) {
            'use strict';

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
                const route = "{{ route('user.playlist.load') }}";
                $('#loading-spinner').removeClass('d-none');
                $.ajax({
                    url: `${route}?page=${currentPage}`,
                    type: 'GET',
                    success: function(response) {

                        if (response.status === 'success') {
                            $('#loading-spinner').addClass('d-none');
                            $('.playlist-card-wrapper').append(response.data.playlists);
                            if (currentPage >= response.data.last_page) {
                                lastPage = true;
                            }
                        }
                    }
                });
            }

        })(jQuery);
    </script>
@endpush
