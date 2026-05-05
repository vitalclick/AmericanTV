<div class="home-body channel-body @if (!blank($videos)) home-body__shorts @else empty-body__shorts @endif">
    @if (!blank($videos))
        @include($activeTemplate . 'partials.video.shorts_list', ['shortVideos' => $videos])
    @else
        <div class="empty-container">
            @include('Template::partials.empty')
        </div>
    @endif
</div>
<div class="text-center d-none spinner mt-4" id="loading-spinner">
    <i class="las la-spinner"></i>
</div>
<!-- Spinner for loading more comments -->



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

        .empty-body__shorts {
            display: grid;
            grid-template-columns: unset;

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


            const controls = [

            ];


            $(document).ready(function() {
                shortPlayers();
              

            });


            function shortPlayers() {
                Plyr.setup('.shorts-video-player', {
                    controls,
                    ratio: '9:16',
                    muted: true,
                });

            }

            // for comment 
            let currentPage = "{{ $videos->currentPage() }}";
            let lastPage = false;


            $(window).scroll(function() {
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 5 && !lastPage) {
                    currentPage++;
                    loadMoreVideos();

                }
            });


            function loadMoreVideos() {
                const route = "{{ route('load.shorts.video', $user->id) }}";

                $('#loading-spinner').removeClass('d-none');

                $.ajax({
                    url: `${route}?page=${currentPage}`,
                    type: 'GET',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#loading-spinner').addClass('d-none');
                            appendVideos(response.data.videos);

                            if (currentPage >= response.data.last_page) {
                                lastPage = true;
                            }
                        } else {
                            notify('error', response.message.error);
                        }
                    }
                });
            }


            function appendVideos(videos) {
                $('.home-body__shorts').append(videos);
                shortPlayers();
          

            }




        })(jQuery);
    </script>
@endpush
