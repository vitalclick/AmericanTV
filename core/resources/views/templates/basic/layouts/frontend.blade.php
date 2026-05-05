@extends($activeTemplate . 'layouts.app')
@section('app')
    <!-- ==================== Home Start ==================== -->
    <div class="home-fluid">
        <div class="home__inner">
            <!-- ====================== Sidebar menu Start ========================= -->
            @include($activeTemplate . 'partials.sidebar')
            <!-- ====================== Sidebar menu End ========================= -->
            <div class="home__right">
                <!-- ====================== Header Start ========================= -->
                @include($activeTemplate . 'partials.header')
                <!-- ====================== Header End ========================= -->
                @yield('content')
            </div>
        </div>
    </div>

    <!-- ==================== Home End ==================== -->
@endsection


