@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <form action="{{ route('admin.videos.update', $video->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="col-md-12">

                <div class="card pb-3">
                    <div class="card-body">
                        <div class="row mb-none-30">
                            <div class="col-xl-4     @if ($video->is_shorts_video) col-lg-5 @else col-lg-6 @endif  col-md-12 mb-30">

                                @if ($video->is_shorts_video)
                                    <video class="shorts-video-player" controls>
                                        <source src="{{ getVideo($video->video, $video) }}" type="video/mp4" />
                                    </video>
                                @else
                                    <video class="video-player" controls>
                                        @foreach ($video->videoFiles as $file)
                                            <source src="{{ getVideo($file->file_name, $video) }}" type="video/mp4" size="{{ $file->quality }}" />
                                        @endforeach
                                        @foreach ($video->subtitles as $subtitle)
                                            <track src="{{ getImage(getFilePath('subtitle') . '/' . $subtitle->file) }}" srclang="{{ $subtitle->language_code }}" kind="captions" label="{{ $subtitle->caption }}" />
                                        @endforeach
                                    </video>
                                @endif

                            </div>
                            @if (!$video->is_shorts_video)
                                <div class="col-xl-8 col-lg-6 col-md-12">
                                    <x-image-uploader class="w-100" id="thumb" name="thumb_image" :imagePath="getImage(getFilePath('thumbnail') . '/' . $video->thumb_image)" :size="getFileSize('thumbnail')" :required="false" />

                                </div>
                            @endif
                            <div class=" @if ($video->is_shorts_video) col-xl-8 col-lg-7  @else col-xl-6 @endif ">
                                <div class=" form-group">
                                    <label> @lang('Title') </label>
                                    <a class="buildSlug" href="javescript:void(0)"><i class="las la-link"></i>@lang('Make Slug')</a>

                                    <input class="form-control" name="title" type="text" value="{{ old('title', @$video->title) }}" required>
                                </div>
                                @if ($video->is_shorts_video)
                                    <div class=" form-group">
                                        <label> @lang('Slug') </label>
                                        <div class="slug-verification d-none"></div>

                                        <input class="form-control" name="slug" type="text" value="{{ old('slug', @$video->slug) }}" required>
                                    </div>
                                @endif

                                <div class=" form-group">
                                    <label> @lang('Category') </label>
                                    <select class="form-control select2" name="category" required>
                                        <option value="">@lang('Select Category')</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category', $video->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ __($category->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>



                                <div class=" form-group">
                                    <label>@lang('Visibility')</label>
                                    <select class="form-control select2 " name="visibility" data-minimum-results-for-search="-1" required>
                                        <option value="0" @if ($video->visibility == Status::PUBLIC) selected @endif>@lang('Public')</option>
                                        <option value="1" @if ($video->visibility == Status::PRIVATE) selected @endif>@lang('Private')</option>

                                    </select>
                                </div>




                                <div class="form-group">
                                    <label> @lang('Tags') </label>
                                    <select class="select form--control select2-auto-tokenize" name="tags[]" required multiple>
                                        @foreach (@$video->tags ?? [] as $tag)
                                            <option value="{{ @$tag->tag }}" selected>{{ @$tag->tag }}</option>
                                        @endforeach
                                    </select>
                                </div>


                                @if ($video->is_shorts_video)
                                    <div class="form-group">
                                        <label>@lang('Status')</label>
                                        <select class="form-control select2 " name="status" data-minimum-results-for-search="-1" required>
                                            <option value="1" @if ($video->status == Status::PUBLISHED) selected @endif>@lang('Published')</option>
                                            <option value="2" @if ($video->status == Status::REJECTED) selected @endif>@lang('Rejected')</option>

                                        </select>
                                    </div>

                                    <div class="form-group">

                                        <label class="title-label required">@lang('Audience')</label>
                                        <div class="radio-options d-flex gap-2 ">
                                            <div class="form-check form--radio">
                                                <input class="form-check-input" id="audience01" name="audience" type="radio" value="0" @if (old('audience', $video->audience) == 0) checked @endif>
                                                <label class="form-check-label" for="audience01">@lang('All Ages can view this video')</label>
                                            </div>
                                            <div class="form-check form--radio">
                                                <input class="form-check-input" id="audience02" name="audience" type="radio" value="1" @if (old('audience', $video->audience) == 1) checked @endif>
                                                <label class="form-check-label" for="audience02">@lang('Only 18')+</label>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if (!$video->is_shorts_video)
                                    <div class="form-group">
                                        <label class="form--label">@lang('Video Description')</label>
                                        <textarea class="form--control nicEdit" name="description">{{ old('description', @$video->description) }}</textarea>
                                    </div>
                                @endif

                            </div>
                            @if ($video->is_shorts_video)
                                <div class="form-group mt-0">
                                    <label class="form--label">@lang('Video Description')</label>
                                    <textarea class="form--control nicEdit" name="description">{{ old('description', @$video->description) }}</textarea>
                                </div>
                            @endif



                            @if (!$video->is_shorts_video)
                                <div class="col-xl-6">
                                    <div class=" form-group">
                                        <label> @lang('Slug') </label>
                                        <div class="slug-verification d-none"></div>

                                        <input class="form-control" name="slug" type="text" value="{{ old('slug', $video->slug) }}" required>
                                    </div>

                                    <div class="row">

                                        <div class="col-md-6 form-group">
                                            <label>@lang('Stock Video')</label>
                                            <select name="audience" class="form-control">
                                                <option value="0" @selected(old('audience', @$video->audience) == 0)>@lang('All Ages can view this video')</option>
                                                <option value="1" @selected(old('audience', @$video->audience) == 1)>@lang('Only 18+')</option>
                                            </select>
                                        </div>


                                        <div class="col-md-12  col-lg-6">
                                            <div class="form-group">
                                                <label>@lang('Status')</label>
                                                <select class="form-control select2 " name="status" data-minimum-results-for-search="-1" required>
                                                    <option value="1" @if ($video->status == Status::PUBLISHED) selected @endif>@lang('Published')</option>
                                                    <option value="2" @if ($video->status == Status::REJECTED) selected @endif>@lang('Rejected')</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label>@lang('Stock Video')</label>
                                            <select name="stock_video" class="form-control">
                                                <option value="1" @selected(old('stock_video', @$video->stock_video) == 1)>@lang('Yes')</option>
                                                <option value="0" @selected(old('stock_video', @$video->stock_video) == 0)>@lang('No')</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 form-group ">
                                            <label class="title-label" for="stock01">
                                                @lang('Price')
                                            </label>
                                            <div class="input-group">
                                                <input class="form-control" name="price" type="number" value="{{ old('price', getAmount($video->price)) }}" placeholder="Price" step="any">
                                                <span class="input-group-text">{{ __(gs('cur_text')) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    @if (!$video->is_shorts_video)
                                        <div class="form-group">
                                            <label>@lang('Make Tranding')</label>
                                            <input name="is_trending" data-width="100%" data-size="large" data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-height="35" data-on="@lang('Yes')" data-off="@lang('No')" type="checkbox" @if ($video->is_trending) checked @endif>
                                        </div>
                                        <div class="col-md-12  text-end">
                                            <button class="add-subtitle-btn btn btn-sm btn-outline--primary addSubtitleBtn" type="button"><i
                                                   class="las la-plus"></i>@lang('Add New')</button>
                                        </div>
                                        <label for="">@lang('Subtitle') <span>(@lang('Supported File: .vtt'))</span> </label>
                                        <div class=" subtitle-wrapper">
                                            @foreach (old('caption', $video->subtitles) ?? [] as $key => $subtitle)
                                                <div class="row appendSubtitle">
                                                    <input name="old_subtitle[]" type="hidden" value="{{ @$subtitle->id }}" accept=".vtt">
                                                    <div class="col-md-4 flex-grow-1">
                                                        <div class="form-group">
                                                            <input class="form-control" name="subtitle_file[]" type="file">
                                                            <a href="{{ getImage(getFilePath('subtitle') . '/' . @$subtitle->file) }}" download>@lang('Download Subtitle')</a>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 flex-grow-1">
                                                        <div class="form-group ">
                                                            <input class="form-control" name="caption[]" type="text" value="{{ @old('caption')[$key] ?? @$subtitle->caption }}" placeholder="Caption (e.g., English)" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 flex-grow-1">
                                                        <div class=" form-group">
                                                            <div class="input-group">
                                                                <input class="form-control" name="language_code[]" type="text" value="{{ @old('language_code')[$key] ?? @$subtitle->language_code }}" placeholder="Language Code (e.g., en)" required>
                                                                <button class="btn btn--danger btn-sm closeBtn input-group-text" type="button">
                                                                    <i class="las la-times"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                </div>
                            @endif

                        </div>
                    </div>


                </div>
                <div class="form-group mt-3">
                    <button class="btn btn--primary w-100 h-45" type="submit">@lang('Submit')</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-back route="{{ route('admin.videos.index') }}" />
@endpush


@push('style')
    <style>
        .plyr {
            max-height: 600px !important;
            height: 100%;
        }

        .plyr__control--overlaid {
            background: #4634ff !important;
        }

        .plyr--video .plyr__control:focus-visible,
        .plyr--video .plyr__control:hover,
        .plyr--video .plyr__control[aria-expanded=true] {
            background: #4634ff !important;

        }

        .plyr--full-ui input[type=range] {
            color: #4634ff !important;

        }

        .plyr__menu__container .plyr__control[role=menuitemradio][aria-checked=true]:before {
            background: #4634ff !important;

            background: #4634ff !important;

        }
    </style>
@endpush


@push('style-lib')
    <link href="{{ asset('assets/global/css/plyr.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';
            $(document).ready(function() {
                playersInitiate()
            });


            const controls = [
                'play-large',
                'play-large',
                'play',
                'progress',
                'mute',
                'volume',
                'settings',

            ];

            function playersInitiate() {
                const player = new Plyr('.video-player', {
                    controls,
                    ratio: '16:9',
                    muted: true,
                });

            }

            $(document).ready(function() {
                const shortPlayer = new Plyr('.shorts-video-player', {
                    controls: [
                        'play-large',
                        'play',
                        'progress',
                        'mute',
                        'volume',
                    ],
                    ratio: '9:16',
                    muted: true,
                });


            });



        })(jQuery);
    </script>
