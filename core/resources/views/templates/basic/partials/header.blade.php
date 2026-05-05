@php
    $user = auth()->user();
@endphp
<div class="home-header">
    <div class="home-header__inner">
        <!-- Left -->
        <div class="home-header__left">
            <!-- Header Button -->
            <button class="menu-button">
                <span class="menu-button-line"></span>
                <span class="menu-button-line"></span>
                <span class="menu-button-line"></span>
            </button>
            <!-- Logo -->
            <div class="d-xxl-none">
                <a class="sidebar-logo__link dark" href="{{ route('home') }}"><img src="{{ siteLogo() }}"
                        alt="@lang('logo')"></a>
                <a class="sidebar-logo__link light" href="{{ route('home') }}"><img src="{{ siteLogo('dark') }}"
                        alt="@lang('logo')"></a>
            </div>
            <!-- Search Form -->
            <div class="search-form-wrapper">
                <form class="search-form" action="{{ route('search') }}">
                    <div class="form-group">
                        <input class="form--control" name="search" type="text" value="{{ request()->search }}"
                            placeholder="Search Here...">
                        <button class="search-form-btn" type="submit">
                            <i class="vti-search"></i>
                        </button>
                    </div>
                </form>
                <button class="home-header__left-mic micBtn"><i class="fa-solid fa-microphone-lines"></i></button>
                <!-- Responsive Search Button -->
                <button class="sm-search-btn d-md-none d-block" type="button">
                    <i class="vti-search"></i>
                </button>
            </div>
            <!-- Responsive Search Close Button -->
            <button class="search-close d-none" type="button">
                <i class="vti-cross"></i>
            </button>
        </div>
        <!-- Right -->
        <div class="home-header__right">
            <div class="sm-bottom-nav">
                <a class="sm-bottom-nav__link"
                    href=" @auth {{ route('user.home') }} @else {{ route('home') }} @endauth">
                    <span class="icon">
                        <svg class="lucide lucide-house" xmlns="http://www.w3.org/2000/svg" width="18"
                            height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"></path>
                            <path
                                d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z">
                            </path>
                        </svg>
                    </span>
                    <span class="text">@auth @lang('Dashboard')
                        @else
                        @lang('Home') @endauth </span>
                </a>
            </div>

            <div class="sm-bottom-nav">
                <a class="sm-bottom-nav__link" href="{{ route('shorts.list') }}">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                            fill="none">
                            <g id="Shorts">
                                <g id="Group 68">
                                    <path id="Subtract"
                                        d="M4.83441 6.10509L5.08441 6.53811L4.83441 6.10509ZM11.1872 2.43733L10.9372 2.00432L11.1872 2.43733ZM15.6407 3.63066L16.0737 3.38066L15.6407 3.63066ZM14.4474 8.08422L14.1974 7.65121L14.4474 8.08422ZM14.2037 8.22488L13.9537 7.79187C13.7979 7.88186 13.7024 8.04867 13.7038 8.22863C13.7051 8.4086 13.8031 8.57396 13.9603 8.6616L14.2037 8.22488ZM14.2436 13.8949L14.4936 14.3279L14.2436 13.8949ZM7.89089 17.5627L7.64089 17.1297L7.89089 17.5627ZM3.43733 16.3693L3.00432 16.6193L3.43733 16.3693ZM4.63066 11.9158L4.38066 11.4828L4.63066 11.9158ZM4.8743 11.7751L5.1243 12.2081C5.28016 12.1181 5.37564 11.9513 5.37429 11.7714C5.37294 11.5914 5.27497 11.426 5.11778 11.3384L4.8743 11.7751ZM4.58441 5.67208C2.78592 6.71044 2.16971 9.01016 3.20807 10.8087L4.07409 10.3087C3.31188 8.98845 3.76421 7.30032 5.08441 6.53811L4.58441 5.67208ZM10.9372 2.00432L4.58441 5.67208L5.08441 6.53811L11.4372 2.87035L10.9372 2.00432ZM16.0737 3.38066C15.0354 1.58217 12.7356 0.965961 10.9372 2.00432L11.4372 2.87035C12.7574 2.10813 14.4455 2.56046 15.2077 3.88066L16.0737 3.38066ZM14.6974 8.51723C16.4959 7.47887 17.1121 5.17915 16.0737 3.38066L15.2077 3.88066C15.9699 5.20086 15.5176 6.88899 14.1974 7.65121L14.6974 8.51723ZM14.4537 8.6579L14.6974 8.51723L14.1974 7.65121L13.9537 7.79187L14.4537 8.6579ZM15.87 9.19135C15.5184 8.58236 15.0208 8.10794 14.4472 7.78817L13.9603 8.6616C14.3806 8.89595 14.7452 9.24324 15.004 9.69135L15.87 9.19135ZM14.4936 14.3279C16.2921 13.2896 16.9083 10.9898 15.87 9.19135L15.004 9.69135C15.7662 11.0115 15.3138 12.6997 13.9936 13.4619L14.4936 14.3279ZM8.14089 17.9957L14.4936 14.3279L13.9936 13.4619L7.64089 17.1297L8.14089 17.9957ZM3.00432 16.6193C4.04268 18.4178 6.3424 19.034 8.14089 17.9957L7.64089 17.1297C6.32069 17.8919 4.63256 17.4395 3.87035 16.1193L3.00432 16.6193ZM4.38066 11.4828C2.58217 12.5211 1.96596 14.8208 3.00432 16.6193L3.87035 16.1193C3.10813 14.7991 3.56046 13.111 4.88066 12.3488L4.38066 11.4828ZM4.6243 11.3421L4.38066 11.4828L4.88066 12.3488L5.1243 12.2081L4.6243 11.3421ZM3.20807 10.8087C3.55967 11.4176 4.05727 11.8921 4.63083 12.2118L5.11778 11.3384C4.69743 11.104 4.33281 10.7568 4.07409 10.3087L3.20807 10.8087Z"
                                        fill="currentColor"></path>
                                    <path id="Vector"
                                        d="M8.04688 8.62652C8.04688 8.41646 8.26368 8.28345 8.441 8.38455L11.1694 9.94237C11.2111 9.9662 11.2459 10.0012 11.2701 10.0438C11.2943 10.0863 11.3071 10.1348 11.3071 10.1842C11.3071 10.2336 11.2943 10.2821 11.2701 10.3247C11.2459 10.3672 11.2111 10.4022 11.1694 10.4261L8.441 11.9836C8.40052 12.0067 8.35493 12.0185 8.30874 12.0177C8.26254 12.017 8.21733 12.0038 8.17755 11.9794C8.13777 11.955 8.1048 11.9203 8.08189 11.8786C8.05898 11.837 8.04691 11.7899 8.04688 11.7419V8.62652Z"
                                        stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                    </path>
                                </g>
                            </g>
                        </svg>
                    </span>
                    <span class="text">@lang('Shorts')</span>
                </a>
            </div>
            @if (@$user->profile_complete)
                <!-- Create -->
                <div class="create">
                    <ul class="create__list">
                        <li class="create__list-item">
                            <a class="create__list-link" href="{{ route('user.video.upload.form') }}">
                                <span class="icon one"><i class="vti-export-box"></i></span>
                                <span class="text">@lang('Upload')</span>
                            </a>
                        </li>

                        <li class="create__list-item">
                            <a class="create__list-link" href="{{ route('user.shorts.upload.form') }}">
                                <span class="icon three"><i class="vti-short"></i></span>
                                <span class="text">@lang('Shorts')</span>
                            </a>
                        </li>

                    </ul>
                </div>
                <button class="btn btn--base manageCreate create__btn ">
                    <span class="icon">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">@lang('Create')</span>
                </button>
            @elseif(auth()->check() && !@$user->profile_complete)
                <a class="btn btn--base create__btn" href="{{ route('user.channel.create') }}">
                    <span class="icon">
                        <i class="fas fa-plus"></i>
                    </span>
                    <span class="text">@lang('Create Channel')</span>
                </a>
            @endif
            @if (!auth()->check())
                <a class="btn btn--base create__btn" href="{{ route('user.login') }}">
                    <span class="icon"><i class="vti-user-circle"></i></span>
                    <span class="text">@lang('Login')</span>
                </a>
            @endif
            @php

                $notifications = collect();
                $notificationCount = 0;

            @endphp
            @auth
                @php
                    $notificationsQuery = App\Models\UserNotification::where('user_id', $user->id)->with('user');
                    $notificationCount = (clone $notificationsQuery)->where('is_read', Status::NO)->count();
                    $notifications = $notificationsQuery->latest()->take(5)->get();
                    $showCount = $notificationCount > 9 ? '9+' : $notificationCount;

                @endphp
                <div class="notification notification--sm">
                    <button class="notification__btn"> <i class="vti-notification"></i><span
                            class="rounded-circle countDown">{{ @$showCount }}</span></button>
                    <ul class="notification__list">
                        <li class="notification__list-header">
                            <span class="text">@lang('Notifications')</span>
                        </li>

                        @foreach ($notifications as $notification)
                            <li class="notification__list-item">
                                <a class="notification__list-link"
                                    href="{{ route('user.notification.read', $notification->id) }}">
                                    <span class="channel-thumb">
                                        <img class="fit-image"
                                            src="{{ getImage(getFilePath('userProfile') . '/' . $notification->user->image, isAvatar: true) }}"
                                            alt="image">
                                    </span>
                                    <div class="content">
                                        <h6 class="title">{{ __($notification->title) }}</h6>
                                        <span class="time">{{ $notification->created_at->diffForHumans() }}</span>
                                    </div>
                                </a>
                            </li>
                        @endforeach

                        @if (count($notifications) == 0)
                            <li class="no-notification">
                                <span class="icon"><i class="far fa-bell"></i></span>
                                <span class="text">@lang('Your notifications live here')</span>
                            </li>
                        @endif

                        @if (count($notifications) > 0)
                            <li class="notification__list-item text-center ">
                                <a href="{{ route('user.notification.all') }}">@lang('View All')</a>
                            </li>
                        @endif
                    </ul>
                </div>
            @endauth

            <div class="sm-bottom-nav">
                <a class="sm-bottom-nav__link" href="{{ route('trending.list') }}">
                    <span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                            fill="none">
                            <path
                                d="M12.8014 4.345C14.2388 4.98731 15.4122 6.10339 16.1257 7.50679C16.8392 8.91019 17.0496 10.5159 16.7217 12.0558C16.3937 13.5956 15.5473 14.9763 14.3239 15.9672C13.1005 16.958 11.5741 17.4991 9.99971 17.5C8.65305 17.4999 7.33606 17.1044 6.21222 16.3625C5.08839 15.6205 4.20719 14.5649 3.67803 13.3265C3.14886 12.0882 2.99502 10.7217 3.23561 9.39672C3.47621 8.07173 4.10063 6.84658 5.03137 5.87333C5.67276 6.76894 6.51909 7.49813 7.49971 8C7.51688 6.89886 7.77681 5.81506 8.26094 4.82591C8.74508 3.83675 9.4415 2.9666 10.3005 2.2775C10.9562 3.15683 11.8136 3.86563 12.8005 4.34417L12.8014 4.345Z"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path
                                d="M9.16406 9.44856C9.16406 9.23439 9.3851 9.09879 9.56589 9.20187L12.3475 10.7901C12.3901 10.8144 12.4255 10.8501 12.4502 10.8935C12.4749 10.9368 12.4879 10.9863 12.4879 11.0367C12.4879 11.087 12.4749 11.1365 12.4502 11.1799C12.4255 11.2232 12.3901 11.2589 12.3475 11.2832L9.56589 12.8712C9.52461 12.8948 9.47813 12.9067 9.43103 12.906C9.38394 12.9052 9.33784 12.8917 9.29729 12.8669C9.25673 12.842 9.22312 12.8066 9.19976 12.7641C9.1764 12.7217 9.1641 12.6737 9.16406 12.6248V9.44856Z"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                    <span class="text">@lang('Trending')</span>
                </a>
            </div>

            <!-- User Information -->
            <div class="user-info">
                <button class="user-info__button">
                    @if (!auth()->check())
                        <span class="title">
                            <i class="vti-ellipsis-v"></i>
                        </span>
                    @else
                        <span class="user-info__thumb">
                            <img src="{{ getImage(getFilePath('userProfile') . '/' . $user->image, isAvatar: true) }}"
                                alt="image">
                        </span>
                    @endif
                </button>

                <ul class="user-info-list">
                    @if (auth()->check())
                        <li class="user-info-list__item">
                            <div class="author">
                                <div class="author__thumb">
                                    <img src="{{ getImage(getFilePath('userProfile') . '/' . $user->image, isAvatar: true) }}"
                                        alt="Author">
                                </div>
                                <div class="author__content">
                                    <h5 class="title">
                                        {{ $user->channel_name ? $user->channel_name : $user->fullname }}</h5>

                                    @if ($user->profile_complete)
                                        <span class="username">{{ $user->username }}</span> <br>
                                        <span class="username">{{ $user->email }}</span>
                                    @else
                                        <span class="username"><a
                                                href="{{ route('user.channel.create') }}">@lang('Create Channel')</a></span>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="user-info-list__item">
                            <ul class="list">
                                <li class="list__item">
                                    <a class="list__link" href="{{ route('user.home') }}">
                                        <span class="icon"><i class="las la-home"></i></span>
                                        <span class="text">@lang('Dashboard')</span>
                                    </a>
                                </li>
                                @if ($user->profile_complete)
                                    <li class="list__item">
                                        <a class="list__link" href="{{ route('user.channel.home') }}">
                                            <span class="icon"><i class="las la-tv"></i></span>
                                            <span class="text">@lang('Your Channel')</span>
                                        </a>
                                    </li>
                                @endif
                                <li class="list__item">
                                    <a class="list__link" href="{{ route('user.advertiser.home') }}">
                                        <span class="icon"><i class="vti-advertising"></i></span>
                                        <span class="text">@lang('Advertising')</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if (gs('multi_language'))
                        @php
                            $language = App\Models\Language::all();
                            $defaultLanguage = App\Models\Language::where('code', config('app.locale'))->first();
                        @endphp
                        <li class="user-info-list__item">
                            <ul class="list">
                                <li class="list__item has-dropdown">
                                    <a class="list__link" href="javascript:void(0)">
                                        <span class="icon"><i class="vti-language"></i></span>
                                        <span class="text">@lang('Language'): {{ __($defaultLanguage->name) }}
                                        </span>
                                    </a>
                                    <ul class="user-info-submenu">
                                        @foreach ($language as $item)
                                            <li class="user-info-submenu__item">
                                                <a class="user-info-submenu__link"
                                                    href="{{ route('lang', $item->code) }}">
                                                    <span class="icon"><i class="las la-angle-right"></i></span>
                                                    <span class="text">{{ __($item->name) }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <li class="user-info-list__item">
                        <ul class="list">
                            <li class="list__item theme-switch-item">
                                <div class="theme-switch-wrapper">
                                    <label class="theme-switch" for="checkbox">
                                        <input id="checkbox" type="checkbox">
                                        <div class="slider round">
                                            <span class="icon dark"><i class="vti-moon"></i></span>
                                            <span class="icon light"><i class="vti-sun"></i></span>
                                        </div>
                                    </label>
                                </div>
                                <span class="text dark">@lang('Dark Mode')</span>
                                <span class="text light">@lang('Light Mode')</span>
                            </li>
                            @auth
                                <li class="list__item">
                                    <a class="list__link" href="{{ route('user.setting.account') }}">
                                        <span class="icon"><i class="vti-setting"></i></span>
                                        <span class="text">@lang('Setting')</span>
                                    </a>
                                </li>

                                <li class="list__item">
                                    <a class="list__link" href="{{ route('user.logout') }}">
                                        <span class="icon"><i class="vti-logout"></i></span>
                                        <span class="text">@lang('Logout')</span>
                                    </a>
                                </li>
                            @endauth
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @if (request()->routeIs('home'))
        @php
            $categories = App\Models\Category::active()->get();
        @endphp
        @if ($categories->count() > 0)
            <div class="tag_sliders owl-carousel">
                <a class="tag-item" href="{{ route('category.video', 'all') }}">@lang('All')</a>
                @foreach ($categories as $category)
                    <a class="tag-item"
                        href="{{ route('category.video', $category->slug) }}">{{ __($category->name) }}</a>
                @endforeach
            </div>
        @endif
    @endif
</div>

<div class="popup-container">
    <span class="close-icon">
        <i class="las la-times"></i>
    </span>
    <div class="popup-container__header">
        <h4 class="popup-container__title"> @lang('Search with your voice') </h4>
    </div>
    <div class="popup-container__body">
        <span class="shape-icon micActiveBtn">
            <i class="vti-mic"></i>
        </span>
    </div>
</div>



@push('style')
    <style>
        .countDown {
            padding: 0px 4px;
            font-size: 12px;
        }

        sup {
            top: -1em;
        }

        /* MOBILE ONLY - Equal spacing for bottom navigation */
        @media (max-width: 991px) {
            .home-header__right {
                display: flex;
                align-items: center;
                justify-content: space-around;
                width: 100%;
            }

            .home-header__right > .sm-bottom-nav,
            .home-header__right > .create,
            .home-header__right > .btn.create__btn,
            .home-header__right > .user-info {
                flex: 1;
                display: flex;
                justify-content: center;
                align-items: center;
            }
        }

        @keyframes micPulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 0, 0, 0.7);
            }

            70% {
                transform: scale(1.2);
                box-shadow: 0 0 0 10px rgba(0, 136, 255, 0);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(0, 136, 255, 0);
            }
        }

        .micActiveBtn {
            cursor: pointer;
            display: inline-block;
            padding: 10px;
            border-radius: 50%;
            background-color: hsl(var(--base));
            transition: transform 0.3s ease-in-out;
        }

        .micActiveBtn .vti-mic {
            font-size: 24px;
            color: white;
        }

        .micActiveBtn.active {
            animation: micPulse 1.5s infinite;
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            $(document).ready(function() {
                var recognition = new(window.SpeechRecognition || window.webkitSpeechRecognition)();
                recognition.lang = 'en-US';
                recognition.interimResults = false;
                recognition.maxAlternatives = 1;


                $('.micActiveBtn').on('click', function() {
                    $('.popup-container__title').text('Listening...');
                    $(this).addClass('active');
                    recognition.start();
                });


                recognition.onresult = function(event) {
                    var transcript = event.results[0][0].transcript;
                    $('[name="search"]').val(transcript);
                    $('.search-form').submit();
                    $('.popup-container__title').text('@lang('Search with your voice')');
                    $('.micActiveBtn').removeClass('active');
                };

                recognition.onerror = function(event) {
                    $('.popup-container__title').text('Sorry, I didn\'t catch that.');
                    $('.micActiveBtn').removeClass('active');
                };

                recognition.onend = function() {
                    $('.popup-container__title').text('@lang('Search with your voice')');
                    $('.micActiveBtn').removeClass('active');
                };
            });

        })(jQuery)
    </script>
@endpush