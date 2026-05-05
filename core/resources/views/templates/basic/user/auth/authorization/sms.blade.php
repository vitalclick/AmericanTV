@extends($activeTemplate . 'layouts.frontend')
@section('content')
    @php
        $authImage = getContent('auth_page.content', true);
    @endphp

    <div class="account-section">
        @include('Template::partials.auth_header')

        <div class="account-section__body">
            <div class="container">

                <div class="verification-code-wrapper">
                    <div class="verification-area">
                        <h5 class="pb-3 text-center border-bottom">@lang('Verify Mobile Number')</h5>
                        <form class="submit-form" action="{{ route('user.verify.mobile') }}" method="POST">
                            @csrf
                            <p class="verification-text">@lang('A 6 digit verification code sent to your mobile number') :
                                +{{ showMobileNumber(auth()->user()->mobileNumber) }}</p>
                            @include($activeTemplate . 'partials.verification_code')
                            <div class="mb-3">
                                <button class="btn btn--base w-100" type="submit">@lang('Submit')</button>
                            </div>
                            <div class="form-group">
                                <p>
                                    @lang('If you don\'t get any code'), <span class="countdown-wrapper">@lang('try again after') <span
                                            class="fw-bold" id="countdown">--</span> @lang('seconds')</span> <a
                                        class="try-again-link d-none" href="{{ route('user.send.verify.code', 'sms') }}">
                                        @lang('Try again')</a>
                                </p>
                                <a href="{{ route('user.logout') }}">@lang('Logout')</a>
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


@push('style')
    <style>
        .verification-code-wrapper {
            margin: 0 auto;
            border: 1px solid hsl(var(--bg-color));
            background: hsl(var(--bg-color));
            backdrop-filter: blur(4px);
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

@push('script')
    <script>
        var timeZone = '{{ now()->timezoneName }}';
        var countDownDate = new Date("{{ showDateTime($user->ver_code_send_at->addMinutes(2), 'M d, Y H:i:s') }}");
        countDownDate = countDownDate.getTime();
        var x = setInterval(function() {
            var now = new Date();
            now = new Date(now.toLocaleString('en-US', {
                timeZone: timeZone
            }));
            var distance = countDownDate - now;
            var seconds = Math.floor(distance / 1000);
            document.getElementById("countdown").innerHTML = seconds;
            if (distance < 0) {
                clearInterval(x);
                document.querySelector('.countdown-wrapper').classList.add('d-none');
                document.querySelector('.try-again-link').classList.remove('d-none');
            }
        }, 1000);
    </script>
@endpush
