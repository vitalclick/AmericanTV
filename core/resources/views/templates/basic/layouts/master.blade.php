@extends($activeTemplate . 'layouts.app')
@section('app')
    <div class="home-fluid">
        <div class="home__inner">
            @include($activeTemplate . 'partials.sidebar')
            <div class="home__right">
                @include($activeTemplate . 'partials.header')
                @if (!request()->routeIs(['user.setting.*', 'user.authorization']))
                    <div class="home-body dashboard-body">
                        <div class="dashboard-wrapper">
                            @if (request()->routeIs(['user.advertiser.*']) || @$advertisement)
                                @include($activeTemplate . 'partials.advertiser_sidebar')
                            @else
                                @include($activeTemplate . 'partials.user_sidebar')
                            @endif
                            @yield('content')
                        </div>
                    </div>
                @else
                    <div class="home-body setting-body">
                        <div class="setting-wrapper">
                            @include($activeTemplate . 'partials.setting_menu')
                            @yield('content')
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .daterangepicker td.active,
        .daterangepicker td.active:hover,
        .daterangepicker .ranges li.active {
            background-color: hsl(var(--base)) !important;
        }
    </style>
@endpush

@push('style-lib')
    <link href="{{ asset($activeTemplateTrue . 'css/owl.theme.default.min.css') }}" rel="stylesheet">
    <link href="{{ asset($activeTemplateTrue . 'css/owl.carousel.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset($activeTemplateTrue . 'js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset($activeTemplateTrue . 'js/owl.carousel.filter.js') }}"></script>
@endpush
