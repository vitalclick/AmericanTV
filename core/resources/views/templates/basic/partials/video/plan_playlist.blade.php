@foreach ($planPlaylists as $playlist)
    @php
        $video = @$playlist?->videos()->where('status', Status::PUBLISHED)->first();
    @endphp
    <a href="{{ route('video.play', [$video->id, $video->slug]) }}?list={{ @$playlist->slug }}&index=1&plan={{ @$plan->slug }}"
       class="playlist-card">
        <div class="playlist-card__content">
            <div class="playlist-card__thumb">
                <img class="fit-image" src="{{ getImage(getFilePath('thumbnail') . '/' . @$video->thumb_image) }}"
                     alt="Playlist Image">
                <p class="playlist-count">@lang('Videos') : {{ count($playlist->videos) }}</p>

            </div>
            <div class="playlist-card__body">

                <p class="playlist-card__name">@lang('Playlist')</p>
                <div class="d-flex justify-content-between gap-3">

                    <h5 class="playlist-card__title">{{ __($playlist->title) }}</h5>
                    @if ($playlist->user_id == auth()->id())
                        <div class="playlist-card__btn">
                            <button class="btn editPlaylist" data-playlist="{{ $playlist }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-pen-line">
                                    <path
                                          d="m18 5-2.414-2.414A2 2 0 0 0 14.172 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2" />
                                    <path
                                          d="M21.378 12.626a1 1 0 0 0-3.004-3.004l-4.01 4.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z" />
                                    <path d="M8 18h1" />
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
                <p class="playlist-card__desc">
                    {{ __(strLimit($playlist->description, 200)) }}
                </p>

            </div>
        </div>
    </a>
@endforeach

@push('style')
    <style>
        .playlist-card {
            width: 100% !important;
        }

        .playlist-card__content {
            flex-direction: row;
            overflow: unset !important;
        }

        .playlist-wrapper {
            max-height: 600px;
            overflow-y: scroll;
        }

        .playlist-card__thumb img {
            border-radius: 8px;
        }

        .playlist-card__thumb {
            position: relative;
            width: 170px;
        }

        .playlist-card__body {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .playlist-card__thumb::before {
            content: "";
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 32px);
            height: 100%;
            background: hsl(var(--white) / 0.1);
            z-index: -2;
            border-radius: 12px;
        }

        .playlist-card__thumb::after {
            content: "";
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 16px);
            height: 100%;
            background: var(--border-color);
            z-index: -1;
            border-radius: 10px;
        }

        .playlist-wrapper .playlist-card::after,
        .playlist-wrapper .playlist-card::before {
            display: none !important;
        }
    </style>
@endpush
