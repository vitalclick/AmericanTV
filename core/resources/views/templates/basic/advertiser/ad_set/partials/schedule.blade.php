<div class="advertisement-card">
    <div class="advertisement-card__top">
        <h6 class="advertisement-card__title"> @lang('Schedule') </h6>
    </div>
    <div class="advertisement-card__content">
        <div class="schedule-wrapper">
            <div class="row">
                <div class="col-lg-5">
                    <div class="row gy-4">
                        <div class="col-sm-6">
                            <label class="form--label"> @lang('Start Date') </label>
                            <div class="datepicker-container">
                                <span class="datepicker-icon">
                                    <i class="las la-calendar-alt"></i>
                                </span>
                                <input class="datepicker form--control"
                                    value="{{ old('start_date', @$advertisement->start_date) }}" name="start_date">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form--label"> @lang('End Date') </label>
                            <div class="datepicker-container">
                                <span class="datepicker-icon">
                                    <i class="las la-calendar-alt"></i>
                                </span>
                                <input class="datepicker form--control"
                                    value="{{ old('end_date', @$advertisement->end_date) }}" name="end_date">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Ad Scheduling --}}
            <div class="ads-info-wrapper mb-3">
                <p class="ads-info-wrapper__title"> @lang('Ad Scheduling') </p>
                <div class="ads-info">
                    <div class="form-check form--radio">
                        <input class="form-check-input" id="flexRadioDefault10" name="schedule_type" value="1"
                            type="radio" @if (request()->get('schedule_type') == Status::ALL_DAYS || @$advertisement->schedule_type == Status::ALL_DAYS) checked @endif>
                        <label class="form-check-label" for="flexRadioDefault10">
                            <span class="label-title"> @lang('All Days') </span>
                            <span class="label-text">@lang('The ads show all days in randomly').</span>
                        </label>
                    </div>
                    <div class="form-check form--radio">
                        <input class="form-check-input" id="flexRadioDefault20" name="schedule_type" value="2"
                            type="radio" @if (request()->get('schedule_type') == Status::CUSTOM_DAYS || @$advertisement->schedule_type == Status::CUSTOM_DAYS) checked @endif>
                        <label class="form-check-label" for="flexRadioDefault20">
                            <span class="label-title">@lang('Custom Schedule').</span>
                            <span class="label-text">@lang('The ads show  in specific date and time').</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Custom Schedule --}}
            <div
                class="row gy-4 customSchedule {{ request()->get('schedule_type') == Status::CUSTOM_DAYS || @$advertisement->schedule_type == Status::CUSTOM_DAYS ? '' : 'd-none' }}">
                @foreach ($advertisement->schedules ?? [] as $schedule)
                    <div class="col-lg-6 scheduleList">
                        <div class="time-info-wrapper ">
                            <p class="time-info-btn__text">@lang('Tell us your custom time information')</p>
                            <div class="row gy-3">
                                <div class="col-sm-6">
                                    <label class="form--label">@lang('Start On')</label>
                                    <div class="datepicker-container">
                                        <span class="datepicker-icon"><i class="las la-calendar-alt"></i></span>
                                        <input class="datepicker form--control"
                                            value="{{ @$schedule->custom_start_date }}" name="custom_start_date[]">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form--label">@lang('End Date')</label>
                                    <div class="datepicker-container">
                                        <span class="datepicker-icon"><i class="las la-calendar-alt"></i></span>
                                        <input class="datepicker form--control"
                                            value="{{ @$schedule->custom_end_date }}" name="custom_end_date[]">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="time-info-inner__close removeSchedule">
                                <span class="btn-icon">
                                    <i class="las la-times"></i>
                                </span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div
                class="bottom-btn customScheduleBtn mt-3 {{ request()->get('schedule_type') == 2 || @$advertisement->schedule_type == 2 ? '' : 'd-none' }}">
                <button type="button" class="btn btn--white addAnotherSchedule">
                    <span class="btn-icon"> <i class="las la-plus"></i> </span>
                    @lang('Add Schedule')
                </button>
            </div>
        </div>
    </div>
</div>

