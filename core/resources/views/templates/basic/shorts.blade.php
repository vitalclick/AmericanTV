@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="home-body">
        <x-home-body-title icon="vti-top" title="{{ $pageTitle }}" />

        @if (!blank($shortVideos))
            <div class="home-body__shorts">
                @include($activeTemplate . 'partials.video.shorts_list', ['shortVideos' => $shortVideos])
            </div>
        @else
            <div class="empty-container">
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
            let currentPage = "{{ $shortVideos->currentPage() }}";
            let lastPage = false;


            $(window).scroll(function() {
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 3 && !lastPage) {
                    currentPage++;
                    loadMoreVideos();

                }
            });


            function loadMoreVideos() {
                const route = "{{ route('load.shorts.video') }}";

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
