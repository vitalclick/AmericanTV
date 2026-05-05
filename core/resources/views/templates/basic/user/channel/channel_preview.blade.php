@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="channel-body">
        <div class="channel-cover">
            <img src="{{ getImage(getFilePath('cover') . '/' . $user->cover_image) }}" alt="Channel Cover Photo">
            <div class="social">

                @if (@$user->social_links?->facebook)
                    <a class="social__link" href="{{ @$user->social_links?->facebook }}" target="__blank"><i
                           class="vti-facebook"></i></a>
                @endif
                @if (@$user->social_links?->twitter)
                    <a class="social__link" href="{{ @$user->social_links?->twitter }}" target="__blank"><i
                           class="vti-twitter"></i></a>
                @endif
                @if (@$user->social_links?->instragram)
                    <a class="social__link" href="{{ @$user->social_links?->instragram }}" target="__blank"><i
                           class="vti-instagram"></i></a>
                @endif
                @if (@$user->social_links?->descord)
                    <a class="social__link" href="{{ @$user->social_links?->descord }}" target="__blank"><i
                           class="vti-descord"></i></a>
                @endif
                @if (@$user->social_links?->descord)
                    <a class="social__link" href="{{ @$user->social_links?->tiktok }}" target="__blank"><i
                           class="vti-tiktok"></i></a>
                @endif
            </div>
        </div>
        <div class="channel-header">
            <div class="channel-header__content">
                <div class="avatar">
                    <img class="fit-image"
                         src="{{ getImage(getFilePath('userProfile') . '/' . $user->image, isAvatar: true) }}"
                         alt="Channel Profile Picture">
                </div>
                <h3 class="name">{{ $user->channel_name }}</h3>
                <span class="username"><span>@</span>{{ $user->username }}</span>
                <div class="meta">

                    <span> <span class="subscribeCount">{{ formatNumber($subscriberCount ?? 0) }}</span>
                        @lang('subscribers')</span>
                    @if ($user->id == auth()->id())
                        <span>{{ $videosCount ?? 0 }} @lang('videos')</span>
                    @else
                        <span>{{ $user->videos->where('visibility', Status::PUBLIC)->where('status', Status::PUBLISHED)->count() }}
                            @lang('videos')</span>
                    @endif
                </div>
            </div>

            @if (auth()->check() && auth()->id() != $user->id)
                @php
                    $authUser = auth()->user();
                    $subscribed = in_array($authUser->id, $authUser->isSubscribe());
                @endphp
                <div class="channel-header__buttons subscriber-btn">
                    <button
                            class="btn cta @if (!$subscribed) btn--white  subcriberBtn @else  btn--white outline unSubcriberBtn @endif">
                        @if ($subscribed)
                            @lang('Unsubscribe')
                        @else
                            @lang('Subscribe')

                            <span class="shape">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        @endif
                    </button>
                </div>
            @endif
            @if (request()->routeIs('preview.playlist.videos'))
                <div class="playlist-title">
                    <p>{{ __(@$playlist->title) }} @lang('Playlist Collection')</p>
                </div>
                @if (@$playlist->playlist_subscription == Status::YES && gs('is_playlist_sell'))
                    @php

                        $isPurchased = true;
                        if (@$playlist->playlist_subscription) {
                            $isPurchased = false;
                        }
                        if (auth()->check()) {
                            $viewer = auth()->user();
                            if (@$playlist->playlist_subscription) {
                                $isPurchased = in_array(@$playlist->id, $viewer->purchasedPlaylistId);
                            }
                        }

                    @endphp
                    <div class="premium-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16"
                             aria-hidden="true" class="_24ydrq0 _1286nb17o _1286nb12r6">
                            <path
                                  d="M486.2 50.2c-9.6-3.8-20.5-1.3-27.5 6.2l-98.2 125.5-83-161.1C273 13.2 264.9 8.5 256 8.5s-17.1 4.7-21.5 12.3l-83 161.1L53.3 56.5c-7-7.5-17.9-10-27.5-6.2C16.3 54 10 63.2 10 73.5v333c0 35.8 29.2 65 65 65h362c35.8 0 65-29.2 65-65v-333c0-10.3-6.3-19.5-15.8-23.3">
                            </path>
                        </svg>
                    </div>
                    @if ($isPurchased)
                        @lang('Purchased')
                    @elseif (!auth()->user() || $playlist->user_id !== @$viewer->id)
                        <div class="d-flex gap-3 align-items-center">
                            <div class="left purchase-price">
                                {{ gs('cur_sym') }}{{ showAmount(@$playlist->price, currencyFormat: false) }}
                            </div>
                            <div class="btn btn--base btn--sm premium-stock-text purchase-now"
                                 data-resource="{{ @$playlist }}">
                                @lang('Purchase Now')
                            </div>
                        </div>
                    @endif
                @endif
            @endif


        </div>
        <div class="channel-tab">
            <a class="channel-tab__item {{ menuActive('preview.channel') }}"
               href="{{ route('preview.channel', $user->slug) }}">@lang('Videos')</a>
            <a class="channel-tab__item {{ menuActive(['preview.playlist', 'preview.playlist.videos']) }}"
               href="{{ route('preview.playlist', $user->slug) }}">@lang('Playlists')</a>
            <a class="channel-tab__item {{ menuActive('preview.shorts') }} "
               href="{{ route('preview.shorts', $user->slug) }}">@lang('Shorts')</a>
            <a class="channel-tab__item {{ menuActive('preview.about') }}"
               href="{{ route('preview.about', $user->slug) }}">@lang('About')</a>
            @if (gs('is_monthly_subscription') && (!auth()->check() || $user->id != auth()->id()))
                <a class="channel-tab__item {{ menuActive('preview.monthly.plan') }}"
                   href="{{ route('preview.monthly.plan', $user->slug) }}">@lang('Monthly Plan')</a>
            @endif
        </div>

        @include($activeTemplate . 'partials.channel.' . $bladeName)
    </div>

    @if (auth()->check())
        {{-- unSubcriberModal --}}
        <div class="modal scale-style fade custom--modal" id="unSubcriberModal" aria-labelledby="unSubcriberModalLabel"
             aria-hidden="true" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Confirm Alert!')</h5>
                        <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>@lang('Are you sure you want to unsubscribe this channel?')</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--sm btn--white outline" data-bs-dismiss="modal"
                                type="button">@lang('No')</button>
                        <button class="btn btn--sm btn--white confirmUnsubscribe" type="button">@lang('Yes')</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (request()->routeIs('preview.playlist.videos'))
        @include('Template::partials.gateway_modal')
    @endif
