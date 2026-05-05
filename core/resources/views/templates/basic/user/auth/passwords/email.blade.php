@extends($activeTemplate . 'layouts.app')
@section('app')
    @php
        $authImage = getContent('auth_page.content', true);
    @endphp

    <div class="account-section">
        @include('Template::partials.auth_header')
        <div class="account-section__body">
            <div class="container">
                <div class="account-form sm--style">
                    <div class="account-form__heading">
                        <h3 class="account-form__title">{{ __($pageTitle) }}</h3>
                    </div>
                    <div class="account-form__body">
                        <div class="mb-4">
                            <p>@lang('To recover your account please provide your email or username to find your account.')</p>
                        </div>
                        <form method="POST" action="{{ route('user.password.email') }}" class="verify-gcaptcha">
                            @csrf
                            <div class="form-group">
                                <label class="form--label">@lang('Email or Username')</label>
                                <input type="text" class="form-control form--control" name="value" value="{{ old('value') }}" required autofocus="off">
                            </div>
                            @php
                                $hasLevel = true;
                            @endphp
                            <x-captcha :hasLevel='$hasLevel' />

                            <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>

                        </form>
                    </div>
                </div>

            </div>
        </div>

        <div class="account-section__footer">
            <div class="container">
                <p>© {{ now()->year }} {{ __(gs('site_name')) }}. @lang('All rights reserved.')</p>
            </div>
        </div>
    </div>
@endsection
