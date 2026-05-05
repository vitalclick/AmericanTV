@extends($activeTemplate . 'layouts.master')
@section('content')
    @php
        $content = getContent('advertiser_dashboard.content', true);

    @endphp
    <div class="dashboard-content">
        @if (!$user->advertiser_status || $user->advertiser_status == Status::ADVERTISER_REJECTED)
            <div class="chart-box">
                <div class="text-center">
                    @if (!$user->advertiser_status == Status::ADVERTISER_REJECTED)
                        <h5 class="chart-box__title mb-2">{{ __($user->fullname) }}</h5>
                        <p class="mb-2 text-white">{{ __($content->data_values->initiate_message) }}</p>
                    @else
                        <div class="alert alert--danger" role="alert">
                            <div class="alert__icon"><i class="fas fa-file-signature"></i></div>
                            <p class="alert__message"><span class="fw-bold">@lang('Your Documents Rejected')</span><br>

                                <small>
                                    @lang('Your advertiser request has been rejected.')
                                    <a href="javascript::void(0)" class="link-color" data-bs-toggle="modal"
                                        data-bs-target="#rejectionReason">@lang('Click here')</a> @lang('to show the reason').
                                </small>
                            </p>
                        </div>
                    @endif
                </div>
                <form action="{{ route('user.advertiser.data.submit') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <x-viser-form identifier="act" identifierValue="advertiser" />

                    <button class="btn btn--base w-100" type="submit">@lang('Submit')</button>

                </form>
            </div>
        @elseif($user->advertiser_status == Status::ADVERTISER_PENDING)
            <div class="chart-box">
                <div class="text-center">
                    <img class="fit-image w-25"
                        src="{{ frontendImage('advertiser_dashboard', $content->data_values->image, '285x285') }}"
                        alt="@lang('image')">
                    <h5 class="chart-box__title">{{ __($user->fullname) }}</h5>
                    <p class=" text text--white">{{ __($content->data_values->review_message) }}</p>
                </div>
            </div>
        @endif
        @if ($user->advertiser_status == Status::ADVERTISER_APPROVED)
            <div class="dashboard-card-wrapper advertisement-card-wrapper">

                @if (gs('ads_module'))
                    <div class="dashboard-card">
                        <div class="dashboard-card__shape">
                            <img src="{{ asset($activeTemplateTrue . 'images/ds-shape.png') }}" alt="">
                        </div>
                        <div class="left">
                            <h5 class="dashboard-card__title">@lang('Total Campaign') </h5>
                            <h1 class="dashboard-card__number"> {{ $totalCampaign }} </h1>
                        </div>
                        <span class="dashboard-card__icon"> <img
                                src="{{ asset($activeTemplateTrue . 'images/icon-img/dc-4.png') }}" alt=""></span>
                    </div>
                @endif


                <div class="dashboard-card">
                    <div class="dashboard-card__shape">
                        <img src="{{ asset($activeTemplateTrue . 'images/ds-shape.png') }}" alt="">
                    </div>
                    <div class="left">
                        <h5 class="dashboard-card__title">@lang('Total Ads')</h5>
                        <h1 class="dashboard-card__number"> {{ $totalAds }} </h1>
                    </div>
                    <span class="dashboard-card__icon"><img
                            src="{{ asset($activeTemplateTrue . 'images/icon-img/dc-1.png') }}" alt=""></span>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-card__shape">
                        <img src="{{ asset($activeTemplateTrue . 'images/ds-shape.png') }}" alt="">
                    </div>
                    <div class="left">
                        <h5 class="dashboard-card__title"> @lang('Total Impressions') </h5>
                        <h1 class="dashboard-card__number"> {{ formatNumber($totalImpressions) }} </h1>
                    </div>
                    <span class="dashboard-card__icon"><img
                            src="{{ asset($activeTemplateTrue . 'images/icon-img/impression.png') }}" alt=""></span>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-card__shape">
                        <img src="{{ asset($activeTemplateTrue . 'images/ds-shape.png') }}" alt="">
                    </div>
                    <div class="left">
                        <h5 class="dashboard-card__title"> @lang('Total Click') </h5>
                        <h1 class="dashboard-card__number">{{ formatNumber($totalClicks) }} </h1>
                    </div>
                    <span class="dashboard-card__icon"><img
                            src="{{ asset($activeTemplateTrue . 'images/icon-img/dc-3.png') }}" alt=""></span>
                </div>

            </div>


            <div class="chart-box  mb-0">
                <div class="chart-box__top">
                    <h5 class="chart-box__title">@lang('Ads Reports')</h5>

                    <div class="border p-1 cursor-pointer rounded chart-title-text" id="impressionDatePicker">
                        <i class="la la-calendar"></i>&nbsp;
                        <span></span> <i class="la la-caret-down"></i>
                    </div>
                </div>
                <div id="adsReportChart">

                </div>
            </div>
        @endif


        <div class="campaign-table mt-4">
            <table class="table--responsive--lg table">
                <thead>
                    <tr>

                        <th>@lang('Ad Title')</th>
                        @if (gs('ads_module'))
                            <th>@lang('Campaign Title')</th>
                            <th>@lang('Ad Reached')</th>
                            <th>@lang('Ad Engagement')</th>
                            <th>@lang('Daily Budget')</th>
                            <th>@lang('Total Costs')</th>
                        @else
                            <th>@lang('Ad Clicks')</th>
                            <th>@lang('Ad Impressions')</th>
                        @endif
                        <th>@lang('Ad Type')</th>
                        <th>@lang('Status')</th>

                    </tr>
                </thead>
                <tbody>
                    @forelse ($advertisements as $advertisement)
                        <tr>
                            <td>
                                <div class="campaign-item">
                                    <div class="campaign-item__content">
                                        <p class="campaign-item__title">{{ __($advertisement->title) }}</p>
                                        <span class="campaign-item__date">
                                            {{ showDateTime($advertisement->created_at) }}</span>
                                    </div>
                                </div>
                            </td>

                            @if (gs('ads_module'))
                                <td>
                                    {{ __($advertisement->campaign->title) }}
                                </td>

                                <td>
                                    {{ formatNumber($advertisement->ad_reached) }}
                                </td>
                                <td>
                                    {{ formatNumber($advertisement->ad_engagement) }}
                                </td>
                                <td>
                                    {{ showAmount($advertisement->daily_costs) }}
                                </td>
                                <td>
                                    {{ showAmount($advertisement->total_amount) }}
                                </td>
                            @else
                                <td>
                                    <div>
                                        <div class="result table--result text--success">
                                            <span class="icon"><i class="fas fa-chart-line"></i></span>
                                            <span class="text">{{ $advertisement->available_impression }}
                                                @lang('impression')</span>
                                        </div>
                                        <div class="result table--result text--voilet">
                                            <small class="icon"><i class="fas fa-mouse"></i></small>
                                            <small class="text">{{ $advertisement->available_click ?? 0 }}
                                                @lang('Click')</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div>
                                        <div class="result table--result text--warning">
                                            <span class="icon"><i class="fas fa-chart-line"></i></span>
                                            <span
                                                class="text">+{{ $advertisement->advertisementAnalytics()->impression()->count() }}
                                                @lang('impression')</span>
                                        </div>
                                        <div class="result table--result text--white">
                                            <small class="icon"><i class="fas fa-mouse"></i></small>
                                            <small
                                                class="text">+{{ $advertisement->advertisementAnalytics()->click()->count() }}
                                                @lang('Click')</small>
                                        </div>
                                    </div>
                                </td>
                            @endif

                            <td>
                                @php
                                    echo $advertisement->adTypeBadge;
                                @endphp

                            </td>
                            <td>
                                @php
                                    echo $advertisement->statusBadge;
                                @endphp
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-muted text-center" colspan="100%">
                                <div class="py-5">
                                    @include('Template::partials.empty')
                                </div>
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

    </div>

    @if ($user->advertiser_status == Status::ADVERTISER_REJECTED && $user->advertiser_rejection_reason)
        <div class="modal custom--modal scale-style fade" id="rejectionReason">
            <div class="modal-dialog modal-dialog-centered" role="document">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Document Rejection Reason')</h5>
                        <button type="button" class="close modal-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>{{ __($user->advertiser_rejection_reason) }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection


