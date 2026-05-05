@foreach ($playlists as $playlist)
    @php
        if (request()->routeIs('user.playlist.index') || request()->routeIs('user.playlist.load')) {
            $playlistUrl = route('user.playlist.videos', @$playlist->slug);
        } elseif (request()->routeIs('preview.playlist') || request()->routeIs('user.channel.playlist.fetch')) {
            $playlistUrl = route('preview.playlist.videos', [@$playlist->slug, @$playlist->user->slug]);
        }

        $isPurchased = true;
        if ($playlist->playlist_subscription) {
            $isPurchased = false;
        }

        if (auth()->check()) {
            $user = auth()->user();
            if ($playlist->playlist_subscription) {
                $isPurchased = in_array($playlist->id, $user->purchasedPlaylistId);
            }
        }
    @endphp

    <div class="playlist-card">
        <a href="{{ $playlistUrl }}" class="playlist-card__wrapper">
            <div class="playlist-card__content">
                <div class="playlist-card__thumb">
                    <img class="fit-image"
                         src="{{ getImage(getFilePath('thumbnail') . '/' . @$playlist?->videos()->where('status', Status::PUBLISHED)->first()->thumb_image) }}"
                         alt="Playlist Image">
                    <p class="playlist-count">@lang('Videos') : {{ count($playlist->videos) }}</p>
                </div>
                <div class="playlist-card__body">
                    <div class="d-flex justify-content-between gap-3">
                        <p class="playlist-card__name">@lang('Playlist')</p>
                        @if ($playlist->playlist_subscription == Status::YES && gs('is_playlist_sell'))
                            @if (!auth()->user() || $playlist->user_id !== $user->id)
                                <div>
                                    <div class="left">
                                        {{ gs('cur_sym') }}{{ showAmount($playlist->price, currencyFormat: false) }}
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                    <div>
                        <h5 class="playlist-card__title">{{ __($playlist->title) }}</h5>
                        @if ($playlist->user_id == auth()->id() && !request()->routeIs('user.playlist.index'))
                            <div class="playlist-card__btn">
                                <button class="btn editPlaylist" data-playlist="{{ $playlist }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round"
                                         class="lucide lucide-file-pen-line">
                                        <path
                                              d="m18 5-2.414-2.414A2 2 0 0 0 14.172 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2" />
                                        <path
                                              d="M21.378 12.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z" />
                                        <path d="M8 18h1" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                        @if ($playlist->playlist_subscription == Status::YES && gs('is_playlist_sell'))
                            <div class="premium-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16"
                                     height="16" aria-hidden="true" class="_24ydrq0 _1286nb17o _1286nb12r6">
                                    <path
                                          d="M486.2 50.2c-9.6-3.8-20.5-1.3-27.5 6.2l-98.2 125.5-83-161.1C273 13.2 264.9 8.5 256 8.5s-17.1 4.7-21.5 12.3l-83 161.1L53.3 56.5c-7-7.5-17.9-10-27.5-6.2C16.3 54 10 63.2 10 73.5v333c0 35.8 29.2 65 65 65h362c35.8 0 65-29.2 65-65v-333c0-10.3-6.3-19.5-15.8-23.3">
                                    </path>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <p class="playlist-card__desc">
                        {{ __(strLimit($playlist->description, 200)) }}
                    </p>
                </div>
            </div>
        </a>

        @if ($playlist->playlist_subscription == Status::YES && gs('is_playlist_sell'))
            @if ($isPurchased)
                <div class="btn btn--success btn--sm premium-stock-text">
                    @lang('Purchased')
                </div>
            @elseif(!auth()->user() || $playlist->user_id !== $user->id)
                <div>
                    <div class="btn btn--base btn--sm premium-stock-text purchase-now"
                         data-resource="{{ $playlist }}">
                        @lang('Purchase Now')
                    </div>
                </div>
            @endif
        @endif
    </div>
@endforeach


@if (request()->routeIs(['preview.playlist.videos', 'preview.playlist']))
    @include('Template::partials.gateway_modal')
@endif

@push('style')
    <style>
        .premium-stock-text {
            font-weight: 500;
            position: absolute;
            top: 11px;
            right: 13px;
            padding: 5px 10px;
            text-decoration: none;
            font-size: 12px;
        }
    </style>
@endpush
