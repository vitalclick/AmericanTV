@foreach ($videos as $index => $video)
    <div class="video-item">
        <a data-video_id="{{ $video->id }}"
            class="video-item__thumb    @if ($video->showEligible() && !$video->audience) autoPlay @endif"
            href="{{ route('video.play', [$video->id, $video->slug]) }} @if (@$playlist) ?list={{ @$playlist->slug }}&index={{ $index + 1 }} @endif">
            @if ($video->showEligible())
                <video class="video-player" controls playsinline
                    data-poster="{{ getImage(getFilePath('thumbnail') . '/thumb_' . $video->thumb_image) }}">
                </video>
                @include('Template::partials.video.video_loader')
            @else
                <img src="{{ getImage(getFilePath('thumbnail') . '/thumb_' . $video->thumb_image) }}" alt="Video Thumb">
                <span class="video-item__price"><span
                        class="text">@lang('Only')</span>{{ gs('cur_sym') }}{{ showAmount($video->price, currencyFormat: false) }}</span>
                <div class="premium-icon">
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
            <a class="video-item__channel-author" href="{{ route('preview.channel', $video->user->slug) }}">
                <img class="fit-image"
                    src="{{ getImage(getFilePath('userProfile') . '/' . $video->user->image, isAvatar: true) }}"
                    alt="image">
            </a>
            <a class="channel"
                href="{{ route('preview.channel', $video->user->slug) }}">{{ __($video->user->channel_name) }}</a>
            <h5 class="title">
                <a
                    href="{{ route('video.play', [$video->id, $video->slug]) }}@if (@$playlist) ?list={{ @$playlist->slug }}&index={{ $index + 1 }} @endif ">{{ __($video->title) }}</a>
            </h5>
            <div class="meta">
                <span class="view">{{ formatNumber($video->views) }} @lang('views')</span>
                <span class="date">{{ $video->created_at->diffForHumans() }}</span>
                @if (request()->routeIs('preview.playlist.videos') && $user->id == auth()->id())
                    <div class="playlist-card__btn">
                        <a href="javascript:void(0)"
                            data-action="{{ route('user.playlist.video.remove', ['video_id' => $video->id, 'playlist_id' => @$playlist->id]) }}"
                            data-question="@lang('Are you sure you want to remove this video from the playlist?')" class="btn confirmationBtn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-trash-2">
                                <path d="M3 6h18" />
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
                                <line x1="10" x2="10" y1="11" y2="17" />
                                <line x1="14" x2="14" y1="11" y2="17" />
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endforeach

<x-confirmation-modal frontend="true" />

@if (!request()->routeIs('home'))
    @push('script')
        <script>
            $(document).ready(function() {
                playersInitiate()
            });

            const controls = [

            ];


            function playersInitiate() {
                const players = Plyr.setup('.video-player', {
                    controls,
                    ratio: '16:9',
                    muted: true,
                });
            }
        </script>
    @endpush
@endif
