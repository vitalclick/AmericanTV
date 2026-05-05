@extends($activeTemplate . 'layouts.app')
@section('app')
    @php
        $authImage = getContent('auth_page.content', true);
    @endphp

    <div class="account-section">
        @include('Template::partials.auth_header')

        <div class="account-section__body">
            <div class="container">
                <div class="account-form">
                    <div class="account-form__heading">
                        <h3 class="account-form__title">{{ __($pageTitle) }}</h3>
                    </div>
                    <div class="account-form__body">
                        <div class="card-body__text">
                            <p>@lang('Your account is verified successfully. Now you can change your password. Please enter a strong password and don\'t share it with anyone.')</p>
                        </div>
                        <form method="POST" action="{{ route('user.password.update') }}">
                            @csrf
                            <input name="email" type="hidden" value="{{ $email }}">
                            <input name="token" type="hidden" value="{{ $token }}">
                            <div class="form-group ">
                                <label class="form--label">@lang('Password')</label>
                                <input
                                       class="form-control form--control @if (gs('secure_password')) secure-password @endif"
                                       name="password" type="password" required>
                            </div>
                            <div class="form-group">
                                <label class="form--label">@lang('Confirm Password')</label>
                                <input class="form-control form--control" name="password_confirmation" type="password"
                                       required>
                            </div>
                            <button class="btn btn--base w-100" type="submit"> @lang('Submit')</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="account-section__footer">
            <div class="container">
                <p>&copy; {{ now()->year }} {{ __(gs('site_name')) }}. @lang('All rights reserved.')</p>
            </div>
        </div>
    </div>
@endsection

@if (gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif

@push('style')
    <style>
        .card-body__text {
            margin-bottom: 12px;
        }

        .hover-input-popup .input-popup {
            bottom: 75% !important;
        }
    </style>
@endpush
