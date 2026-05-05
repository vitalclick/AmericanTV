@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="card custom--card">
            <div class="card-header">
                <h5 class="card-title">@lang('KYC Form')</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('user.kyc.submit') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <x-viser-form identifier="act" identifierValue="kyc" />
                    <button class="btn btn--base w-100" type="submit">@lang('Submit')</button>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('style-lib')
    <link href="{{ asset('assets/global/css/select2.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush
