@foreach ($relatedVideos as $index => $relatedVideo)
    <div class="video-item @if (request()->index == $index + 1) active @endif">
        <span class="serial-no"><span class="serial">{{ $index + 1 }}</span></span>
        <a data-video_id="{{ $relatedVideo->id }}" href="{{ getPlanVideoUrl($plan) }}"
            class="video-item__thumb  @if ($relatedVideo->showEligible() && !$relatedVideo->audience) autoPlay @endif">
            <video class="related-video-player video-player" controls
                data-poster="{{ getImage(getFilePath('thumbnail') . '/' . $relatedVideo->thumb_image) }}">

            </video>
            @include('Template::partials.video.video_loader')
            @if (!$relatedVideo->showEligible())
                <span class="video-item__price">
                    <span class="text">@lang('Only')</span>
                    {{ gs('cur_sym') }}{{ showAmount($relatedVideo->price, currencyFormat: false) }}
                </span>

                <div class="premium-icon releted-pre-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="16" height="16"
                        aria-hidden="true" class="_24ydrq0 _1286nb17o _1286nb12r6">
                        <path
                            d="M486.2 50.2c-9.6-3.8-20.5-1.3-27.5 6.2l-98.2 125.5-83-161.1C273 13.2 264.9 8.5 256 8.5s-17.1 4.7-21.5 12.3l-83 161.1L53.3 56.5c-7-7.5-17.9-10-27.5-6.2C16.3 54 10 63.2 10 73.5v333c0 35.8 29.2 65 65 65h362c35.8 0 65-29.2 65-65v-333c0-10.3-6.3-19.5-15.8-23.3">
                        </path>
                    </svg>
                </div>
            @endif
        </a>
        <div class="video-item__content">
            <h6 class="title">
                <a href="{{ getPlanVideoUrl($plan) }}">{{ __($relatedVideo->title) }}</a>
            </h6>
            <a href="{{ route('preview.channel', $video->user->slug) }}"
                class="channel">{{ __($relatedVideo->user->channel_name) }}</a>
            <div class="meta">
                <span class="view">{{ formatNumber($relatedVideo->views) }} @lang('views')</span>
                <span class="date">{{ $relatedVideo->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>
@endforeach

@push('style')
    <style>
        .releted-pre-icon {
            top: 5px;
            left: 5px;
        }

        .serial-no {
            flex-shrink: 0;
            font-size: 0.75rem;
            color: hsl(var(--white) / .6);
            padding-inline: 8px;
        }

        @media (max-width: 424px) {
            .serial-no {
                display: none;
            }
        }

        .play-video .secondary__playlist .video-item {
            padding: 4px 6px;
            margin-bottom: 7px !important;
        }

        .play-video .secondary__playlist .video-item.active,
        .play-video .secondary__playlist .video-item:hover {
            background-color: hsl(var(--white) / .1);
        }
        .video-item.active .serial-no {
            position: relative;
            margin: 0 4px;
        }
        .video-item.active .serial-no .serial {
            display: none;
        }
        .video-item.active .serial-no::after {
            position: absolute;
            content: "\f04b";
            font-family: "Font Awesome 6 Free";
            font-weight: 700;
            top: 50%;
            left: 0;
            transform: translateY(-50%);
        }
        .play-video .secondary__playlist .video-item__content .title a {
            line-height: 1.3;
        }
        .video-item__price {
            min-height: 40px;
            font-size: 12px;
        }
    </style>
@endpush
