@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">

        <div class="notice"></div>

        @php
            $kyc = getContent('kyc.content', true);
            $user = auth()->user();
        @endphp

        @if ($user->kv == Status::KYC_UNVERIFIED && $user->kyc_rejection_reason)
            <div class="alert alert--danger" role="alert">
                <div class="alert__icon"><i class="fas fa-file-signature"></i></div>
                <p class="alert__message">
                    <span class="fw-bold">@lang('KYC Documents Rejected')</span><br>
                    <small><i>{{ __(@$kyc->data_values->reject) }}
                            <a href="javascript::void(0)" class="link-color" data-bs-toggle="modal"
                                data-bs-target="#kycRejectionReason">@lang('Click here')</a> @lang('to show the reason').

                            <a href="{{ route('user.kyc.form') }}" class="link-color">@lang('Click Here')</a>
                            @lang('to Re-submit Documents'). <br>
                            <a href="{{ route('user.kyc.data') }}" class="link-color">@lang('See KYC Data')</a>
                        </i></small>
                </p>
            </div>
        @elseif ($user->kv == Status::KYC_UNVERIFIED)
            <div class="alert alert--info" role="alert">
                <div class="alert__icon"><i class="fas fa-file-signature"></i></div>
                <p class="alert__message">
                    <span class="fw-bold">@lang('KYC Verification Required')</span><br>
                    <small><i>{{ __(@$kyc->data_values->required) }}
                            <a href="{{ route('user.kyc.form') }}" class="link-color">@lang('Click here')</a>
                            @lang('to submit KYC information').</i></small>
                </p>
            </div>
        @elseif($user->kv == Status::KYC_PENDING)
            <div class="alert alert--warning" role="alert">
                <div class="alert__icon"><i class="fas fa-user-check"></i></div>
                <p class="alert__message">
                    <span class="fw-bold">@lang('KYC Verification Pending')</span><br>
                    <small><i>{{ __(@$kyc->data_values->pending) }} <a href="{{ route('user.kyc.data') }}"
                                class="link-color">@lang('Click here')</a> @lang('to see your submitted information')</i></small>
                </p>
            </div>
        @endif

        <div class="dashboard-card-wrapper">
            <div class="dashboard-card">
                <h5 class="dashboard-card__title">@lang('Total views')</h5>
                <h3 class="dashboard-card__number">{{ formatNumber($totalViews) }}</h3>
                <span class="dashboard-card__icon"><img src="{{ asset($activeTemplateTrue . 'images/icon-img/7.png') }}"
                        alt="image"></span>
            </div>
            <div class="dashboard-card info">
                <h5 class="dashboard-card__title">@lang('Subscribers')</h5>
                <h3 class="dashboard-card__number">{{ formatNumber($totalFollowers) }}</h3>
                <span class="dashboard-card__icon"><img src="{{ asset($activeTemplateTrue . 'images/icon-img/8.png') }}"
                        alt="image"></span>
            </div>
            <div class="dashboard-card purple">
                <h5 class="dashboard-card__title">@lang('Total Earning')</h5>
                <h3 class="dashboard-card__number">{{ showAmount($totalEarning) }}</h3>
                <span class="dashboard-card__icon"><img
                        src="{{ asset($activeTemplateTrue . 'images/icon-img/9.png') }}"alt="image"></span>
            </div>
        </div>
        <div class="chart-box">
            <div class="chart-box__top">
                <h5 class="chart-box__title">@lang('Video impression')</h5>

                <div class="border p-1 cursor-pointer rounded chart-title-text" id="impressionDatePicker">
                    <i class="la la-calendar"></i>&nbsp;
                    <span></span> <i class="la la-caret-down"></i>
                </div>

            </div>
            <div id="videoImpression"></div>
        </div>
        <div class="video-analytics">
            <div class="video-analytics__top">
                <h3 class="video-analytics__title">@lang('Video Analytics')</h3>

            </div>
            <div class="dashboard-card-wrapper sm">
                <div class="dashboard-card sm">
                    <h6 class="dashboard-card__title">@lang('Total views')</h6>
                    <h3 class="dashboard-card__number">{{ formatNumber($totalViews) }}</h3>
                </div>
                <div class="dashboard-card sm">
                    <h6 class="dashboard-card__title">@lang('Average views')</h6>
                    <h3 class="dashboard-card__number">{{ formatNumber(number_format($averageViews)) }}</h3>
                </div>
                <div class="dashboard-card sm">
                    <h6 class="dashboard-card__title">@lang('Total Like Video')</h6>
                    <h3 class="dashboard-card__number">{{ formatNumber($totalLike) }}</h3>
                </div>
                <div class="dashboard-card sm">
                    <div class="d-flex justify-content-between">

                        <h6 class="dashboard-card__title">@lang('New Subscribers (Last 7 Days)')</h6>
                        <h6 class="dashboard-card__title"></h6>
                    </div>
                    <h3 class="dashboard-card__number">{{ formatNumber($newFollowers) }}</h3>
                </div>
            </div>
        </div>
    </div>



    @if (auth()->user()->kv == Status::KYC_UNVERIFIED && auth()->user()->kyc_rejection_reason)
        <div class="custom--modal scale-style modal fade" id="kycRejectionReason">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('KYC Document Rejection Reason')</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>{{ auth()->user()->kyc_rejection_reason }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection



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

        let trxChart = lineChart(
            document.querySelector("#videoImpression"),
            [{
                name: "Total Impressions",
                data: []
            }],
            []
        );



        const impressionChart = (startDate, endDate) => {
            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }
            const url = @json(route('user.chart.impression'));
            $.get(url, data,
                function(data, status) {
                    if (status == 'success') {
                        trxChart.updateSeries(data.data);
                        trxChart.updateOptions({
                            xaxis: {
                                categories: data.created_on,
                            },
                            colors: ['#fa8500'],
                            fill: {
                                type: "gradient",
                                gradient: {
                                    shadeIntensity: 0,
                                    opacityFrom: 0.2,
                                    opacityTo: 0.1,
                                    stops: [0, 90, 100]
                                }
                            },
                        });
                    }
                }
            );
        }


        $('#impressionDatePicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText(
            '#impressionDatePicker span', start, end));


        changeDatePickerText('#impressionDatePicker span', start, end);

        impressionChart(start, end);


        $('#impressionDatePicker').on('apply.daterangepicker', (event, picker) => impressionChart(picker.startDate, picker
            .endDate));
    </script>
@endpush