@endpush


@if (!$video->is_shorts_video)
    @push('script')
        <script>
            (function($) {
                "use strict";

                let count = "{{ $video->subtitles->count() }}";

                $('.addSubtitleBtn').on('click', function() {


                    if (count >= 5) {
                        notify('error', 'You are already added maximum subtitle');
                        return;
                    }
                    count++;

                    $('.subtitle-wrapper').append(` <div class="row appendSubtitle">
                <div class="form-group col-md-4">
                    <input type="file" class="form-control" name="subtitle_file[]"  accept=".vtt"  required>
                </div>
                 <div class="form-group col-md-4">
                    <input type="text" placeholder="Capntion (e.x: English)" class="form-control" name="caption[]" required >
                </div>
                  <div class=" form-group col-md-4">
                            <div class="input-group">
                                <input type="text" placeholder="Language Code (e.g., en)" class="form-control"
                                    value=""
                                    name="language_code[]" required>
                                    <button type="button" class="btn btn--danger btn-sm closeBtn input-group-text">
                                        <i class="las la-times"></i>
                                    </button>
                            </div>
                        </div>
             
            </div>`)

                });


                $(document).on('click', '.closeBtn', function() {

                    count--;

                    $(this).closest('.appendSubtitle').remove();

                })



            })(jQuery)
        </script>
    @endpush
