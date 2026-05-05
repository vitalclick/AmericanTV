@extends('admin.layouts.app')
@section('panel')
    @php
        $purchased = $user
            ->deposits()
            ->where('is_monetization', Status::YES)
            ->exists();

    @endphp
    <div class="row gy-3 ">
        <div class="col-lg-12">
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex flex-wrap justify-content-between">
                    <h5 class="card-title ">@lang('Subscriber & Views')</h5>

                    <h5>@lang('Monetiztion Type'): @if ($purchased)
                            <span class="badge badge--success">@lang('Paid') </span>
                        @else
                            <span class="badge badge--warning"> @lang('Free') </span>
                        @endif
                    </h5>

                    <h5>@lang('Monetization Status'): @php
                        echo $user->monetizationStep;
                    @endphp </h5>
                </div>
                <div class="card-body text-center">
                    <div class="row gy-3">

                        <div class="col-xxl-6 col-sm-6">
                            <x-widget type="2" value="{{ gs('minimum_subscribe') }}" title="Need Subscribers"
                                style="7" link="javascript:void(0)" icon="las la-user-friends" bg="indigo" />
                        </div>


                        <div class="col-xxl-6 col-sm-6">
                            <x-widget type="2" value="{{ gs('minimum_views') }}" title="Need Views" style="7"
                                link="javascript:void(0)" icon="las la-eye" bg="8" />
                        </div>


                        <div class="col-xxl-6 col-sm-6">
                            <x-widget type="2" value="{{ $totalSubscriber }}" title="Get Subscribers" style="7"
                                link="javascript:void(0)" icon="las la-users" bg="17" />
                        </div>
                        <div class="col-xxl-6 col-sm-6">
                            <x-widget type="2" value="{{ $totalViews }}" title="Get Views" style="7"
                                link="javascript:void(0)" icon="las la-chart-line" bg="6" />
                        </div>

                    </div>

                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header  d-flex flex-wrap justify-content-between">
                    <h5 class="card-title">@lang('Subscriber & Views Progress')</h5>
                    <div class="border p-1 cursor-pointer rounded chart-title-text" id="dataPicker">
                        <i class="la la-calendar"></i>&nbsp;
                        <span></span> <i class="la la-caret-down"></i>
                    </div>
                </div>
                <div class="card-body">
                    <div class="overViewChart"></div>
                </div>
            </div>
        </div>
    </div>
    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')

    @if ($user->monetization_status == Status::MONETIZATION_APPLYING)
        <button class="btn btn-sm btn-outline--success confirmationBtn" @if ($user->monetization_status == Status::MONETIZATION_APPROVED) disabled @endif
            data-action="{{ route('admin.users.monetization.approve', $user->id) }}" data-question="@lang('Are you sure you want to approve this monetization request')?"><i
                class="las la-check-double"></i>@lang('Approved')</button>

        <button class="btn btn-sm btn-outline--danger  confirmationBtn" @if ($user->monetization_status == Status::MONETIZATION_CANCEL) disabled @endif
            data-action="{{ route('admin.users.monetization.reject', $user->id) }}" data-question="@lang('Are you sure you want to reject this monetization request')?"><i
                class="las la-times"></i>@lang('Reject')</button>
    @endif
    <x-back route="{{ route('admin.users.all') }}" />
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/vendor/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
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

        const overViewChart = (startDate, endDate) => {

            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }

            const url = @json(route('admin.users.monetization.chart', $user->id));


            $.get(url, data, function(data, status) {

                if (status == 'success') {

                    renderProgressChart(data)

                }
            });
        }

        let chart;

        function renderProgressChart(data) {
            var options = {
                chart: {
                    type: 'bar',
                    height: 350
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        dataLabels: {
                            position: 'top'
                        }
                    }
                },
                dataLabels: {

                    enabled: true,
                    style: {
                        colors: ['#000']
                    },
                    formatter: function(val, opts) {
                        const seriesIndex = opts.seriesIndex;
                        return (seriesIndex === 0 ? val + " subscribers" : val + " views");
                    }
                },
                series: [{
                    name: 'Subscribers',
                    data: [data.actualSubscribers, data.targetSubscribers]
                }, {
                    name: 'Views',
                    data: [data.actualViews, data.targetViews]
                }],
                xaxis: {
                    categories: ['Actual', 'Target'],
                    max: Math.max(data.targetSubscribers, data.actualSubscribers, data.targetViews, data.actualViews) *
                        1.2
                },
                colors: ['#00E396', '#008FFB'],
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    y: {
                        formatter: function(val, {
                            seriesIndex
                        }) {
                            return seriesIndex === 0 ? `${val} subscribers` : `${val} views`;
                        }
                    }
                }
            };

            if (chart) {
                chart.destroy();
            }

            chart = new ApexCharts(document.querySelector(".overViewChart"), options);
            chart.render();
        }

        $('#dataPicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#dataPicker span', start,
            end));

        changeDatePickerText('#dataPicker span', start, end);
        overViewChart(start, end);

        $('#dataPicker').on('apply.daterangepicker', (event, picker) => overViewChart(picker.startDate, picker.endDate));
    </script>
@endpush
