@extends('admin.layouts.app')
@section('panel')
    <div class="row gy-3">
        <div class="col-xl-8 col-lg-6 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>@lang('Report for Earnings')</h5>
                </div>
                <div class="card-body">
                    <div id="earningChart"></div>

                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>@lang('Report for Reactions, Views, and Comments')</h5>
                </div>
                <div class="card-body d-flex justify-content-center">
                    <div id="reactionChart"></div>
                </div>
            </div>
        </div>
        <div class=" col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>@lang('Report for Impressions & click')</h5>
                </div>
                <div class="card-body">
                    <div id="adsChart"></div>

                </div>
            </div>
        </div>




    </div>
@endsection

@push('breadcrumb-plugins')
    <div class="border p-1 cursor-pointer rounded chart-title-text" id="dataPicker">
        <i class="la la-calendar"></i>&nbsp;
        <span></span> <i class="la la-caret-down"></i>
    </div>

    <x-back route="{{ route('admin.videos.index') }}" />
@endpush

@push('style')
    <style>
        .card-body {
            position: unset !important;

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
            document.querySelector("#adsChart"),
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

        let earningChart = lineChart(
            document.querySelector("#earningChart"),
            [{
                    name: "Ads",
                    data: []
                },
                {
                    name: "Sales",
                    data: []
                },
                {
                    name: "Total",
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

            const url = @json(route('admin.videos.filter.data', $video->id));


            $.get(url, data,
                function(data, status) {

                    if (status == 'success') {


                        adsChart.updateSeries([{
                                name: data[0].data[0].name,
                                data: data[0].data[0].data
                            },
                            {
                                name: data[0].data[1].name,
                                data: data[0].data[1].data
                            }

                        ]);

                        earningChart.updateSeries([{
                                name: data[0].data[2].name,
                                data: data[0].data[2].data
                            },
                            {
                                name: data[0].data[3].name,
                                data: data[0].data[3].data
                            },
                            {
                                name: data[0].data[4].name,
                                data: data[0].data[4].data
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
                                categories: data[0].created_on,
                            }
                        });

                        earningChart.updateOptions({
                            colors: ['#008FFB', '#f93b5b', '#FEB019'],

                            yaxis: {
                                labels: {
                                    formatter: function(value) {
                                        return Math.round(value);
                                    }
                                }
                            },
                            xaxis: {
                                categories: data[0].created_on,
                            }
                        });

                        pieChart(data[1])


                    }
                }
            );
        }

        let chart;

        function pieChart(data = null) {
            var options = {
                series: data,
                chart: {
                    width: 510,
                    type: 'pie',
                },
                labels: ['Like', 'Dislike', 'Comment', 'Views'],
                colors: ['#C4E1F6', '#FEEE91', '#FFBD73', '#FEEE91', '#FF9D3D'],
                legend: {
                    position: 'bottom'
                },
                responsive: [{
                        breakpoint: 1449,
                        options: {
                            chart: {
                                width: 480
                            }

                        }
                    },
                    {
                        breakpoint: 1024,
                        options: {
                            chart: {
                                width: 400
                            }

                        }
                    },
                    {
                        breakpoint: 768,
                        options: {
                            chart: {
                                width: 400
                            }

                        }
                    },
                    {
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 300
                            }

                        }
                    }
                ]
            };

            if (chart) {
                chart.destroy();
            }

            chart = new ApexCharts(document.querySelector("#reactionChart"), options);
            chart.render();
        }





        $('#dataPicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#dataPicker span', start,
            end));


        changeDatePickerText('#dataPicker span', start, end);

        videoChart(start, end);


        $('#dataPicker').on('apply.daterangepicker', (event, picker) => videoChart(picker.startDate, picker.endDate));
    </script>
@endpush
