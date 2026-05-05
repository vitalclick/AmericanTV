@extends($activeTemplate . 'layouts.master')
@section('content')
    @php

        $user = auth()->user();
    @endphp

    <div class="dashboard-content">
        <div class="advertising-heading">
            <h3 class="advertising-heading__title">
                {{ __($pageTitle) }}</h3>
        </div>

        <div class="border p-1 cursor-pointer rounded chart-title-text d-inline-block mb-3" id="videoDataPicker">
            <i class="la la-calendar"></i>&nbsp;
            <span></span> <i class="la la-caret-down"></i>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <div class="chart-box pb-0 mb-0">
                    <label class="form--label">@lang('Report for show & cLick ads')</label>
                    <div class="adsChart"></div>
                </div>
            </div>


            <div class="col-md-6 form-group">
                <div class="chart-box pb-0 mb-0">
                    <label class="form--label">@lang('Report for Earnings')</label>
                    <div class="adsRevenueChart"></div>
                </div>
            </div>


            <div class="col-md-12 form-group">
                <div class="chart-box pb-0 mb-0">
                    <label class="form--label">@lang('Report for Likes & Dislike')</label>
                    <div class="likeChart"></div>
                </div>

            </div>

            <div class="col-md-6 ">
                <div class="chart-box pb-0 mb-0">
                    <label class="form--label">@lang('Report for Comment')</label>

                    <div class="commentChart">

                    </div>
                </div>
            </div>

            <div class="col-md-6 ">
                <div class="chart-box pb-0 mb-0">
                    <label class="form--label">@lang('Report for Views')</label>

                    <div class="viewChart">

                    </div>
                </div>
            </div>


        </div>

    </div>
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



        let likeChart = lineChart(
            document.querySelector(".likeChart"),
            [{
                    name: "Like",
                    data: []
                },
                {
                    name: "Dislike",
                    data: []
                }
            ],
            []
        );





        let adsChart = lineChart(
            document.querySelector(".adsChart"),
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


        let adsRevenueChart = lineChart(
            document.querySelector(".adsRevenueChart"),
            [{
                    name: "Ads Revenue",
                    data: []
                }

            ],
            []
        );






        let commentChart = lineChart(
            document.querySelector(".commentChart"),
            [{
                name: "Comment",
                data: []
            }],
            []
        );




        let viewChart = lineChart(
            document.querySelector(".viewChart"),
            [{
                name: "View",
                data: []
            }],
            []
        );




        const videoChart = (startDate, endDate) => {

            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }

            const url = @json(route('user.video.chart', $video->slug));


            $.get(url, data,
                function(data, status) {

                    if (status == 'success') {
                        likeChart.updateSeries([

                            {
                                name: data.data[1].name,
                                data: data.data[1].data
                            },
                            {
                                name: data.data[2].name,
                                data: data.data[2].data
                            }

                        ]);


                        adsChart.updateSeries([{
                                name: data.data[4].name,
                                data: data.data[4].data
                            },
                            {
                                name: data.data[5].name,
                                data: data.data[5].data
                            }

                        ]);

                        adsRevenueChart.updateSeries([{
                                name: data.data[6].name,
                                data: data.data[6].data
                            },
                            {
                                name: data.data[7].name,
                                data: data.data[7].data
                            },
                            {
                                name: data.data[8].name,
                                data: data.data[8].data
                            }
                        ]);




                        commentChart.updateSeries([{
                            name: data.data[3].name,
                            data: data.data[3].data
                        }]);

                        viewChart.updateSeries([{
                            name: data.data[0].name,
                            data: data.data[0].data
                        }]);

                        likeChart.updateOptions({
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

                        adsRevenueChart.updateOptions({
                            colors: ['#2eff00', '#0008f9', '#F7D752'],
                            yaxis: {
                                labels: {
                                    formatter: function(value) {
                                        return "{{ gs('cur_sym') }}" + Math.round(value).toFixed(2);
                                    }
                                }
                            },
                            xaxis: {
                                categories: data.created_on,
                            }
                        });





                        commentChart.updateOptions({
                            colors: ['#00f6ff'],
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


                        viewChart.updateOptions({
                            colors: ['#10ff00'],
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


        $('#videoDataPicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText(
            '#videoDataPicker span', start, end));


        changeDatePickerText('#videoDataPicker span', start, end);

        videoChart(start, end);


        $('#videoDataPicker').on('apply.daterangepicker', (event, picker) => videoChart(picker.startDate, picker.endDate));
    </script>
@endpush
