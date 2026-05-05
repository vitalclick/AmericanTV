<button class="dashboard-menu-btn btn btn--base d-xl-none d-block"><i class="las la-sliders-h"></i></button>
<div class="dashboard-menu">
    <span class="dashboard-menu__close d-xl-none d-block"><i class="las la-times"></i></span>
    <ul class="dashboard-menu__list">
        <li class="dashboard-menu__item">
            <a href="{{ route('user.home') }}" class="dashboard-menu__link {{ menuActive('user.home') }}">
                <span class="icon"><i class="vti-dashboard"></i></span>
                <span class="text">@lang('Dashboard')</span>
            </a>
        </li>
        <li
            class="dashboard-menu__item  has-dropdown {{ menuActive(['user.videos', 'user.free.videos', 'user.stock.videos', 'user.shorts']) }}">
            <a href="javascript:void(0)" class="dashboard-menu__link">
                <span class="icon"><i class="vti-video"></i></span>
                <span class="text">@lang('Videos')</span>
            </a>
            <div
                class="sidebar-submenu {{ menuActive(['user.videos', 'user.free.videos', 'user.stock.videos', 'user.shorts'], 4) }}">
                <ul class="sidebar-submenu-list">
                    <li class="sidebar-submenu-list__item {{ menuActive('user.videos') }}">
                        <a href="{{ route('user.videos') }}" class="sidebar-submenu-list__link">
                            <span class="text"> @lang('All Videos') </span>
                        </a>
                    </li>

                    <li class="sidebar-submenu-list__item {{ menuActive('user.free.videos') }} ">
                        <a href="{{ route('user.free.videos') }}" class="sidebar-submenu-list__link">
                            <span class="text"> @lang('Free Videos') </span>
                        </a>
                    </li>

                    <li class="sidebar-submenu-list__item {{ menuActive('user.stock.videos') }}">
                        <a href="{{ route('user.stock.videos') }}" class="sidebar-submenu-list__link">
                            <span class="text"> @lang('Stock Videos') </span>
                        </a>
                    </li>


                    <li class="sidebar-submenu-list__item {{ menuActive('user.shorts') }}">
                        <a href="{{ route('user.shorts') }}" class="sidebar-submenu-list__link">
                            <span class="text"> @lang('Shorts') </span>
                        </a>
                    </li>

                </ul>
            </div>
        </li>



        @if (auth()->user()->monetization_status == Status::YES)
            <li class="dashboard-menu__item">
                <a href="{{ route('user.wallet') }}"
                    class="dashboard-menu__link {{ menuActive(['user.wallet', 'user.withdraw']) }}">
                    <span class="icon"><i class="vti-wallet"></i></span>
                    <span class="text">@lang('Wallet')</span>
                </a>
            </li>
        @endif


        <li class="dashboard-menu__item">
            <a href="{{ route('user.monetization') }}"
                class="dashboard-menu__link {{ menuActive('user.monetization') }}">
                <span class="icon"><i class="vti-money"></i></span>
                <span class="text">@lang('Monetization')</span>
            </a>
        </li>

        <li class="dashboard-menu__item">
            <a href="{{ route('user.advertiser.home') }}"
                class="dashboard-menu__link {{ menuActive('user.monetization') }}">
                <span class="icon"><i class="vti-advertising"></i></span>
                <span class="text">@lang('Advertising')</span>
            </a>
        </li>

        @if (gs('is_monthly_subscription'))
            <li class="dashboard-menu__item">
                <a href="{{ route('user.manage.plan.index') }}"
                    class="dashboard-menu__link {{ menuActive(['user.manage.plan.index', 'user.manage.plan.details']) }}">
                    <span class="icon"><i class="las la-calendar-alt"></i></span>
                    <span class="text">@lang('Monthly Plan')</span>
                </a>
            </li>
        @endif

        <li class="dashboard-menu__item">
            <a href="{{ route('user.earnings') }}" class="dashboard-menu__link {{ menuActive('user.earnings') }}">
                <span class="icon"><i class="las la-hand-holding-usd"></i></span>
                <span class="text">@lang('Earnings')</span>
            </a>
        </li>

        <li class="dashboard-menu__item">
            <a href="{{ route('user.purchased.history') }}"
                class="dashboard-menu__link {{ menuActive('user.purchased.history') }}">
                <span class="icon"><i class="las la-file-invoice-dollar"></i></span>
                <span class="text">@lang('Purchased Video ')</span>
            </a>
        </li>

        @if (gs('is_playlist_sell'))
            <li class="dashboard-menu__item">
                <a href="{{ route('user.playlist.purchased.history') }}"
                    class="dashboard-menu__link {{ menuActive('user.playlist.purchased.history') }}">
                    <span class="icon"><i class="las la-list-ul"></i></span>
                    <span class="text">@lang('Purchased Playlist')</span>
                </a>
            </li>
        @endif

        @if (gs('is_monthly_subscription'))
            <li class="dashboard-menu__item">
                <a href="{{ route('user.plan.purchased') }}"
                    class="dashboard-menu__link {{ menuActive('user.plan.purchased') }}">
                    <span class="icon"><i class="las la-receipt"></i></span>
                    <span class="text">@lang('Purchased Plan')</span>
                </a>
            </li>
        @endif

        @if (gs('is_playlist_sell') && auth()->user()->salePlaylists()->exists())
            <li class="dashboard-menu__item">
                <a href="{{ route('user.playlist.sell.history') }}"
                    class="dashboard-menu__link {{ menuActive('user.playlist.sell.history') }}">
                    <span class="icon"><i class="las la-history"></i></span>
                    <span class="text">@lang('Sold Playlist History')</span>
                </a>
            </li>
        @endif

        @if (gs('is_monthly_subscription') && auth()->user()->salePlans()->exists())
            <li class="dashboard-menu__item">
                <a href="{{ route('user.plan.sell.history') }}"
                    class="dashboard-menu__link {{ menuActive('user.plan.sell.history') }}">
                    <span class="icon"><i class="las la-wallet"></i></span>
                    <span class="text">@lang('Sold Plan History')</span>
                </a>
            </li>
        @endif

        <li class="dashboard-menu__item">
            <a href="{{ route('ticket.index') }}"
                class="dashboard-menu__link {{ menuActive(['ticket.index', 'ticket.view']) }}">
                <span class="icon"><i class="las la-ticket-alt"></i></span>
                <span class="text">@lang('Support Ticket')</span>
            </a>
        </li>


        <li class="dashboard-menu__item">
            <a href="{{ route('user.transactions') }}"
                class="dashboard-menu__link {{ menuActive('user.transactions') }}">
                <span class="icon"><i class="las la-exchange-alt"></i></span>
                <span class="text">@lang('Transaction')</span>
            </a>
        </li>
        <li class="dashboard-menu__item">
            <a href="{{ route('user.notification.all') }}"
                class="dashboard-menu__link {{ menuActive('user.notification.all') }}">
                <span class="icon"><i class="las la-bell"></i></span>
                <span class="text">@lang('Notification')</span>
            </a>
        </li>

    </ul>
</div>
