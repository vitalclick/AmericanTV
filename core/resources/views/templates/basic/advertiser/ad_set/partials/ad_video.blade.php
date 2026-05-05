<div class="advertisement-card">

    <div class="advertisement-card__content">
        <div class="advertisement-card__tab">
            <ul class="nav nav-pills custom--tab tab-three" id="pills-tabTwo" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-device-tab" data-bs-toggle="pill" data-bs-target="#tab-device"
                        type="button" role="tab" aria-controls="tab-device"
                        aria-selected="true">@lang('Select From Device')</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-channel-tab" data-bs-toggle="pill" data-bs-target="#tab-channel"
                        type="button" role="tab" aria-controls="tab-channel"
                        aria-selected="false">@lang('Select From Channel')</button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content" id="pills-tabContentTwo">
        <!-- Device Upload Tab -->
        <div class="tab-pane fade show active" id="tab-device" role="tabpanel" aria-labelledby="tab-device-tab"
            tabindex="0">
            <div class="video-information">
                <div class="video-information__item">
                    <div class="video-information__top">
                        <h5 class="video-information__title">@lang('Upload Ads Video')</h5>
                    </div>
                    <div class="form-group">
                        <label class="mediaUploadLabel item-two" for="video-file-device">
                            <div class="upload-placeholder horizontal-upload">
                                <!-- Your SVG and Upload Text -->
                                <span class="icon"> <svg class="" style="enable-background:new 0 0 512 512"
                                        xmlns="http://www.w3.org/2000/svg" version="1.1"
                                        xmlns:xlink="http://www.w3.org/1999/xlink" x="0" y="0" viewBox="0 0 24 24"
                                        xml:space="preserve">
                                        <g>
                                            <g data-name="Layer 2">
                                                <path class="" data-original="#000000"
                                                    d="M17.5 19.75H6a4.75 4.75 0 0 1-.75-9.441V9.5a6.25 6.25 0 0 1 12.5-.244 5.25 5.25 0 0 1-.25 10.494zm-6-15A4.756 4.756 0 0 0 6.75 9.5V11a.75.75 0 0 1-.75.75 3.25 3.25 0 0 0 0 6.5h11.5a3.75 3.75 0 0 0 0-7.5H17a.75.75 0 0 1-.75-.75v-.5a4.756 4.756 0 0 0-4.75-4.75z"
                                                    fill="#currentColor" opacity="1"></path>
                                                <path class="" data-original="#000000"
                                                    d="M12 15.75a.75.75 0 0 1-.75-.75v-4a.75.75 0 0 1 1.5 0v4a.75.75 0 0 1-.75.75z"
                                                    fill="#currentColor" opacity="1"></path>
                                                <path class="" data-original="#000000"
                                                    d="M14 13.75a.744.744 0 0 1-.53-.22L12 12.061l-1.47 1.469a.75.75 0 0 1-1.06-1.06l2-2a.749.749 0 0 1 1.06 0l2 2a.75.75 0 0 1-.53 1.28z"
                                                    fill="#currentColor" opacity="1"></path>
                                            </g>
                                        </g>
                                    </svg></span>
                                <span class="text">@lang('Drag your file(s) or browse')</span>
                                <span>@lang('Supported File is: ') .@lang('mp4'), .@lang('mov'), .@lang('wmv'),
                                    .@lang('flv'), .@lang('avi'), .@lang('mkv')</span>
                            </div>
                            <div class="upload-progress d-none" id="horizontalProgress">
                                <div class="progress-bar">
                                    <div class="bar" style="width: 0%"></div>
                                </div>
                                <span class="progress-text">0%</span>
                                <div class="upload-checkmark d-none">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                            </div>
                            <input class="videoUpload" id="video-file-device" name="ad_video" type="file"
                                accept="video/*">
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Channel Select Tab -->
        <div class="tab-pane fade" id="tab-channel" role="tabpanel" aria-labelledby="tab-channel-tab" tabindex="0">
            <div class="video-information">
                <div class="video-information__item">
                    <div class="video-information__top">
                        <h5 class="video-information__title">@lang('Select Video From Channel')</h5>

                        <div class="form-group mb-0">
                            <input class="form--control" name="search" type="text" placeholder="Search...">
                        </div>

                    </div>
                    <div class="videoList-wrapper ">
                        <div class="text-center d-none spinner mt-4 w-100" id="load-spinner">
                            <i class="las la-spinner"></i>
                        </div>
                        <div class="row videoChildList">
                            @foreach ($videoLists as $videoList)
                                <div class="col-md-6">
                                    <label class="check-type mb-3" for="flexCheck{{ $videoList->id }}">
                                        <input class="check-type-input" id="flexCheck{{ $videoList->id }}"
                                            name="video_id" type="radio" value="{{ $videoList->id }}">
                                        <span class="check-type-icon">
                                            <svg class="check-circle" width="13" height="10"
                                                viewBox="0 0 13 10" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor"
                                                    stroke-linecap="round">
                                                </path>
                                            </svg>
                                        </span>

                                        <img class="check-type-img"
                                            src="{{ getImage(getFilePath('thumbnail') . '/thumb_' . @$videoList->thumb_image) }}"
                                            alt="thumb_image">

                                        <span class="form-check-label">
                                            {{ __($videoList->title) }}
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@push('style')
    <style>
        .upload-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .upload-progress {
            margin-top: 10px;
        }

        .progress-bar {
            width: 100%;
            background: #e5e5e5;
            height: 8px;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .bar {
            height: 100%;
            background-color: #28a745;
            width: 0%;
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 14px;
            font-weight: bold;
        }

        .upload-checkmark {
            margin-top: 10px;
        }

        .upload-checkmark i {
            font-size: 24px;
            color: #28a745;
        }


        .videoList-wrapper {
            max-height: 300px;
            overflow: hidden auto;
        }
    </style>
@endpush

@push('script')
    <script>
        function simulateProgress(progressSelector, textSelector, containerSelector) {
            let progress = 0;
            const interval = setInterval(function() {
                progress += 1;
                $(progressSelector).css('width', progress + '%');
                $(textSelector).text(progress + '%');

                if (progress >= 100) {
                    clearInterval(interval);
                    $(containerSelector + ' .progress-bar').addClass('d-none');
                    $(containerSelector + ' .progress-text').addClass('d-none');
                    $(containerSelector + ' .upload-checkmark').removeClass('d-none');
                }
            }, 30);
        }

        $('#video-file-device').on('change', function() {
            $('.horizontal-upload').addClass('d-none');
            $('#horizontalProgress').removeClass('d-none');
            simulateProgress('#horizontalProgress .bar', '#horizontalProgress .progress-text',
                '#horizontalProgress');
        });


        let currentVideolistPage = "{{ $videoLists->currentPage() }}";

        let lastVideoPage = false;

        var videoList = $('.videoChildList');

        videoList.scroll(function() {
            if (videoList.scrollTop() + videoList.height() >= videoList[0].scrollHeight - 50 && !lastVideoPage) {
                currentVideolistPage++;
                loadVideoList();
            }
        });

        let videoSearchTimer;

        $('input[name="search"]').on('keyup', function() {
            const searchTerm = $(this).val().trim();

            clearTimeout(videoSearchTimer);

            videoSearchTimer = setTimeout(function() {
                currentVideolistPage = 1;
                lastVideoPage = false;
                $('.videoChildList').empty();
                loadVideoList(searchTerm);
            }, 500);
        });

        function loadVideoList(searchTerm = '') {
            const route = "{{ route('user.advertiser.ad.get.video') }}";
            $('#load-spinner').removeClass('d-none');

            $.ajax({
                url: `${route}?page=${currentVideolistPage}&search=${searchTerm}`,
                type: 'GET',
                success: function(response) {



                    $('#load-spinner').addClass('d-none');

                    if (response.status === 'success' && response.data.videoLists.data.length > 0) {
                        $.each(response.data.videoLists.data, function(index, videoList) {

                            var imagePath =
                                "{{ getImage(getFilePath('thumbnail') . '/thumb_' . '12.png') }}";

                            imagePath = imagePath.replace('default.png', 'thumbnail/thumb_' + videoList
                                .thumb_image);

                            var videoHTML = `
                                  <div class="col-md-6">
                                        <label for="flexCheck${videoList.id}" class="check-type mb-3">
                                            <input class="check-type-input" id="flexCheck${videoList.id}" name="video_id" type="radio" value="${videoList.id}">
                                            <span class="check-type-icon">
                                                <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor" stroke-linecap="round">
                                                    </path>
                                                </svg>
                                            </span>
                                            <img class="check-type-img" src="${imagePath}" alt="thumb_image">
                                            <span class="check-type-label">
                                                ${videoList.title}
                                            </span>
                                        </label>
                                    </div>
                                    `;
                            $('.videoChildList').append(videoHTML);
                        });

                        if (currentVideolistPage >= response.data.last_page) {
                            lastVideoPage = true;
                        }
                    } else {


                        $('.videoChildList').html(`
                                <div class="col-12 text-center">
                                    <p class="text-muted">No video found</p>
                                </div>
                             
                             
                             `);
                        lastVideoPage = true;
                    }
                },
                error: function() {
                    $('#loading-spinner').addClass('d-none');
                }
            });
        }
    </script>
@endpush
