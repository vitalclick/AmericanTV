<!-- Header -->
<!doctype html>
<html lang="{{ config('app.locale') }}" itemscope itemtype="http://schema.org/WebPage">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ gs()->siteName(__($pageTitle)) }}</title>
    @include('partials.seo')

    <link href="{{ siteFavIcon() }}" rel="shortcut icon">
    <link href="{{ asset('assets/global/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/global/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/global/css/line-awesome.min.css') }}" rel="stylesheet">

    <link href="{{ asset($activeTemplateTrue . 'css/owl.theme.default.min.css') }}" rel="stylesheet">
    <link href="{{ asset($activeTemplateTrue . 'css/owl.carousel.min.css') }}" rel="stylesheet">
    <link href="{{ asset($activeTemplateTrue . 'css/vt-icons.css') }}" rel="stylesheet">
    <link href="{{ asset($activeTemplateTrue . 'css/main.css') }}" rel="stylesheet">
    <link href="{{ asset($activeTemplateTrue . 'css/custom.css') }}" rel="stylesheet">

    @stack('style-lib')

    <link
        href="{{ asset($activeTemplateTrue . 'css/color.php') }}?color={{ gs('base_color') }}&secondColor={{ gs('secondary_color') }}"
        rel="stylesheet">
    @stack('style')

    <style>
        body {
            display: none;
        }

        [data-theme="light"] {
            background-color: hsl(var(--white));
        }

        [data-theme="dark"] {
            background-color: hsl(var(--black));
        }
    </style>

</head>
@php echo loadExtension('google-analytics') @endphp

