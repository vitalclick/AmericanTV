@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">


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

    </div>
@endsection

@push('style')
    <style>
        .action-btn:disabled {
            background-color: hsl(0deg 0% 35.43%)
        }
    </style>
@endpush
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
                    },
                    {
                        name: "Total Reached Users",
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

                const url = @json(route('user.advertiser.ad.analytics.chart', $advertisement->id));


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
                                },
                                {
                                    name: data.data[2].name,
                                    data: data.data[2].data
                                }

                            ]);


                            adsChart.updateOptions({
                                colors: ['#1E88E5', '#ff0000', '#FFC107'],
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