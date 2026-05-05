@extends($activeTemplate . 'partials.upload')
@section('uplaod_content')
    @include($activeTemplate . 'partials.video.visibility')
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush
