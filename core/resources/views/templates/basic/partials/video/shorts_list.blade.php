@foreach ($shortVideos as $video)
    <div class="short-item">
        <a href="{{ route('preview.channel', $video->user->slug) }}" class="short-item__channel">
            <img class="fit-image" src="{{ getImage(getFilePath('userProfile') . '/' . @$video->user?->image) }}"
                 alt="Short Author">
        </a>
        <a href="{{ route('short.play', [$video->id, $video->slug]) }}"  class="short-item__thumb shortsAutoPlay">
            <video class="shorts-video-player" controls playsinline >
                <source src=" {{ route('short.path', encrypt($video->id)) }}" type="video/mp4" />
            </video>
           @include('Template::partials.video.video_loader')
        </a>
        <div class="short-item__content">
            <h5 class="short-item__title">
                <a href="{{ route('short.play', [$video->id, $video->slug]) }}">{{ __($video->title) }}</a>
            </h5>
        </div>
        <span class="short-item__view">{{ formatNumber($video->views) }} @lang('views')</span>
    </div>
@endforeach
