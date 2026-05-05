@extends($activeTemplate . 'layouts.app')
@section('app')
    <div class="account-section">

        @include('Template::partials.auth_header')

        <div class="account-section__body">
            <div class="container d-flex justify-content-center">
                <div class="verification-code-wrapper">
                    <div class="verification-area">
                        <h5 class="pb-3 text-center border-bottom">@lang('2FA Verification')</h5>
                        <form action="{{ route('user.2fa.verify') }}" method="POST" class="submit-form">
                            @csrf

                            @include($activeTemplate . 'partials.verification_code')

                            <div class="form--group">
                                <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="account-section__footer">
            <div class="container">
                <p>    <p>© {{ now()->year }} {{ __(gs('site_name')) }}. @lang('All rights reserved.')</p></p>
            </div>
        </div>

    </div>
@endsection
