<div class="modal custom--modal modal-lg scale-style fade" id="playlistModal" tabindex="-1" aria-labelledby="playlistModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Add Playlist')</h5>
                <button type="button" class="close modal-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form method="post" class="playlistForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <div class="d-flex justify-content-between">
                            <label class="form--label">@lang('Title')</label> <a class="playListbuildSlug fs-14"
                                href="javescript:void(0)"><i class="las la-link"></i>@lang('Make Slug')</a>
                        </div>
                        <input class="form--control" type="text" name="title" value="{{ old('title') }}" required>
                    </div>
                    <div class="form-group">
                        <div class="d-flex justify-content-between">
                            <label class="form--label">@lang('slug')</label>
                            <div class="slug-verification d-none"></div>
                        </div>
                        <input class="form--control playListSlug" type="text" name="slug"
                            value="{{ old('slug') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="" class="form--label">@lang('Description (Optional)')</label>
                        <textarea name="description" class="form--control" cols="30" rows="10">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group select2-parent">
                        <label for="" class="form--label">@lang('Visibility')</label>
                        <div class="check-type-wrapper">
                            <label for="category01" class="check-type check-type-success">
                                <input class="check-type-input" type="radio" value="0" name="visibility"
                                    id="category01" checked="">
                                <span class="check-type-icon">
                                    <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor" stroke-linecap="round"
                                            class="check">
                                        </path>
                                    </svg>
                                </span>
                                <span class="check-type-label">@lang('Public')</span>
                            </label>

                            <label for="category02" class="check-type check-type-warning">
                                <input class="check-type-input" type="radio" value="1" name="visibility"
                                    id="category02">
                                <span class="check-type-icon">
                                    <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor" stroke-linecap="round"
                                            class="check">
                                        </path>
                                    </svg>
                                </span>
                                <span class="check-type-label">@lang('Private')</span>
                            </label>
                        </div>
                    </div>
                    @if(gs('is_playlist_sell'))
                        <div class="form-group stock-video-wrapper">

                            <label class="title-label stock-video" for="stock01">
                                <span class="check-circle-inner">
                                    <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor"
                                            stroke-linecap="round">
                                        </path>
                                    </svg>
                                </span>

                                <span class="icon">
                                    <svg class="_24ydrq0 _1286nb17o _1286nb12r6" aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="32"
                                        height="32">
                                        <path fill="currentColor"
                                            d="M486.2 50.2c-9.6-3.8-20.5-1.3-27.5 6.2l-98.2 125.5-83-161.1C273 13.2 264.9 8.5 256 8.5s-17.1 4.7-21.5 12.3l-83 161.1L53.3 56.5c-7-7.5-17.9-10-27.5-6.2C16.3 54 10 63.2 10 73.5v333c0 35.8 29.2 65 65 65h362c35.8 0 65-29.2 65-65v-333c0-10.3-6.3-19.5-15.8-23.3">
                                        </path>
                                    </svg>
                                </span>
                                <span class="text">@lang('Playlist Purchase')</span>
                                <input class="form-check-input" id="stock01" name="playlist_subscription" value="1" type="checkbox" hidden>
                            </label>
                        </div>

                        <div class="form-group stock-price">
                            <label class="form--label">@lang('Playlist Price')</label>
                            <div class="input-group">
                                <input class="form--control form-control" name="price" type="number"
                                placeholder="Price" step="any">
                                <span class="input-group-text btn--base">{{ __(gs('cur_text')) }}</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn--white submitBtn">@lang('Create')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('style')
    <style>
        .input-group-text {
            border: none !important;
        }

        .toggle.btn {
            height: 45px !important;
            border-radius: 5px;
        }

        .toggle.btn:active {
            border-color: transparent;
        }

        .toggle-on.btn,
        .toggle-off.btn {
            line-height: 32px;
        }

        .toggle-group .btn {
            border-radius: 5px;
        }

        .toggle-group .btn:active {
            color: #fff;
        }

        .toggle.btn-lg {
            height: 37px !important;
            min-height: 37px !important;
        }

        .toggle-handle {
            width: 25px !important;
            padding: 0;
        }
    </style>
@endpush


@push('script')
    <script>
        $('.playListbuildSlug').on('click', function() {
            let closestForm = $(this).closest('form');
            let title = closestForm.find(`[name="title"]`).val();
            closestForm.find('.playListSlug').val(title);
            closestForm.find('.playListSlug').trigger('input');
        });

        $('.playListSlug').on('input', function() {
            let closestForm = $(this).closest('form');
            let slug = $(this).val();
            slug = slug.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
            $(this).val(slug);
            if (slug) {
                closestForm.find('.slug-verification').removeClass('d-none');
                closestForm.find('.slug-verification').html(`
                    <small class="text--info"><i class="las la-spinner la-spin"></i> @lang('Verifying')</small>
                `);
                $.get("{{ route('user.playlist.check.slug') }}", {
                    slug: slug,

                }, function(response) {


                    if (!response.exists) {
                        closestForm.find('.slug-verification').html(`
                            <small class="text--success"><i class="las la-check"></i> @lang('Verified')</small>
                        `);
                    }

                    if (response.exists) {
                        closestForm.find('.slug-verification').html(`
                            <small class="text--danger"><i class="las la-times"></i> @lang('Slug already exists')</small>
                        `);
                    }
                });
            } else {
                closestForm.find('.slug-verification').addClass('d-none');
            }
        });
    </script>
@endpush
