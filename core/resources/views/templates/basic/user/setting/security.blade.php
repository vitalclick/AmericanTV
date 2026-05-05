@extends($activeTemplate . 'layouts.master')
@section('content')
    @php
        $user = auth()->user();
        $auth = getContent('auth_page.content', true);

    @endphp
    <div class="setting-content">
        <div class="security-setting">
            @if (!$user->ts)
                <div class="alert alert--warning">
                    <span class="alert__icon"><i class="las la-info-circle"></i></span>
                    <p class="alert__message">
                    {{__($auth->data_values->security_page_alert_message)}}
                        <a class="text--warning text-decoration-underline fw-medium d-block" href="{{ route('user.setting.twofactor') }}">{{__($auth->data_values->security_page_link_text)}}</a>
                    </p>
                </div>
            @endif
            <h3 class="security-setting__title">{{ __($pageTitle) }}</h3>
            <div class="security-setting__item">
                <div class="left">
                    <h5 class="title"><a href="{{ route('user.setting.change.password') }}">#@lang('Change password')</a></h5>
                    <span class="desc">@lang('Update your account password to strengthen security and protect your account').</span>
                </div>

                <a href="{{ route('user.setting.change.password') }}" class="btn-link">
                    @lang('Change Now')
                </a>
            </div>
            <div class="security-setting__item">
                <div class="left">
                    <h5 class="title">@lang('Two step verification')</h5>
                    @if (!$user->ts)
                        <span class="desc">@lang('Enabled two-step verification to secure your account').</span>
                    @else
                        <span class="desc"> @lang('Two-step verification is enabled. Your account is more secure now').</span>
                    @endif
                </div>
                <a class="btn @if (!$user->ts) btn--base @else btn--success @endif" href="{{ route('user.setting.twofactor') }}">
                    @if (!$user->ts)
                        @lang('Turn on')
                    @else
                        @lang('Turn off')
                    @endif
                </a>
            </div>
        </div>
    </div>
@endsection
