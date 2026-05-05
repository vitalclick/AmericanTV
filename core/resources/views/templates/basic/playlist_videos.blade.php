@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="home-body">

        <h3 class="home-body__title page-title">
            <span class="icon"><i class="vti-top"></i></span>
            {{ __($pageTitle) }}
        </h3>


        <div class="video-wrapper">
            @include($activeTemplate . 'partials.video.video_list', ['videos' => $videos])
        </div>

    </div>
@endsection


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

            // for playlists
            let currentPage = "{{ $videos->currentPage() }}";
            currentPage = parseInt(currentPage) + 1;
            let lastPage = false;
            loadVideos();


            $(window).scroll(function() {

                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 0 && !lastPage) {
                    currentPage++;
                    loadVideos();
                }
            });

            function loadVideos() {

                const route = "{{ route('user.playlist.load.videos', $playlist->id) }}";

                $.ajax({
                    url: `${route}?page=${currentPage}`,
                    type: 'GET',
                    success: function(response) {

                        if (response.status === 'success') {

                            $('.video-wrapper').append(response.data.videos);

                            if (currentPage >= response.data.last_page) {
                                lastPage = true;
                            }

                            playersInitiate();

                        }
                    }
                });
            }





        })(jQuery);
    </script>
@endpush