@push('style')
    <style>
        .chart-box {
            min-height: unset;
            margin-bottom: unset;
        }

        .chart-box img {
            max-width: 15%;
        }

        .chart-box__title {
            margin-top: 20px;
        }

        .chart-box .text {
            max-width: 600px;
            margin: 0 auto;
            margin-top: 15px;
        }

        .alert__message {
            text-align: start;

        }
    </style>
@endpush


@if ($user->advertiser_status == Status::ADVERTISER_APPROVED)
    @push('script-lib')
        <script src="{{ asset('assets/global/js/vendor/apexcharts.min.js') }}"></script>
        <script src="{{ asset('assets/global/js/vendor/chart.js.2.8.0.js') }}"></script>
        <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
        <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
        <script src="{{ asset('assets/global/js/charts.js') }}"></script>
    @endpush

    @push('style-lib')
        <link type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}" rel="stylesheet">
    @endpush



    @push('script')
        <script>
            "use strict";

            const start = moment().subtract(14, 'days');
            const end = moment();

            const dateRangeOptions = {
                startDate: start,
                endDate: end,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf(
                        'month')],
                    'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                },
                maxDate: moment()
            }

            const changeDatePickerText = (element, startDate, endDate) => {
                $(element).html(startDate.format('MMMM D, YYYY') + ' - ' + endDate.format('MMMM D, YYYY'));
            }


            let adsChart = lineChart(
                document.querySelector("#adsReportChart"),
                [{
                        name: "Clicks",
                        data: []
                    },
                    {
                        name: "Impressions",
                        data: []
                    }
                ],
                []
            );

            const videoChart = (startDate, endDate) => {

                const data = {
                    start_date: startDate.format('YYYY-MM-DD'),
                    end_date: endDate.format('YYYY-MM-DD')
                }

                const url = @json(route('user.advertiser.ad.chart'));


                $.get(url, data,
                    function(data, status) {

                        if (status == 'success') {
                            adsChart.updateSeries([{
                                    name: data.data[0].name,
                                    data: data.data[0].data
                                },
                                {
                                    name: data.data[1].name,
                                    data: data.data[1].data
                                }

                            ]);


                            adsChart.updateOptions({
                                colors: ['#1E88E5', '#ff0000'],
                                yaxis: {
                                    labels: {
                                        formatter: function(value) {
                                            return Math.round(value);
                                        }
                                    }
                                },
                                xaxis: {
                                    categories: data.created_on,
                                }
                            });

                        }
                    }
                );
            }
            $('#impressionDatePicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText(
                '#impressionDatePicker span', start, end));
            changeDatePickerText('#impressionDatePicker span', start, end);
            videoChart(start, end);
            $('#impressionDatePicker').on('apply.daterangepicker', (event, picker) => videoChart(picker.startDate, picker
                .endDate));
        </script>
    @endpush
@endif