@push('style')
    <style>
        .daterangepicker {
            border: 1px solid hsl(var(--white) / 0.1);
            background-color: hsl(var(--dashboard-card)) !important;
        }

        .daterangepicker:before,
        .daterangepicker:after {
            border-bottom-color: hsl(var(--dashboard-card)) !important;
        }

        .daterangepicker .calendar-table {
            /* background-color: hsl(var(--black)); */
            background-color: hsl(var(--dashboard-card));
        }

        .daterangepicker select.monthselect,
        .daterangepicker select.yearselect {
            color: hsl(var(--white));
            border: 1px solid hsl(var(--white) / 0.1);
            background-color: hsl(var(--white) /.04) !important;
            padding: 6px;
            border-radius: 4px;
        }

        .daterangepicker select.monthselect option,
        .daterangepicker select.yearselect option {
            color: hsl(var(--white));
            background-color: hsl(var(--dashboard-card));
        }

        .daterangepicker td.off,
        .daterangepicker td.off.in-range,
        .daterangepicker td.off.start-date,
        .daterangepicker td.off.end-date {
            background-color: transparent;
        }

        .daterangepicker td.available:hover,
        .daterangepicker th.available:hover {
            color: hsl(var(--white));
            background-color: hsl(var(--white)/0.05);

        }

        .daterangepicker select.hourselect,
        .daterangepicker select.minuteselect,
        .daterangepicker select.secondselect,
        .daterangepicker select.ampmselect {
            color: hsl(var(--white));
            border: 1px solid hsl(var(--white) / 0.1);
            background-color: hsl(var(--white) /.04) !important;
            padding: 6px;
            border-radius: 4px;
        }

        .daterangepicker select.hourselect option,
        .daterangepicker select.minuteselect option,
        .daterangepicker select.secondselect option,
        .daterangepicker select.ampmselect option {
            color: hsl(var(--white));
            background-color: hsl(var(--dashboard-card));
        }

        .daterangepicker .drp-buttons {
            border-top-color: hsl(var(--white) / 0.2)
        }

        .daterangepicker .drp-buttons .btn-default:hover {
            color: hsl(var(--white)) !important;
        }

        .daterangepicker .calendar-table .next span,
        .daterangepicker .calendar-table .prev span {
            border-color: hsl(var(--white));
        }
    </style>
@endpush
@push('script')
    <script>
        (function($) {

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


            $(document).ready(function() {


                $('.addAnotherSchedule').on('click', function() {
                    $('.customSchedule').append(`
                    <div class="col-lg-6 scheduleList">
                        <div class="time-info-wrapper ">
                            <p class="time-info-btn__text">@lang('Tell us your custom time information')</p>
                            <div class="row gy-3">
                                <div class="col-sm-6">
                                    <label class="form--label">@lang('Start On')</label>
                                    <div class="datepicker-container">
                                        <span class="datepicker-icon"><i class="las la-calendar-alt"></i></span>
                                        <input class="datepicker form--control"
                                            value="{{ @$schedule->custom_start_date }}" name="custom_start_date[]">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form--label">@lang('End Date')</label>
                                    <div class="datepicker-container">
                                        <span class="datepicker-icon"><i class="las la-calendar-alt"></i></span>
                                        <input class="datepicker form--control"
                                            value="{{ @$schedule->custom_end_date }}" name="custom_end_date[]">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="time-info-inner__close removeSchedule">
                                <span class="btn-icon">
                                    <i class="las la-times"></i>
                                </span>
                            </button>
                        </div>
                    </div>
                `);

                    initDatepickers(); // Re-init for new inputs
                });

                $(document).on('click', '.removeSchedule', function() {
                    $(this).closest('.scheduleList').remove();
                });


                $(document).ready(function() {
                    $('[name="schedule_type"]').on('change', function() {
                        if ($(this).val() == 2) {
                            $('.customSchedule, .customScheduleBtn').removeClass('d-none');
                        } else {
                            $('.customSchedule, .customScheduleBtn').addClass('d-none');
                        }
                    });

                });



            });
        })(jQuery);
    </script>
@endpush
