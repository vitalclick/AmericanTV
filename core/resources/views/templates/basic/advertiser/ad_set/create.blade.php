@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="content-top">
            <h4 class="title">{{ __($pageTitle) }}</h4>
            <a href="{{ route('user.advertiser.campaign.gateways', $campaign->id) }}"
                class="btn btn--base">@lang('Pay Now')</a>
        </div>
        <div class="advertisement-card">
            <div class="advertisement-card__top">
                <h6 class="advertisement-card__title">@lang('Campaign Info')</h6>
            </div>
            <div class="advertisement-card__content">
                <div class="row gy-4">
                    <div class="col-sm-12">
                        <label class="form--label"> @lang('Campaign Title') </label>
                        <input class="form--control" value="{{ $campaign->title }}" type="text" readonly>
                    </div>
                </div>
            </div>
        </div>
        <div class="group-info">
            <h6 class="group-info__title">
                <span class="icon">
                    <svg class="" style="enable-background:new 0 0 512 512" xmlns="http://www.w3.org/2000/svg"
                        version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" x="0" y="0" viewBox="0 0 444 444"
                        xml:space="preserve">
                        <g>
                            <path class="" data-original="#000000"
                                d="M7 0h181c3.867 0 7 3.133 7 7v181c0 3.867-3.133 7-7 7H7c-3.867 0-7-3.133-7-7V7c0-3.867 3.133-7 7-7zM256 0h181c3.867 0 7 3.133 7 7v181c0 3.867-3.133 7-7 7H256c-3.867 0-7-3.133-7-7V7c0-3.867 3.133-7 7-7zM7 249h181c3.867 0 7 3.133 7 7v181c0 3.867-3.133 7-7 7H7c-3.867 0-7-3.133-7-7V256c0-3.867 3.133-7 7-7zM256 249h181c3.867 0 7 3.133 7 7v181c0 3.867-3.133 7-7 7H256c-3.867 0-7-3.133-7-7V256c0-3.867 3.133-7 7-7zm0 0"
                                fill="currentColor" opacity="1"></path>
                        </g>
                    </svg>
                </span>
                @lang('Ad Group Info')
            </h6>
            <div class="group-button">
                @foreach ($campaign->advertisements as $adSet)
                    <a href="{{ route('user.advertiser.ad.edit', $adSet->id) }}"
                        class="btn btn--sm btn--base {{ menuActive('user.advertiser.ad.edit', null, $adSet->id) }}">{{ $adSet->title }}</a>
                @endforeach

                <a href="{{ route('user.advertiser.ad.create', $campaign->slug) }}"
                    class="btn btn--sm btn--base  addNewGroup {{menuActive('user.advertiser.ad.create')}}">@lang('Untitled')</a>

                <a href="{{ route('user.advertiser.ad.create', $campaign->slug) }}" type="button"
                    class="btn--transparent"><i class="las la-plus"></i>@lang('Add New Ads')</a>
            </div>
        </div>

        <form id="adForm" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="campaign_id" value="{{ $campaign->id }}">
            <div class="advertisement-card">
                <div class="advertisement-card__top">
                    <h6 class="advertisement-card__title"> @lang('General Info')</h6>
                </div>
                <div class="advertisement-card__content">
                    <div class="row gy-4">
                        <div class="col-sm-12">
                            <label class="form--label"> @lang('Ad Group Name') </label>
                            <input class="form--control" type="text" value="{{ old('title', @$advertisement->title) }}"
                                name="title" />
                        </div>
                        <div class="col-sm-12">
                            <label class="form--label"> @lang('Daily Budget')</label>
                            <div class="input--group budget">
                                <input class="form--control" type="number" name="amount"
                                    value="{{ old('amount', getAmount(@$advertisement->daily_costs)) }}" placeholder="1000"
                                    @if (@$advertisement) readonly @endif>
                                <span class="input-text">{{ gs('cur_text') }}</span>
                            </div>
                            <span class="text fs-14 mt-1">
                                @lang('The daily budget is set per day, while the lifetime budget applies to the entire duration of the ad.')
                            </span>
                        </div>
                        <div class="col-sm-12">
                            <div class="advertisement-result">
                                <h6 class="advertisement-result__title">
                                    <span class="icon">
                                        <svg class="" style="enable-background:new 0 0 512 512"
                                            xmlns="http://www.w3.org/2000/svg" version="1.1"
                                            xmlns:xlink="http://www.w3.org/1999/xlink" x="0" y="0" viewBox="0 0 64 64"
                                            xml:space="preserve">
                                            <g>
                                                <path data-original="#000000"
                                                    d="M59 50H7V12a2 2 0 0 0-4 0v40a2 2 0 0 0 2 2h54a2 2 0 0 0 0-4Z"
                                                    fill="currentColor" opacity="1"></path>
                                                <path data-original="#000000"
                                                    d="M12 47a2 2 0 0 0 1.41-.59L27 32.83l4.59 4.59a2 2 0 0 0 2.83 0L50 21.83v6.36a2 2 0 0 0 4 0V17a2 2 0 0 0-2-2H40.81a2 2 0 0 0 0 4h6.36L33 33.18l-4.59-4.59a2 2 0 0 0-2.83 0l-15 15A2 2 0 0 0 12 47Z"
                                                    fill="currentColor" opacity="1"></path>
                                            </g>
                                        </svg>
                                    </span>
                                    @lang('Estimated Daily Result')
                                </h6>
                                <div class="result-list">
                                    <div class="result-list__item">
                                        <input type="hidden" value="{{ @$advertisement->ad_reached }}" name="ad_reached">
                                        <span class="result-list__title"> @lang('Ad Reach') </span>
                                        <p class="result-list__number adReach">0 - 0k</p>
                                        <div class="progress">
                                            <div class="progress-bar bg--primary" role="progressbar"
                                                aria-label="Success example" aria-valuenow="25" aria-valuemin="0"
                                                aria-valuemax="100" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div class="result-list__item">
                                        <input type="hidden" value="{{ @$advertisement->ad_engagement }}"
                                            name="ad_engagement">
                                        <span class="result-list__title"> @lang('Ad Engagement')</span>
                                        <p class="result-list__number adEngagement">0 - 0k</p>
                                        <div class="progress">
                                            <div class="progress-bar bg--primary" role="progressbar"
                                                aria-label="Success example" aria-valuenow="25" aria-valuemin="0"
                                                aria-valuemax="100" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('Template::advertiser.ad_set.partials.ads_type', ['advertisement' => @$advertisement])
            @include('Template::advertiser.ad_set.partials.schedule', ['advertisement' => @$advertisement])
            @include('Template::advertiser.ad_set.partials.audience', ['advertisement' => @$advertisement])
            @include('Template::advertiser.ad_set.partials.ad_detail', [
                'advertisement' => @$advertisement,
            ])
            @include('Template::advertiser.ad_set.partials.ad_video', ['videoLists' => @$videoLists])

            <div class="btn--group flex-align gap-2">
                <button class="btn btn--base btn--sm submitBtn" type="submit">
                    @lang('Continue')
                </button>

            </div>
        </form>
    </div>
