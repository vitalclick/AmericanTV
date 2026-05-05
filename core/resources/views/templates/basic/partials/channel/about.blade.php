<div class="home-body channel-body">
    <div class="about-artist">
        <div class="chanel-details">
            <h5 class="chanel-details__title">
                @lang('Channel details')
            </h5>
            <p class="chanel-details__text mb-3">
                @php
                    echo $user->channel_description;
                @endphp

            </p>
            <p class="chanel-details__date mt-3">
                <span class="icon">
                    <i class="fa-regular fa-clock"></i>
                </span>
                @lang('Joined'): {{ showDateTime($user->created_at, 'M-d-y') }}
            </p>
        </div>

        <div class="chanel-social mt-4">
            <ul class="chanel-social__list">
                <li class="chanel-social__item">
                    <span class="chanel-social__icon">
                        <img src="{{ asset($activeTemplateTrue . 'images/x.png') }}" alt="image">
                    </span>
                    <span class="chanel-social__content">
                        <span class="chanel-social__title">@lang('Twitter')</span>
                        <a href="{{ @$user->social_links?->twitter }}" target="__blank"
                           class="chanel-social__link">{{ @$user->social_links?->twitter }}
                        </a>
                    </span>
                </li>
                <li class="chanel-social__item">
                    <span class="chanel-social__icon">
                        <img src="{{ asset($activeTemplateTrue . 'images/fb.png') }}" alt="image">
                    </span>
                    <span class="chanel-social__content">
                        <span class="chanel-social__title">@lang('Facebook')</span>
                        <a href="{{ @$user->social_links?->facebook }}" class="chanel-social__link" target="__blank">
                            {{ @$user->social_links?->facebook }}
                        </a>
                    </span>
                </li>
                <li class="chanel-social__item">
                    <span class="chanel-social__icon">
                        <img src="{{ asset($activeTemplateTrue . 'images/insta.png') }}" alt="image">
                    </span>
                    <span class="chanel-social__content">
                        <span class="chanel-social__title">@lang('Instagram')</span>
                        <a href="{{ @$user->social_links?->instragram }}" class="chanel-social__link" target="__blank">
                            {{ @$user->social_links?->instragram }}
                        </a>
                    </span>
                </li>

            </ul>
        </div>
    </div>
</div>
