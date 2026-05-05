@extends($activeTemplate . 'layouts.app')
@section('app')
    @php
        $authImage = getContent('auth_page.content', true);
    @endphp

    <div class="account-section">
        @include('Template::partials.auth_header')
        <div class="account-section__body">
            <div class="container">
                <div class="verification-code-wrapper">
                    <div class="account-form__heading">
                        <h3 class="account-form__title">{{ __($pageTitle) }}</h3>
                    </div>
                    <div class="verification-area">
                        <form action="{{ route('user.password.verify.code') }}" method="POST" class="submit-form">
                            @csrf
                            <p class="account-form__text">@lang('A 6 digit verification code sent to your email address') : {{ showEmailAddress($email) }}</p>
                            <input type="hidden" name="email" value="{{ $email }}">
                            @include($activeTemplate . 'partials.verification_code', [
                                'level' => true,
                            ])
                            <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                            <p class="verify-text mt-3">
                                @lang('Please check including your Junk/Spam Folder. if not found, you can')
                                <a class="text--base" href="{{ route('user.password.request') }}">@lang('Try to send again')</a>
                            </p>
                        </form>
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
        .verification-code-wrapper {
            margin: 0 auto;
            border: 1px solid hsl(var(--bg-color));
            background: hsl(var(--bg-color));
        }

        .verification-code span {
            background-color: hsl(var(--dark));
            border: 1px solid hsl(var(--white) / .08);
        }

        .verification-code::after {
            background-color: hsl(var(--bg-color));
        }
    </style>
@endpush
