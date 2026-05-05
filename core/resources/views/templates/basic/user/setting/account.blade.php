@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="setting-content">
        <form method="post" enctype="multipart/form-data">
            <h3 class="setting-content__title">@lang('Account Information')</h3>
            <div class="edit-profile">
                <div class="edit-profile__photo">
                    <div class="cover-photo upload-image">
                        <div class="cover-preview h-100 w-100">
                            <div class="coverPhotoPreview upload-image__thumb h-100 w-100">
                                <img class="fit-image" src="{{ getImage(getFilePath('cover') . '/' . $user->cover_image, getFileSize('cover')) }}"
                                     alt="@lang('cover_image')">
                            </div>
                        </div>
                        <div class="cover-edit">
                            <label for="coverImage">
                                <span class="icon text--base"><i class="vti-add-photo"></i> </span>
                                <span class="text--base fs-14">@lang('Add/change cover image')</span>
                                <input type="file" hidden name="cover_image" class="coverPhotoUpload upload-image__btn"
                                       id="coverImage" accept=".png, .jpg, .jpeg">
                            </label>
                        </div>
                    </div>
                    <div class="profile-picture upload-image">
                        <div class="profile-picture__inner bg-img upload-image__thumb">
                            <img class="fit-image" src="{{ getImage(getFilePath('userProfile') . '/' . $user->image, getFileSize('userProfile')) }}"
                                 alt="image">
                            <div class="profile-picture__edit">
                                <label for="image">
                                    <input type="file" class="upload-image__btn" hidden name="image"
                                           accept=".png, .jpg, .jpeg" id="image">
                                    <span class="icon"><i class="fas fa-camera"></i> </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                @csrf

                <div class="edit-profile__form row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form--label">@lang('Channel Name')</label>
                            <input type="text" class="form--control" name="channel_name"
                                   value="{{ __(old('channel_name', $user->channel_name)) }}" placeholder="User Channel">
                        </div>
                    </div>


                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form--label">@lang('Channel Description')</label>
                            <textarea class="form--control nicEdit" name="channel_description" cols="20" rows="10"> {{ $user->channel_description }}</textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group text-end mb-0">
                            <button type="submit" class="btn btn--base">@lang('Save')</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection


@push('style')
    <style>
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

        .edit-profile__photo .cover-photo .cover-edit label {
            border: 1px dashed hsl(var(--static-black));

        }
    </style>
@endpush


@push('script-lib')
    <script src="{{ asset($activeTemplateTrue . 'js/nicEdit.js') }}"></script>
@endpush
@push('script')
    <script>
        (function($) {
            'use strict';

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

        })(jQuery)
    </script>
@endpush
