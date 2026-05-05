@extends($activeTemplate . 'layouts.app')

@section('app')
    @php
        $auth = getContent('auth_page.content', true);
    @endphp

    <div class="account-section">
        @include('Template::partials.auth_header')

        <div class="account-section__body">
            <div class="container">
                <div class="account-form">
                    <div class="account-form__heading">
                        <h3 class="account-form__title">{{ __($pageTitle) }}</h3>
                        <p class="account-form__text">{{ __(@$auth->data_values->login_page_title) }}</p>
                    </div>
                    <div class="account-form__body">
                        @include($activeTemplate . 'partials.social_login')
                        <form method="POST" action="{{ route('user.login') }}" class="verify-gcaptcha">
                            @csrf
                            <div class="form-group">
                                <label class="form--label">@lang('Username or Email')</label>
                                <input type="text" name="username" value="{{ old('username') }}"
                                       class="form-control form--control" required>
                            </div>

                            <div class="form-group">
                                <label class="form--label ">@lang('Password')</label>
                                <input type="password" class="form-control form--control" name="password" required>
                            </div>

                            @php
                                $hasLevel = true;
                            @endphp

                            <x-captcha :hasLevel='$hasLevel' />

                            <div class="d-flex flex-wrap justify-content-between">
                                <div class="form-group form--check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                           {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">
                                        @lang('Remember Me')
                                    </label>
                                </div>

                                <div class="form-group">
                                    <a class="forgot-pass" href="{{ route('user.password.request') }}">
                                        @lang('Forgot your password?')
                                    </a>
                                </div>
                            </div>

                            <button type="submit" id="recaptcha" class="btn btn--base w-100">
                                @lang('Login')
                            </button>

                            @if (gs('registration'))
                                <p class="text-center other-login mt-3">@lang('Don\'t have any account?')
                                    <a class="text--base mb-0 " href="{{ route('user.register') }}">@lang('Register')</a>
                                </p>
                            @endif
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
