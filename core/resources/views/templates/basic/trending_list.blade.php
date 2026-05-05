@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="home-body">
        <x-home-body-title icon="vti-top" title="{{ $pageTitle }}" />

        @if (!blank($trendingVideos))
            <div class="video-wrapper">
                @include($activeTemplate . 'partials.video.video_list', ['videos' => $trendingVideos])
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
    <link rel="stylesheet" href="{{ asset('assets/global/css/plyr.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset($activeTemplateTrue . 'js/owl.carousel.filter.js') }}"></script>

    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';
            // for videos 
            let currentPage = "{{ $trendingVideos->currentPage() }}";
            let url ="{{ route('video.get') }}";

            $(window).scroll(function() {
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 5 && !lastPage) {
                    currentPage++;
                    loadMoreVideos(url, currentPage);
                }
            });



        })(jQuery);
    </script>
@endpush