@endsection


@push('style')
    <style>
        .group-button {
            padding: 5px;
            border: 1px solid hsl(var(--white) / 0.1);
            border-radius: 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .group-button .btn {
            color: hsl(var(--white)/0.7) !important;
            background-color: hsl(var(--white) / 0.1) !important;
        }

        .group-button .btn:hover,
        .group-button .btn:focus {
            color: hsl(var(--white)) !important;
            border-color: hsl(var(--base)) !important;
            background-color: hsl(var(--base)) !important;
        }


        .group-button .btn.active {
            border-color: hsl(var(--base)) !important;
            color: hsl(var(--white)) !important;
            background-color: hsl(var(--base)) !important;
        }

        .btn--transparent {
            color: hsl(var(--body-color));
        }

        span.select2-dropdown.select2-dropdown--below {
            max-height: 400px;
            /* overflow: hidden; */
            overflow-x: auto;
        }

        .key-added {
            pointer-events: unset !important;
        }

        .video-list {
            max-height: 400px;
            /* Adjust this to the height you want */
            overflow-y: auto;
        }

        .add-video--modal .modal-header,
        .add-playlist--modal .modal-header {
            position: relative;
            padding: 12px 24px 12px;
            border-bottom: 1px solid hsl(var(--white) / 0.1);
        }

        .add-video--modal .modal-footer,
        .add-playlist--modal .modal-footer {
            position: relative;
            padding: 12px 24px 12px;
            border-top: 1px solid hsl(var(--white) / 0.1);
        }

        .add-video--modal .modal-content,
        .add-playlist--modal .modal-content {
            overflow: visible;
        }

        .add-video--modal .search-form,
        .add-playlist--modal .search-form {
            flex-grow: 1;

        }

        .add-video--modal .search-form {
            max-width: 300px;
        }

        .add-playlist--modal .search-form {
            max-width: 200px;
        }

        .add-video--modal .modal-close-btn,
        .add-playlist--modal .modal-close-btn {
            --size: 24px;
            width: var(--size);
            height: var(--size);
            border-radius: 50%;
            position: absolute;
            top: calc((var(--size) / 2) * -1);
            right: calc((var(--size) / 2) * -1);
            color: hsl(var(--black));
            font-size: calc(var(--size) / 2);
            border: 1px solid hsl(var(--black) / 0.15) !important;
            background-color: hsl(var(--black) / 0.1) !important;
            backdrop-filter: blur(5px);
            z-index: 1;
        }


        [data-theme="dark"] .add-video--modal .modal-close-btn,
        [data-theme="dark"] .add-playlist--modal .modal-close-btn {
            color: hsl(var(--white)) !important;
            border: 1px solid hsl(var(--white) / 0.15) !important;
            background-color: hsl(var(--white) / 0.1) !important;
        }

        .spinner {
            text-align: center;
            margin-top: 20px;
        }

        .spinner i {
            font-size: 45px;
            color: #ff0000;
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

        .submitBtn {
            width: 120px;
            /* Adjust width as needed */
            text-align: center;
        }

        .spinner {
            display: inline-block;
            font-size: 16px;
        }
    </style>
@endpush


@push('style-lib')
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <link type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
@endpush


@push('script-lib')
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush




@push('script')
    <script>
        (function($) {
            "use strict";


            $(document).ready(function() {

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
            });

            $(document).ready(function() {
                $(".time-info-btn").click(function() {
                    $(this).siblings(".time-info-item").fadeToggle(300);
                });
            });


            $('[name="title"]').on('input', function() {
                var title = $(this).val();
                $('.addNewGroup').text(title);

                if (title == '') {
                    $('.addNewGroup').text('@lang('Untitled')');
                }
            });

            function formatNumber(number, precision = 1) {
                if (number < 1000) {
                    return number;
                }

                const abbreviations = ['', 'K', 'M', 'B', 'T', 'QT'];
                let index = Math.floor(Math.log10(number) / 3);
                let abbreviated = number / Math.pow(1000, index);
                return `${abbreviated.toFixed(precision)} ${abbreviations[index] || `__`}`;
            }


            $(document).ready(function() {
                $('[name="amount"]').on('change', function() {
                    var dailyBudget = parseFloat($(this).val()) || 0;
                    calculateResult(dailyBudget)
                }).trigger('change');

            });


            $('[name="amount"]').on('input', function() {
                var dailyBudget = parseFloat($(this).val()) || 0;
                calculateResult(dailyBudget)
            });


            function calculateResult(dailyBudget) {
                const reached = parseFloat("{{ gs('ad_reach') }}") || 1;
                const engageAmount = parseFloat("{{ gs('ad_engagement') }}") || 1;


                var dailyResult = Math.round(dailyBudget * reached);
                var dailyEngagement = Math.round(dailyBudget * engageAmount);
                $('[name="ad_reached"]').val(dailyResult);
                $('[name="ad_engagement"]').val(dailyEngagement);
                $('.adReach').text(`1 - ${formatNumber(dailyResult)}`);
                $('.adEngagement').text(`1 - ${formatNumber(dailyEngagement)}`);

                var reachPercentage = Math.min((dailyBudget / 100) * 100, 100);
                var engagementPercentage = Math.min((dailyBudget / 100) * 100, 100);

                $('.adReach').next('.progress').find('.progress-bar').css('width',
                    `${reachPercentage}%`);
                $('.adEngagement').next('.progress').find('.progress-bar').css('width',
                    `${engagementPercentage}%`);
            }





            $(document).ready(function() {
                $('#adForm').on('submit', function(e) {
                    e.preventDefault();

                    let form = this;
                    let formData = new FormData(form);

                    // Change button to spinner
                    $('.submitBtn').html(
                        '<i class="las la-spinner "></i>').prop(
                        'disabled', true);

                    $.ajax({
                        url: "{{ route('user.advertiser.ad.store', @$advertisement->id) }}",
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.status == 'success') {
                                $.each(response.message.success, function(key, value) {
                                    notify('success', value);
                                });
                                window.location.reload();
                            } else if (response.status == 'error') {
                                $.each(response.message, function(key, value) {
                                    notify('error', value[0]);
                                });
                            }
                        },
                        complete: function() {
                            // Reset the button
                            $('.submitBtn').html('@lang('Continue')').prop('disabled',
                                false);
                        }
                    });
                });
            });


        })(jQuery);
    </script>
@endpush
