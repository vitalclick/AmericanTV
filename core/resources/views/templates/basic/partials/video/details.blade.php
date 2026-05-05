<div class="upload-details">
    <form class="fetch-youtube-form" method="post">
        @csrf
        <label class="form--label">@lang('Fetch Detail From Youtube')</label>
        <div class="input-group ">
            <input class="form-control form--control" name="video_id" type="text" value=""
                placeholder="Youtube Video id">
            <button class="input-group-text btn--base btn btn--sm">@lang('Fetch Data')</button>
        </div>
    </form>


    <div class="upload-details-wrapper">
        <div class="upload-details__left">
            <div class="uploaded-item">
                <div class="uploaded-item__thumb">


                    <video class="video-player" controls>
                        @if (!$video->is_shorts_video)
                            @foreach ($video->videoFiles as $file)
                                <source src="{{ getVideo($file->file_name, $video) }}" type="video/mp4"
                                    size="{{ $file->quality }}" />
                            @endforeach
                        @else
                            <source src="{{ getVideo($video->video, $video) }}" type="video/mp4" />
                        @endif
                    </video>
                </div>
                <div class="uploaded-item__content">

                    @if (!$video->is_shorts_video && !$video->storage_id)
                        <canvas id="videoCanvas" style="display:none;"></canvas>
                        <button class="thumbGenerate mt-3">
                            <i class="las la-magic icon"></i> @lang('Generate Thumbnail')
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="upload-details__right">
            <form class="video-upload-form" action="{{ route('user.' . $action . '.details.submit', $video->id) }}"
                method="post" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <div class="d-flex justify-content-between">
                        <label class="form--label">@lang('Video Title')</label> <a class="buildSlug"
                            href="javescript:void(0)"><i class="las la-link"></i>@lang('Make Slug')</a>
                    </div>
                    <input class="form--control form-control" name="title" type="text"
                        value="{{ old('title', @$video->title) }}" required>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between">
                        <label class="form--label">@lang('slug')</label>
                        <div class="video-slug-verification d-none"></div>
                    </div>
                    <input class="form--control checkSlug" name="slug" type="text"
                        value="{{ old('slug', @$video->slug) }}" required>
                </div>

                @if (!$video->is_shorts_video)
                    <div class="form-group">
                        <label class="form--label">@lang('Playlist')</label>
                        <div class="d-flex gap-3 flex-wrap flex-md-nowrap">
                            <select class="form--control" name="playlist">
                                <option value="">@lang('Select One')</option>
                                @if ($video->playlist)
                                    @foreach ($playlists as $playlist)
                                        <option value="{{ @$playlist->id }}"
                                            @if ($video->playlist_id == $playlist->id) selected @endif>
                                            {{ __(@$playlist->title) }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <button type="button" class="input-group-text btn btn--base adPlayList"
                                data-bs-toggle="modal">@lang('Add New')</button>
                        </div>
                    </div>
                @endif
                <div class="form-group">
                    <label class="form--label">@lang('Video Description')</label>
                    <textarea class="form--control nicEdit" name="description">{{ old('description', @$video->description) }}</textarea>
                </div>

                <div class="form-group select2-parent">
                    <label for="" class="form--label">@lang('Only for Playlist')</label>
                    <div class="check-type-wrapper">
                        <label for="playlist-01" class="check-type check-type-success">
                            <input class="check-type-input" type="radio" value="1" name="is_only_playlist"
                                id="playlist-01" @checked(@$video->is_only_playlist == 1)>
                            <span class="check-type-icon">
                                <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor" stroke-linecap="round"
                                        class="check">
                                    </path>
                                </svg>
                            </span>
                            <span class="check-type-label">@lang('Yes')</span>
                        </label>

                        <label for="playlist-02" class="check-type check-type-warning">
                            <input class="check-type-input" type="radio" value="0" name="is_only_playlist"
                                id="playlist-02" @checked(@$video->is_only_playlist == 0)>
                            <span class="check-type-icon">
                                <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor" stroke-linecap="round"
                                        class="check">
                                    </path>
                                </svg>
                            </span>
                            <span class="check-type-label">@lang('No')</span>
                        </label>
                    </div>
                </div>
                @if (!$video->is_shorts_video)
                    <div class="upload-thumbnail">
                        <h6 class="upload-thumbnail__title">@lang('Thumbnail')</h6>
                        <p class="upload-thumbnail__desc">
                            @lang('Select or upload a picture that show’s what’s in your video. A good thumbnail stands out and draws viewer\'s attention.')
                            <small class="test">@lang('Thumbnail size will be'): {{ getFileSize('thumbnail') }} px</small>

                        </p>

                        <div class="bottom">
                            <div class="bottom__inner">
                                <div class="video-thumb-upload">
                                    <div class="video-thumb-upload__avatarPreview">
                                        <div class="video-thumb-upload__thumbnailPreview preview">
                                            @if ($video->thumb_image)
                                                <img src="{{ getImage(getFilePath('thumbnail') . '/thumb_' . $video->thumb_image, getFileThumb('thumbnail')) }}"
                                                    alt="image">
                                            @endif
                                        </div>
                                    </div>
                                    <div class="video-thumb-upload__avatarEdit">
                                        <input id="upload-image" name="thumb_image" type="file"
                                            accept=".png, .jpg, .jpeg">
                                        <label class="video-thumb-upload__box" for="upload-image">
                                            <span class="icon"><i class="vti-add-photo"></i></span>
                                            <span class="text">@lang('Upload Thumbnail')</span>
                                        </label><br>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="form-group upload-buttons mb-0">

                    @if (@$video->is_shorts_video)
                        <a class="btn btn--dark"
                            href="{{ route('user.shorts.upload.form', @$video->id) }}">@lang('Previous') </a>
                    @else
                        <a class="btn btn--dark"
                            href="{{ route('user.video.upload.form', @$video->id) }}">@lang('Previous') </a>
                    @endif

                    <button class="btn btn--base" type="submit">@lang('Next Step')</button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Modal -->
<div class="scale-style fade custom--modal show modal" id="thumbModal" aria-labelledby="thumbModalLabel"
    aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered  modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-5" id="thumbModalLabel">@lang('Auto-generated thumbnail')</h5>
                <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-3 text">@lang('Select an image from your video to use as a thumbnail')</p>
                <div class="d-flex thumbPreview gap-2">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn--sm btn--white outline" data-bs-dismiss="modal"
                    type="button">@lang('Close')</button>
                <button class="btn btn--sm btn--white selectThumb" type="button">@lang('Done')</button>
            </div>

        </div>
    </div>
</div>

@include($activeTemplate . 'partials.playlist_modal')

@push('style')
    <style>
        .thumbnail-option {
            border: 2px solid transparent;
        }

        .la-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .video-video-slug-verification {
            font-size: .75rem;
        }

        .thumb-border {
            border: 2px solid hsl(var(--base))
        }

        .select2-container .select2-selection--single {
            height: 45px !important;
        }

        .buildSlug {
            font-size: 0.75rem;
            color: hsl(var(--heading-color));
        }


        .nicEdit-main {
            outline: none !important;
            width: 100% !important;
        }

        .nicEdit-custom-main {
            border-right-color: #cacaca73 !important;
            border-bottom-color: #cacaca73 !important;
            border-left-color: #cacaca73 !important;
            border-radius: 0 0 5px 5px !important;
            width: 100% !important;
        }

        .nicEdit-panelContain {
            border-color: #cacaca73 !important;
            border-radius: 5px 5px 0 0 !important;
            background-color: #fff !important
        }

        .nicEdit-buttonContain div {
            background-color: #fff !important;
            border: 0 !important;
        }

        .nicedit-textarea>div {
            width: 100% !important;
        }

        .profile-item__content .emojionearea.form--control {
            height: 45px !important;
        }

        .emojionearea.emojionearea-inline>.emojionearea-editor {
            top: 7px !important;
        }

        @media screen and (max-width: 575px) {
            .modal-title {
                margin-bottom: 15px;
            }
        }

        .emojionearea.emojionearea-inline>.emojionearea-button {
            top: unset !important;
        }
    </style>
@endpush

@push('style-lib')
    <link href="{{ asset('assets/global/css/plyr.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/global/css/select2.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
    <script src="{{ asset($activeTemplateTrue . 'js/nicEdit.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {

            "use strict";

            $(document).ready(function() {
                const playlistField = $('[name="playlist"]');

                playlistField.select2({
                    ajax: {
                        url: "{{ route('user.video.fatch.playlist') }}",
                        type: "get",
                        dataType: 'json',
                        delay: 1000,
                        data: function(params) {
                            return {
                                search: params.term,
                                page: params.page,
                                rows: 5
                            };
                        },
                        processResults: function(response, params) {
                            params.page = params.page || 1;
                            return {
                                results: response,
                                pagination: {
                                    more: params.page < response.length
                                }
                            };
                        },
                        cache: false
                    },
                    dropdownParent: playlistField.parent(),
                    closeOnSelect: true
                });
            });



            $(document).ready(function() {
                $(document).find('.plyr__controls').addClass('d-none');
            });


            const controls = [
                'play-large',
                'rewind',
                'play',
                'fast-forward',
                'progress',
                'mute',
                'volume',
                'settings',
                'quality',
                'fullscreen'
            ];

            const isShorts = "{{ $video->is_shorts_video }}";


            var ratio = null;

            if (isShorts == 1) {
                ratio = '9:16';
            } else {
                ratio = '16:9';
            }


            $(document).ready(function() {
                const player = new Plyr('.video-player', {
                    controls,
                    ratio: ratio
                });
            });


            $(document).on('change', '#upload-image', event => {
                var file = event.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('.preview').html(`<img src="${e.target.result}" />`);

                    }
                    reader.readAsDataURL(file);
                }
            });



            $(document).on('click', '.thumbnail-option', function() {
                $('.thumbnail-option').each(function(index, element) {
                    $(element).removeClass('thumb-border')
                });
                $(this).addClass('thumb-border')
            });


            $(document).on('click', '.selectThumb', function() {
                const modal = $('#thumbModal');
                var base64Image = $('.thumb-border').data('image');


                selectThumbnail(base64Image)
                modal.modal('hide');
            });



            $(document).on('submit', '.fetch-youtube-form', function(e) {
                e.preventDefault();
                var shorts = "{{ $video->is_shorts_video }}"


                $.ajax({
                    type: "post",
                    url: "{{ route('user.video.fetch.data') }}",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function(response) {
                        if (response.status == 'success') {
                            $('[name="title"]').val(response.data.title);
                            var description = response.data.description.replace(/\n/g, '<br>');
                            $('.nicEdit-main').html(description);
                            if (!shorts) {
                                selectThumbnail(response.data.thumb_base64)
                            }

                            notify('success', response.message.success);
                        } else {
                            notify('error', response.message.error);
                        }

                    }
                });
            });





            function selectThumbnail(base64Image) {
                const byteString = atob(base64Image.split(',')[1]);
                const mimeString = base64Image.split(',')[0].split(':')[1].split(';')[0];
                const ab = new ArrayBuffer(byteString.length);
                const ia = new Uint8Array(ab);

                for (let i = 0; i < byteString.length; i++) {
                    ia[i] = byteString.charCodeAt(i);
                }

                const blob = new Blob([ab], {
                    type: mimeString
                });
                const file = new File([blob], "thumbnail.jpg", {
                    type: mimeString
                });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);

                $('#upload-image')[0].files = dataTransfer.files;

                $('.preview').html(`<img src="${base64Image}" />`);
            }



            $(document).ready(function() {
                $(".thumbGenerate").on('click', function(event) {
                    const mainVideoSrc = $('.video-player').find('source').attr('src');
                    var fileUrl = mainVideoSrc;
                    var video = document.createElement('video');
                    var canvas = document.getElementById('videoCanvas');


                    $('.thumbGenerate').find('.icon').toggleClass('la-spinner', 'la-magic')

                    if (!canvas) {
                        console.error('Canvas element not found');
                        return;
                    }

                    var ctx = canvas.getContext('2d');
                    var thumbnails = [];
                    var times = [0.1, 0.3, 0.5, 0.7,
                        0.9
                    ];

                    video.preload = 'metadata';
                    video.src = fileUrl;

                    video.onloadedmetadata = function() {
                        generateThumbnails(0);
                    };


                    function generateThumbnails(index) {
                        if (index >= times.length) return;

                        video.currentTime = times[index] * video.duration;



                        video.onseeked = function() {
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);


                            thumbnails.push(canvas.toDataURL('image/png'));

                            if (thumbnails) {

                                const modal = $('#thumbModal');

                                modal.find('.thumbPreview').empty();
                                $.each(thumbnails, function(index, thumbnail) {
                                    modal.find('.thumbPreview').append(`
                            <div class="thumbnail-option " data-image="${thumbnail}">
                      <img src="${thumbnail}"  class="auto-thumbnail">
                    </div>

                            `);
                                });

                                setTimeout(() => {
                                    $('.thumbGenerate').find('.icon').toggleClass(
                                        'la-spinner',
                                        'la-magic')
                                    modal.modal('show')

                                }, 1000);
                            }

                            generateThumbnails(index + 1);
                        };
                    }
                });
            });



            $('.adPlayList').on('click', function() {
                $('#playlistModal').modal('show');
            });



            $(document).ready(function() {
                $('.playlistForm').on('submit', function(e) {
                    e.preventDefault();

                    $.ajax({
                        url: "{{ route('user.playlist.save') }}",
                        method: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            if (response.status == 'success') {
                                notify('success', response.message.success)
                                $('#playlistModal').modal('hide');
                            } else {
                                notify('error', response.message.error)
                            }
                        }

                    });
                });
            });






            bkLib.onDomLoaded(function() {
                $(".nicEdit").each(function(index) {
                    $(this).attr("id", "nicEditor" + index);
                    new nicEditor({
                        fullPanel: true
                    }).panelInstance('nicEditor' + index, {
                        hasPanel: true
                    });
                });
            });


            $('.buildSlug').on('click', function() {
                let closestForm = $(this).closest('form');
                let title = closestForm.find(`[name="title"]`).val();
                closestForm.find('.checkSlug').val(title);
                closestForm.find('.checkSlug').trigger('input');
            });

            $('.checkSlug').on('input', function() {
                let closestForm = $(this).closest('form');

                let slug = $(this).val();
                let id = "{{ $video->id }}";

                let isShort = 0;
                if (Number("{{ $video->is_shorts_video }}")) {
                    isShort = 1;
                }
                slug = slug.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
                $(this).val(slug);
                if (slug) {
                    closestForm.find('.video-slug-verification').removeClass('d-none');
                    closestForm.find('.video-slug-verification').html(`
                            <small class="text--info"><i class="las la-spinner la-spin"></i> @lang('Verifying')</small>
                        `);
                    $.get("{{ route('user.video.check.slug') }}", {
                        slug: slug,
                        id: id,
                        is_short: isShort
                    }, function(response) {

                        if (!response.exists) {
                            closestForm.find('.video-slug-verification').html(`
                                    <small class="text--success"><i class="las la-check"></i> @lang('Verified')</small>
                                `);
                        }
                        if (response.exists) {
                            closestForm.find('.video-slug-verification').html(`
                                    <small class="text--danger"><i class="las la-times"></i> @lang('Slug already exists')</small>
                                `);
                        }
                    });
                } else {
                    closestForm.find('.video-slug-verification').addClass('d-none');
                }
            });

        })(jQuery)
    </script>
@endpush
