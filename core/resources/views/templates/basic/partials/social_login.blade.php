@if (@gs('socialite_credentials')->linkedin->status || @gs('socialite_credentials')->facebook->status == Status::ENABLE || @gs('socialite_credentials')->google->status == Status::ENABLE)
    <div class="social-login-wrapper">
        @if (@gs('socialite_credentials')->google->status == Status::ENABLE)
            <div class="continue-google">
                <a href="{{ route('user.social.login', 'google') }}" class="social-login-btn">
                    <span class="google-icon">
                        <img src="{{ asset($activeTemplateTrue . 'images/google.svg') }}" alt="Google">
                    </span>
                </a>
            </div>
        @endif
        @if (@gs('socialite_credentials')->facebook->status == Status::ENABLE)
            <div class="continue-facebook">
                <a href="{{ route('user.social.login', 'facebook') }}" class="social-login-btn">
                    <span class="facebook-icon">
                        <img src="{{ asset($activeTemplateTrue . 'images/facebook.svg') }}" alt="Facebook">
                    </span>
                </a>
            </div>
        @endif
        @if (@gs('socialite_credentials')->linkedin->status == Status::ENABLE)
            <div class="continue-linkedin">
                <a href="{{ route('user.social.login', 'linkedin') }}" class="social-login-btn">
                    <span class="facebook-icon">
                        <img src="{{ asset($activeTemplateTrue . 'images/linkdin.svg') }}" alt="Linkedin">
                    </span>
                </a>
            </div>
        @endif
    </div>

    <div class="text-center another-login">
        <span class="text">@lang('OR')</span>
    </div>
@endif
