<button class="setting-menu-btn btn btn--base d-xl-none d-block"><i class="las la-sliders-h"></i></button>
<div class="setting-menu">
    <span class="setting-menu__close d-xl-none d-block"><i class="las la-times"></i></span>
    <h3 class="setting-menu__title">@lang('Setting')</h3>
    <ul class="setting-menu__list">
        <li class="setting-menu__item">
            <a href="{{ route('user.setting.account') }}"
               class="setting-menu__link {{ menuActive('user.setting.account') }}">
                <span class="icon"><i class="vti-setting"></i></span>
                <span class="text">@lang('Account Information')</span>
            </a>
        </li>
        <li class="setting-menu__item">
            <a href="{{ route('user.setting.profile') }}"
               class="setting-menu__link {{ menuActive('user.setting.profile') }}">
                <span class="icon"><i class="vti-user-circle"></i></span>
                <span class="text">@lang('Profile Settings')</span>
            </a>
        </li>

        <li class="setting-menu__item">
            <a href="{{ route('user.authorization') }}"
               class="setting-menu__link {{ menuActive('user.authorization') }}">
                <span class="icon"><i class="vti-user-varified"></i></span>
                <span class="text">@lang('Verification')</span>
            </a>
        </li>
        <li class="setting-menu__item">
            <a href="{{ route('user.setting.security') }}"
               class="setting-menu__link {{ menuActive(['user.setting.security', 'user.setting.twofactor', 'user.setting.change.password', ]) }}">
                <span class="icon"><i class="vti-security"></i></span>
                <span class="text">@lang('Security Settings')</span>
            </a>
        </li>

    </ul>
</div>
