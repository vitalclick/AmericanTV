@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="setting-content">
        <div class="two-fa-wrapper">
            <a href="{{ route('user.setting.security') }}" class="two-fa-wrapper__back"><i class="vti-left-long"></i></a>
            <h5 class="title">{{ __($pageTitle) }}</h5>
            <div class="two-fa">
                <div class="row">
                    @if (!$user->ts)
                        <div class="col-md-6">
                            <h5 class="two-fa__title">1. @lang('Scan QR Code')</h5>
                            <p class="two-fa__desc">
                                @lang('Scan the QR code using a passcode generator app') (e.g., @lang('Google Authenticator or Authy')).
                            </p>
                            <div class="two-fa__qr">
                                <div class="qr">
                                    <img src="{{ $qrCodeUrl }}" alt="image">
                                </div>
                                <p class="note">@lang('If you cannot scan, please enter the following code manually')
                                </p>
                                <div class="copy-form">
                                    <div class="form-group">
                                        <input type="text"value="{{ $secret }}" class="form--control secretCode"
                                            readonly>
                                        <button class="copyCode-btn  copytext copied" id="copyBoard"><i
                                                class="vti-copy"></i></button>
                                    </div>
                                </div>
                                <label><i class="fas fa-info-circle"></i> @lang('Help')</label>
                                <p class="note">@lang('Google Authenticator is a multifactor app for mobile devices. It generates timed codes used during the 2-step verification process. To use Google Authenticator, install the Google Authenticator application on your mobile device.') <a class="text--base"
                                        href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en"
                                        target="_blank">@lang('Download')</a></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="two-fa__title">2. @lang('Confirm OTP Code')</h5>
                            <p class="two-fa__desc">
                                @lang('Confirm your passcode generator app by entering the code below').
                            </p>
                            <form action="{{ route('user.setting.twofactor.enable') }}" method="post"
                                class="row two-fa__form">
                                @csrf
                                <input type="hidden" name="key" value="{{ $secret }}">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form--label">@lang('One Time Passcode') (@lang('From Authentication App'))</label>
                                        <input type="text" name="code" class="form--control" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-0 text-end">
                                        <button type="submit"
                                            class="btn btn--base btn--lg w-100">@lang('Confirm and Enable Two-Factor')</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif
                    @if ($user->ts)
                        <div class="col-md-12">
                            <h5 class="two-fa__title">2. @lang('Confirm OTP Code')</h5>
                            <p class="two-fa__desc">
                                @lang('Confirm your passcode generator app by entering the code below').
                            </p>
                            <form action="{{ route('user.setting.twofactor.disable') }}" method="post"
                                class="row two-fa__form">
                                @csrf
                                <input type="hidden" name="key" value="{{ $secret }}">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form--label">@lang('One Time Passcode') (@lang('From Authentication App'))</label>
                                        <input type="text" name="code" class="form--control" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group mb-0 text-end">
                                        <button type="submit"
                                            class="btn btn--base btn--lg w-100">@lang('Confirm and Disable Two-Factor')</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection



@push('script')
    <script>
        (function($) {
            "use strict";
            $('#copyBoard').on('click', function() {
                var copyText = document.getElementsByClassName("secretCode");
                copyText = copyText[0];
                copyText.select();
                copyText.setSelectionRange(0, 99999);
                /*For mobile devices*/
                document.execCommand("copy");
                copyText.blur();

                setTimeout(() => this.classList.remove('copied'), 1500);
            });
        })(jQuery);
    </script>
@endpush