<body>
    @stack('fbComment')

    <div class="preloader">
        <span class="loader"></span>
        <div class="loading loading06">
            @foreach (str_split(gs('site_name')) as $char)
                <span data-text="{{ $char }}">{{ $char }}</span>
            @endforeach
        </div>
    </div>

    <div class="body-overlay"></div>
    <div class="sidebar-overlay"></div>
    <a class="scroll-top"><i class="fas fa-arrow-up"></i></a>

    @yield('app')

    @php
        $cookie = App\Models\Frontend::where('data_keys', 'cookie.data')->first();
    @endphp

    @if ($cookie->data_values->status == Status::ENABLE && !\Cookie::get('gdpr_cookie'))
        <div class="cookies-card hide">
            <div class="cookies-card__icon">
                <i class="las la-cookie-bite"></i>
            </div>
            <p class="cookies-card__content">{{ $cookie->data_values->short_desc }} <a class="text--base"
                    href="{{ route('cookie.policy') }}" target="_blank">@lang('learn more')</a></p>
            <div class="cookies-card__btn mt-3 d-flex gap-2">
                <a class="btn btn--base btn--sm policy" href="javascript:void(0)">@lang('Allow')</a>
                <a class="btn btn--white outline btn--sm policy" href="javascript:void(0)">@lang('Cookie Reject')</a>
            </div>
        </div>
    @endif

    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>

    <script src="{{ asset($activeTemplateTrue . 'js/owl.carousel.min.js') }}"></script>
    @stack('script-lib')

    @php echo loadExtension('tawk-chat') @endphp
    @include('partials.notify')
    @if (gs('pn'))
        @include('partials.push_script')
    @endif
    <script src="{{ asset($activeTemplateTrue . 'js/main.js') }}"></script>

    <script>
        let lastPage = false;
        function loadMoreVideos(url, currentPage, categoryId=0) {

                $('#loading-spinner').removeClass('d-none');
                $.ajax({
                    url: `${url}?page=${currentPage}&category_id=${categoryId}`,
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
                $('.video-wrapper').append(videos);
                playersInitiate();
            }

    </script>


    @stack('script')

    <script>
        (function($) {
            "use strict";

            $('.policy').on('click', function() {
                $.get('{{ route('cookie.accept') }}',
                    function(response) {
                        $('.cookies-card').addClass('d-none');
                    });
            });

            setTimeout(function() {
                $('.cookies-card').removeClass('hide')
            }, 2000);

            var inputElements = $('[type=text],select,textarea');
            $.each(inputElements, function(index, element) {
                element = $(element);
                element.closest('.form-group').find('label').attr('for', element.attr('name'));
                element.attr('id', element.attr('name'))
            });

            $.each($('input, select, textarea'), function(i, element) {
                var elementType = $(element);
                if (elementType.attr('type') != 'checkbox') {
                    if (element.hasAttribute('required')) {
                        $(element).closest('.form-group').find('label').addClass('required');
                    }
                }
            });


            function formatState(state) {
                if (!state.id) return state.text;
                let gatewayData = $(state.element).data();
                return $(
                    `<div class="d-flex gap-2">${gatewayData.imageSrc ? `<div class="select2-image-wrapper"><img class="select2-image" src="${gatewayData.imageSrc}"></div>` : '' }<div class="select2-content"> <p class="select2-title">${gatewayData.title}</p><p class="select2-subtitle">${gatewayData.subtitle}</p></div></div>`
                );
            }

            $('.select2').each(function(index, element) {
                $(element).select2();
            });


            $('.select2-basic').each(function(index, element) {
                $(element).select2({
                    dropdownParent: $(element).closest('.select2-parent')
                });

            });

            $('.select2-auto-tokenize').each(function(index, element) {
                $(element).select2({
                    tags: true,
                    tokenSeparators: [','],
                });
            });

            if ("{{ !request()->routeIs('user.advertiser.create.ad') }}") {

                Array.from(document.querySelectorAll('table')).forEach(table => {
                    let heading = table.querySelectorAll('thead tr th');
                    Array.from(table.querySelectorAll('tbody tr')).forEach((row) => {
                        Array.from(row.querySelectorAll('td')).forEach((colum, i) => {
                            colum.setAttribute('data-label', heading[i].innerText)
                        });
                    });
                });
            }


            let disableSubmission = false;
            $('.disableSubmission').on('submit', function(e) {
                if (disableSubmission) {
                    e.preventDefault()
                } else {
                    disableSubmission = true;
                }
            });


            var isScrolling = false;

            $(window).on('scroll', function() {
                isScrolling = true;
                clearTimeout($.data(this, 'scrollTimer'));
                $.data(this, 'scrollTimer', setTimeout(() => {
                    isScrolling = false;
                }, 200));
            });






            // for video

            let loader;
            let player;
            let mouseleaveClass;

            $(document).on('mouseenter', '.autoPlay', function() {
                if (!isScrolling) {
                    parent = $(this);
                    loader = parent.find('.video-loader');
                    player = parent.find('.video-player')[0];
                    const id = parent.data('video_id')

                    loader.show()

                    $.ajax({
                        type: "GET",
                        url: `{{ route('get.video.source', '') }}/${id}`,
                        success: function(response) {
                            if (response.status === 'success') {
                                loader.hide()
                                const src =
                                    `<source src="${response?.path}" type="video/mp4" size="${response?.quality}" />`;
                                parent.find('.video-player').empty().append(src);
                            }
                        },
                        error: function(error) {
                            loader.hide();
                        }
                    });

                    player.load();
                    player.muted = true;
                    player.play().catch(function(error) {
                        console.warn('Autoplay failed:', error);
                    });
                }
            })


            $(document).on('mouseleave', '.autoPlay', function() {
                player.pause();
                player.currentTime = 0;
                $(this).find('.video-player').empty();
                loader.hide();
            });




            // for short auto pllay
            let shortPlayer;

            $(document).on('mouseenter', '.shortsAutoPlay', function() {
                shortPlayer = $(this).find('.shorts-video-player')[0];
                shortPlayer.load();
                shortPlayer.play();
            });


            $(document).on('mouseleave', '.shortsAutoPlay', function() {
                const shortPlayer = $(this).find('.shorts-video-player')[0];
                shortPlayer.load();
                shortPlayer.pause();
                shortPlayer.currentTime = 0;
            });






        })(jQuery);
    </script>
</body>

</html>