@endif


@push('script')
    <script>
        (function($) {
            'use strict';

            $('.buildSlug').on('click', function() {

                let closestForm = $(this).closest('form');
                let title = closestForm.find(`[name="title"]`).val();
                $('[name=slug]').val(title);
                $('[name=slug]').trigger('input');
            });



            $('[name=slug]').on('input', function() {
                let closestForm = $(this).closest('form');

                let slug = $(this).val();
                slug = slug.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');


                $(this).val(slug);
                if (slug) {
                    $('.slug-verification').removeClass('d-none');
                    $('.slug-verification').html(`
                            <small class="text--info"><i class="las la-spinner la-spin"></i> @lang('Verifying')</small>
                        `);
                    $.get("{{ route('admin.videos.check.slug') }}", {
                        slug: slug
                    }, function(response) {
                        if (!response.exists) {
                            $('.slug-verification').html(`
                                    <small class="text--success"><i class="las la-check"></i> @lang('Verified')</small>
                                `);
                        }
                        if (response.exists) {
                            $('.slug-verification').html(`
                                    <small class="text--danger"><i class="las la-times"></i> @lang('Slug already exists')</small>
                                `);
                        }
                    });
                } else {
                    $('.slug-verification').addClass('d-none');
                }
            });
        })(jQuery)
    </script>
@endpush