@endsection
@if (auth()->check())
    @push('script')
        <script>
            (function($) {
                'use strict';
                $(document).on('click', 'button.cta', function() {
                    $(this).addClass('active');
                    setTimeout(() => {
                        $(this).removeClass('active');
                    }, 300);
                });
                // for subscribe
                $(document).on('click', '.unSubcriberBtn', function() {
                    $('#unSubcriberModal').modal('show');
                });

                $(document).on('click', '.confirmUnsubscribe', function() {
                    subscribers();
                    $('#unSubcriberModal').modal('hide');
                });

                $(document).on('click', '.subcriberBtn', function() {
                    subscribers();
                });

                function subscribers() {

                    $.ajax({
                        type: "post",
                        url: "{{ route('user.subscribe.channel', $user->id) }}",
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        success: function(response) {



                            if (response.remark === 'subscribed') {
                                $('.subscriber-btn').html(`
                  <button class="btn btn--white outline unSubcriberBtn"> @lang('Unsubscribe')</button> `)
                                $('.subscribeCount').text(response.data.subscriber_count)

                            } else if (response.remark === 'unsubscribe') {
                                $('.subscriber-btn').html(`
                 <button class="btn cta btn--white subcriberBtn">@lang('Subscribe')
                                        <span class="shape">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </span></button>
                `)
                                $('.subscribeCount').text(response.data.subscriber_count)
                            } else {

                                notify('error', response.message);
                            }
                        }

                    });
                }

            })(jQuery);
        </script>
    @endpush
@endif

@push('style')
    <style>
        .purchase-price {
            color: hsl(var(--white));
            font-weight: 600;
        }

        .premium-stock-text {
            text-decoration: none;
        }
    </style>
@endpush
