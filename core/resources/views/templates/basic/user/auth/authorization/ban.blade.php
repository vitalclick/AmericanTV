@extends($activeTemplate . 'layouts.app')
@section('app')
    @php
        $banContent = getContent('ban_page.content', true);
        $authImage = getContent('auth_page.content', true);
    @endphp
    <div class="account-section">
        <div class="account-section__header">
            <div class="container">
                <div class="account-section__logo">
                    <a href="{{ route('home') }}" class="account-section__logo">
                        <img class="light-logo" src="{{ siteLogo() }}" alt="logo">
                        <img class="dark-logo" src="{{ siteLogo('dark') }}" alt="logo">
                    </a>
                </div>
            </div>
        </div>
        <div class="account-section__body">
            <div class="account-form ban text-center">
                <div class="ban-section">
                    <h4 class="text-center">
                        {{ __(@$banContent->data_values->heading) }}
                    </h4>

                    <img src="{{ frontendImage('ban_page', @$banContent->data_values->image, '360x370') }}"
                         alt="@lang('Ban Image')">
                    <div class="mt-3 text--white">
                        <p class="fw-bold mb-1 text--white">@lang('Reason'):</p>
                        <p>{{ $user->ban_reason }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="account-section__footer">
            <div class="container">
                <p>
                <p>© {{ now()->year }} {{ __(gs('site_name')) }}. @lang('All rights reserved.')</p>
                </p>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .ban-section img {
            max-width: 200px;
            width: 100%;
        }
    </style>
@endpush
