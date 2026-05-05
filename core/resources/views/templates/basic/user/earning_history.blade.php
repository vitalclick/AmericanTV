@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="dashboard-card-wrapper">
            <div class="dashboard-card">
                <h5 class="dashboard-card__title">@lang('Total Earnings')</h5>
                <h3 class="dashboard-card__number">{{ gs('cur_sym') }}{{ showAmount($totalEarnings, currencyFormat: false) }}
                </h3>
            </div>
            <div class="dashboard-card info">
                <h5 class="dashboard-card__title">@lang('Revenue from Ads')</h5>
                <h3 class="dashboard-card__number">{{ gs('cur_sym') }}{{ showAmount($adsEarnings, currencyFormat: false) }}
                </h3>
            </div>
            <div class="dashboard-card caribbean-green">
                <h5 class="dashboard-card__title">@lang('Stock Video sales')</h5>
                <h3 class="dashboard-card__number">
                    {{ gs('cur_sym') }}{{ showAmount($stockVideoEarnings, currencyFormat: false) }}</h3>
            </div>
        </div>
        <div class="dashboard-card-wrapper">
            <div class="dashboard-card purple">
                <h5 class="dashboard-card__title">@lang('Earnings from Playlists')</h5>
                <h3 class="dashboard-card__number">{{ gs('cur_sym') }}{{ showAmount($playlistEarnings, currencyFormat: false) }}
                </h3>
            </div>
            <div class="dashboard-card bg--success">
                <h5 class="dashboard-card__title">@lang('Earnings from Monthly Plan')</h5>
                <h3 class="dashboard-card__number">{{ gs('cur_sym') }}{{ showAmount($planEarnings, currencyFormat: false) }}
                </h3>
            </div>
            <div class="dashboard-card brown">
                <h5 class="dashboard-card__title">@lang('Commission Paid to Admin')</h5>
                <h3 class="dashboard-card__number">
                    {{ gs('cur_sym') }}{{ showAmount($adminCommission, currencyFormat: false) }}</h3>
            </div>
        </div>

        <div class="chart-box two" id="totalRevenue">
            <div class="d-flex flex-wrap justify-content-between">
                <h5 class="card-title">@lang('Earnings Report')</h5>

                <div class="border p-1 cursor-pointer rounded chart-title-text" id="trxDatePicker">
                    <i class="la la-calendar"></i>&nbsp;
                    <span></span> <i class="la la-caret-down"></i>
                </div>
            </div>
            <div id="revenueArea"></div>
        </div>


        <div class="advertising-table">
            <div class="d-flex justify-content-between align-items-center table--header">
                <h4 class="withdraw__title mb-0">@lang('Recenet Earnings')</h4>
                <a href="{{ route('user.transactions') }}" class="btn--link">@lang('See All')
                    <span class="icon"><i class="la la-arrow-right"></i></span></a>
            </div>
            <table class="table table--responsive--lg">
                <thead>
                    <tr>
                        <th>@lang('Trx')</th>
                        <th>@lang('Transacted')</th>
                        <th>@lang('Amount')</th>
                        <th>@lang('Post Balance')</th>
                        <th>@lang('Detail')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($earnings as $trx)
                        <tr>
                            <td>
                                <strong>{{ $trx->trx }}</strong>
                            </td>

                            <td>
                                {{ showDateTime($trx->created_at) }}<br>{{ diffForHumans($trx->created_at) }}
                            </td>

                            <td>
                                <span
                                    class="fw-bold @if ($trx->trx_type == '+') text--success @else text--danger @endif">
                                    {{ $trx->trx_type }} {{ showAmount($trx->amount) }}
                                </span>
                            </td>

                            <td>
                                {{ showAmount($trx->post_balance) }}
                            </td>


                            <td>{{ __($trx->details) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-muted text-center empty-msg" colspan="100%">
                                <div class="empty-container empty-card-two">
                                    @include("Template::partials.empty")
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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


        let trxChart = lineChart(
            document.querySelector("#revenueArea"),
            [{
                    name: "Total Earnings",
                    data: [],
                },
                {
                    name: "Stock Video Earnings",
                    data: []
                },
                {
                    name: "Ads Earnings",
                    data: []
                },
                {
                    name: "Playlist Earnings",
                    data: []
                },
                {
                    name: "Plan Earnings",
                    data: []
                }
            ],
            []
        );


        const transactionChart = (startDate, endDate) => {

            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }

            const url = @json(route('user.chart.earnings'));


            $.get(url, data,
                function(data, status) {
                    if (status == 'success') {
                        trxChart.updateSeries(data.data);
                        trxChart.updateOptions({
                            colors: ['#00C7A2', '#33FF57', '#3357FF'],
                            xaxis: {
                                categories: data.created_on,
                            }
                        });
                    }
                }
            );
        }


        $('#trxDatePicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#trxDatePicker span',
            start, end));


        changeDatePickerText('#trxDatePicker span', start, end);

        transactionChart(start, end);


        $('#trxDatePicker').on('apply.daterangepicker', (event, picker) => transactionChart(picker.startDate, picker
            .endDate));
    </script>
@endpush
