<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ gs('site_name') }}</title>

    <link href="{{ asset('assets/global/css/plyr.css') }}" rel="stylesheet">

</head>

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: var(--body-font);
        color: hsl(var(--body-color));
        word-break: break-word;
        background-color: hsl(var(--body-background));
        min-height: 100vh;
        display: flex;

        margin-bottom: 0px !important;
        flex-direction: column;
    }

    .video-container {
        position: relative;
        display: inline-block;
    }

    .author-overlay {
        position: absolute;
        top: 10px;
        /* Adjust this to move up/down */
        left: 10px;
        /* Adjust this to move left/right */
        background-color: rgba(0, 0, 0, 0.5);
        /* Transparent background */
        color: white;
        padding: 5px;
        border-radius: 5px;
        display: flex;
        align-items: center;
    }

    .author-image {
        width: 30px;
        /* Size of the author image */
        height: 30px;
        border-radius: 50%;
        /* Make it circular */
        margin-right: 8px;
    }

    .author-name {
        font-size: 14px;
        font-weight: bold;
    }
</style>

<body>

    <div class="video-container" style="position: relative;">
        <video class="video-player" data-poster="{{ getImage(getFilePath('thumbnail') . '/' . $video->thumb_image) }}"
            controls @if ($video->stock_video) data-video_id="{{ $video->id }}" @endif>
            @foreach ($video->videoFiles as $file)
                <source src="{{ getVideo($file->file_name, $video) }}" type="video/mp4"
                    size="{{ $file->quality }}" />
            @endforeach
            @foreach ($video->subtitles as $subtitle)
                <track src="{{ getImage(getFilePath('subtitle') . '/' . $subtitle->file) }}"
                    srclang="{{ $subtitle->language_code }}" kind="captions" label="{{ $subtitle->caption }}" default />
            @endforeach
        </video>
        <a class="author-overlay" href="{{ route('preview.channel',$video->user->slug) }}" target="__blank">
            <img class="author-image" src="{{ getImage(getFilePath('userProfile') . '/' . $video->user->image) }}"
                alt="{{ $video->user->channel_name }}">
            <span class="author-name">{{ $video->user->channel_name }}</span>
        </a>

    </div>

    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>


    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>

    <script>
        (function($) {
            'use strict';

            let controls = [
                'play',
                'fast-forward',
                'progress',
                'duration',
                'mute',
                'settings',
                'fullscreen',
            ]
            $(document).ready(function() {

                const singleplayer = new Plyr('.video-player', {
                    controls,
                    autoplay: true,
                    ratio: '16:9',
                });

            });



        })(jQuery);
    </script>

</body>

</html>
