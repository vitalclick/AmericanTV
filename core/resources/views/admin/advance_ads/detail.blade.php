@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">@lang('Basic Information')</h5>
                </div>

                <div class="card-body ">
                    <div class="form-group">
                        <label for="title">@lang('Campaign Title')</label>
                        <input type="text" class="form-control" id="title" name="campaign_title"
                            value="{{ old('campaign_title', $advertisement->campaign?->title) }}" readonly>
                    </div>
                    <div class="form-group">
                        <label for="title">@lang('Advertisement Title')</label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ old('title', $advertisement->title) }}" readonly>
                    </div>
                    <div class="form-group">
                        <label>@lang('Daily Budget')</label>
                        <div class="input-group">
                            <input class="form-control" name="daily_costs"
                                value="{{ old('daily_costs', getAmount($advertisement->daily_costs)) }}" readonly>
                            <span class="input-group-text">{{ gs('cur_text') }}</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>@lang('Daily Ad Reached')</label>
                                <div class="input-group">
                                    <input class="form-control" name="ad_reached"
                                        value="{{ old('ad_reached', formatNumber($advertisement->ad_reached)) }}" readonly>
                                </div>
                            </div>

                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>@lang('Daily Ad Engagement')</label>
                                <div class="input-group">
                                    <input class="form-control" name="ad_engagement"
                                        value="{{ old('ad_engagement', formatNumber($advertisement->ad_engagement)) }}"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <label>@lang('Ads Type')</label>
                        <div class="col-md-6">
                            <div class="form-check form--radio">
                                <input class="form-check-input" id="flexRadioDefault1" name="ad_type" value="1"
                                    @if (request()->ad_type == Status::ALL_VIEWS || @$advertisement->ad_type == Status::ALL_VIEWS) checked @endif type="radio">
                                <label class="form-check-label" for="flexRadioDefault1">
                                    <span class="label-title"> @lang('All Views') </span>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form--radio">
                                <input class="form-check-input" id="flexRadioDefault2" name="ad_type" value="2"
                                    @if (request()->ad_type == Status::SKIPPABLE || @$advertisement->ad_type == Status::SKIPPABLE) checked @endif type="radio">
                                <label class="form-check-label" for="flexRadioDefault2">
                                    <span class="label-title"> @lang('Skippable in-stream ads'). </span>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form--radio">
                                <input class="form-check-input" id="flexRadioDefault3" name="ad_type" value="3"
                                    @if (request()->ad_type == Status::NON_SKIPPABLE || @$advertisement->ad_type == Status::NON_SKIPPABLE) checked @endif type="radio">
                                <label class="form-check-label" for="flexRadioDefault3">
                                    <span class="label-title"> @lang('Non-skippable in-stream ads'). </span>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form--radio">
                                <input class="form-check-input" id="flexRadioDefault4" name="ad_type" value="4"
                                    @if (request()->ad_type == Status::IN_FEED || @$advertisement->ad_type == Status::IN_FEED) checked @endif type="radio">
                                <label class="form-check-label" for="flexRadioDefault4">
                                    <span class="label-title"> @lang('In-feed video ads'). </span>
                                </label>
                            </div>
                        </div>
                        @php

                            $locations = $advertisement?->countries->pluck('country')->toArray() ?? [];
                        @endphp

                        <div class="col-md-12">
                            <div class="col-sm-12 select2-parent">
                                <label class="form--label"> @lang('Location') </label>
                                <select class="form-select form--control select2" name="countries[]" multiple
                                    aria-label="Default select example" readonly>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->country }}"
                                            @if (in_array($country->country, $locations)) selected @endif>{{ $country->country }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>






                        @php
                            $startDate = @$advertisement->start_date
                                ? showDateTime(@$advertisement->start_date, 'Y-m-d')
                                : null;
                            $endDate = @$advertisement->end_date
                                ? showDateTime(@$advertisement->end_date, 'Y-m-d')
                                : null;

                        @endphp
                        <div class="col-md-6 form-group">

                            <label>@lang('Start Date')</label>
                            <input type="text" class="form-control datepicker" name="start_date"
                                value="{{ @$startDate }}" readonly>

                        </div>
                        <div class="col-md-6 form-group">
                            <label>@lang('End Date')</label>
                            <input type="text" class="form-control datepicker" name="end_date"
                                value="{{ @$endDate }}" readonly>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>@lang('Ad Schedule Type')</label>
                            <select class="form-control" name="schedule_type" required>
                                <option value="1" @if ($advertisement->schedule_type == Status::ALL_DAYS) selected @endif>
                                    @lang('All Days')</option>
                                <option value="2" @if ($advertisement->schedule_type == Status::CUSTOM_DAYS) selected @endif>
                                    @lang('Custom Days')</option>
                            </select>
                        </div>
                    </div>

                    @if ($advertisement->schedule_type == Status::CUSTOM_DAYS)
                        <div class="row">
                            <label>@lang('Custom Schedule')</label>

                            @foreach ($advertisement->schedules as $schedule)
                                <div class="col-md-6 form-group">
                                    <label>@lang('Start On')</label>
                                    <input type="text" class="form-control datepicker" name="start_date"
                                        value="{{ @$schedule->custom_start_date }}" readonly>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>@lang('End On')</label>
                                    <input type="text" class="form-control datepicker" name="end_date"
                                        value="{{ @$schedule->custom_end_date }}" readonly>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

            @if($advertisement->status == Status::ADVERTISEMENT_REJECTED)
               <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">@lang('Rejected Reason')</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                     
                        <textarea class="form-control" rows="5" readonly>{{ $advertisement->reject_reason }}</textarea>
                    </div>

                </div>
            </div>
            @endif


         



        </div>


        @php

            $video = $advertisement->video;
            if ($video) {
                $videoFile = $video->videoFiles()->first();
            }

        @endphp
        <div class="col-lg-6">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">@lang('Ad Video')</h5>
                        </div>
                        <div class="card-body">
                            <video class="video-player" controls>
                                @if ($video)
                                    <source src="{{ getVideo($videoFile->file_name, $video) }}" type="video/mp4" />
                                @else
                                    <source src="{{ getAd($advertisement->ad_file, $advertisement) }}"
                                        type="video/mp4" />
                                @endif
                            </video>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">@lang('Ad Details')</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>@lang('Logo')</label>
                                <x-image-uploader name="logo" :imagePath="getImage(getFilePath('adLogo') . '/' . $advertisement->logo)" :size="getFileSize('adLogo')" :required="false" />
                            </div>
                            <div class="form-group">
                                <label>@lang('Ad Button Label')</label>
                                <input type="text" class="form-control" id="description" name="button_label"
                                    value="{{ old('button_label', $advertisement->button_label) }}" readonly>
                            </div>
                            <div class="form-group">
                                <label>@lang('Redirect Url')</label>
                                <input type="text" class="form-control" id="description" name="redirect_url"
                                    value="{{ old('redirect_url', $advertisement->url) }}" readonly>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>






    <div id="rejectModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Reject Ads Confirmation')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.advance.ads.reject', $advertisement->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p>@lang('Are you sure to') <span class="fw-bold">@lang('reject')</span> @lang('this ads?')</p>

                        <div class="form-group">
                            <label class="mt-2">@lang('Reason for Rejection')</label>
                            <textarea name="message" max-length="255" class="form-control" rows="5" required>{{ old('message') }}</textarea>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />

@endsection

@push('breadcrumb-plugins')
    @if ($advertisement->status == Status::ADVERTISEMENT_PENDING)
        <button type="button" class="btn btn-sm btn-outline--danger" data-bs-toggle="modal"
            data-bs-target="#rejectModal"><i class="las la-times"></i>@lang('Reject')</button>
        <button type="button" data-action="{{ route('admin.advance.ads.approved', $advertisement->id) }}"
            data-question="@lang('Are you sure want to approve this advertisement ?')" class="btn btn-sm btn-outline--success confirmationBtn"><i
                class="las la-check"></i>@lang('Approved')</button>
    @endif
@endpush


@push('style')
    <style>
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
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <link type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/global/css/plyr.css') }}" rel="stylesheet">
@endpush


@push('script-lib')
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
@endpush


@push('script')
    <script>
        $(document).ready(function() {

            initDatepickers();
        });

        function initDatepickers() {

            $(".datepicker").daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD HH:mm:ss',
                },
                singleDatePicker: true,
                timePicker: true,
                timePicker24Hour: true,
                showDropdowns: true,
                minDate: moment(),
                autoclose: true,
                todayHighlight: true
            });

        }

        controls = [

            'rewind',
            'play-large',
            'play',

            'fast-forward',
            'progress',
            'current-time',
            'duration',
            'mute',
            'settings',
            'fullscreen',
        ];

        $(document).ready(function() {
            const singleplayer = new Plyr('.video-player', {
                controls,
                autoplay: true,
                ratio: '16:9',
            });

        });
    </script>
@endpush
