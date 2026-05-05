@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        <div class="col-12 ffmpegAlert d-none"></div>

        @if (!gs('is_storage'))
            <div class="col-12 storageAlert d-none"></div>
            <div class="col-12 storage">
                <div class="custom-alert alert alert--danger" role="alert">
                    <span class="alert__icon">
                        <i class="far fa-bell"></i>
                    </span>

                    <div class="alert__content">
                        <h5 class="alert-heading">@lang('Video Upload Disabled In Storage')</h5>
                        <p>@lang('Uploading videos to storage is currently disabled. Otherwise, videos will be uploaded to your local storage.') @lang('you can') <a href="{{ route('admin.setting.system.configuration') }}">
                                @lang('Enable Storage')
                            </a> @lang('it in the settings').</p>
                    </div>
                </div>
            </div>
        @endif


        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['total_users'] }}" title="Total Users" style="6"
                link="{{ route('admin.users.all') }}" icon="las la-users" bg="primary" />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['verified_users'] }}" title="Active Users" style="6"
                link="{{ route('admin.users.active') }}" icon="las la-user-check" bg="success" />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['email_unverified_users'] }}" title="Email Unverified Users" style="6"
                link="{{ route('admin.users.email.unverified') }}" icon="lar la-envelope" bg="danger" />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['mobile_unverified_users'] }}" title="Mobile Unverified Users" style="6"
                link="{{ route('admin.users.mobile.unverified') }}" icon="las la-comment-slash" bg="warning" />
        </div><!-- dashboard-w1 end -->
    </div><!-- row end-->

    <div class="row mt-2 gy-4">
        <div class="col-xxl-6">
            <div class="card box-shadow3 h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Payments')</h5>
                    <div class="widget-card-wrapper">

                        <div class="widget-card bg--success">
                            <a class="widget-card-link" href="{{ route('admin.deposit.list') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ showAmount($deposit['total_deposit_amount']) }}</h6>
                                    <p class="widget-card-title">@lang('Total Payments')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow">
                                <i class="las la-angle-right"></i>
                            </span>
                        </div>

                        <div class="widget-card bg--warning">
                            <a class="widget-card-link" href="{{ route('admin.deposit.pending') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon">
                                    <i class="fas fa-spinner"></i>
                                </div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ $deposit['total_deposit_pending'] }}</h6>
                                    <p class="widget-card-title">@lang('Pending Payments')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow">
                                <i class="las la-angle-right"></i>
                            </span>
                        </div>

                        <div class="widget-card bg--danger">
                            <a class="widget-card-link" href="{{ route('admin.deposit.rejected') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ $deposit['total_deposit_rejected'] }}</h6>
                                    <p class="widget-card-title">@lang('Rejected Payments')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow">
                                <i class="las la-angle-right"></i>
                            </span>
                        </div>

                        <div class="widget-card bg--primary">
                            <a class="widget-card-link" href="{{ route('admin.deposit.list') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ showAmount($deposit['total_deposit_charge']) }}</h6>
                                    <p class="widget-card-title">@lang('Payments Charge')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow">
                                <i class="las la-angle-right"></i>
                            </span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-6">
            <div class="card box-shadow3 h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Withdrawals')</h5>
                    <div class="widget-card-wrapper">
                        <div class="widget-card bg--success">
                            <a class="widget-card-link" href="{{ route('admin.withdraw.data.all') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon">
                                    <i class="lar la-credit-card"></i>
                                </div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ showAmount($withdrawals['total_withdraw_amount']) }}
                                    </h6>
                                    <p class="widget-card-title">@lang('Total Withdrawn')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow">
                                <i class="las la-angle-right"></i>
                            </span>
                        </div>

                        <div class="widget-card bg--warning">
                            <a class="widget-card-link" href="{{ route('admin.withdraw.data.pending') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon">
                                    <i class="fas fa-spinner"></i>
                                </div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ $withdrawals['total_withdraw_pending'] }}</h6>
                                    <p class="widget-card-title">@lang('Pending Withdrawals')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow">
                                <i class="las la-angle-right"></i>
                            </span>
                        </div>

                        <div class="widget-card bg--danger">
                            <a class="widget-card-link" href="{{ route('admin.withdraw.data.rejected') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon">
                                    <i class="las la-times-circle"></i>
                                </div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ $withdrawals['total_withdraw_rejected'] }}</h6>
                                    <p class="widget-card-title">@lang('Rejected Withdrawals')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow">
                                <i class="las la-angle-right"></i>
                            </span>
                        </div>

                        <div class="widget-card bg--primary">
                            <a class="widget-card-link" href="{{ route('admin.withdraw.data.all') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon">
                                    <i class="las la-percent"></i>
                                </div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ showAmount($withdrawals['total_withdraw_charge']) }}
                                    </h6>
                                    <p class="widget-card-title">@lang('Withdrawal Charge')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow">
                                <i class="las la-angle-right"></i>
                            </span>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gy-4 mt-2">

        <div class="col-xxl-3 col-sm-6">

            <x-widget value="{{ $widget['total_videos'] }}" title="Total Videos" style="6" outline="true"
                link="{{ route('admin.videos.index') }}" icon="las la-video" bg="primary" />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['free_videos'] }}" title="Free Videos" style="6" outline="true"
                link="{{ route('admin.videos.free') }}" icon="la la-file-video" bg="success" />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['stock_videos'] }}" title="Stock Videos" style="6" outline="true"
                link="{{ route('admin.videos.stock') }}" icon="la la-hand-holding-usd" bg="danger" />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['regular_videos'] }}" title="Regular Videos" style="6" outline="true"
                link="{{ route('admin.videos.regular') }}" icon="fas fa-video" bg="warning" />
        </div><!-- dashboard-w1 end -->
    </div><!-- row end-->


    <div class="row gy-4 mt-2">

        <div class="col-xxl-3 col-sm-6">

            <x-widget value="{{ $widget['short_videos'] }}" title="Shorts Videos" style="5"
                link="{{ route('admin.videos.shorts') }}" icon="fas fa-play" bg="primary" />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['public_videos'] }}" title="Public Videos" style="5"
                link="{{ route('admin.videos.public') }}" icon="fas fa-file-video" bg="success" />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['private_videos'] }}" title="Private videos" style="5"
                link="{{ route('admin.videos.private') }}" icon="las la-video-slash" bg="danger" />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['draft_videos'] }}" title="Draft Videos" style="5"
                link="{{ route('admin.videos.draft') }}" icon="fa-solid fa-photo-film" bg="warning" />
        </div><!-- dashboard-w1 end -->
    </div><!-- row end-->

    <div class="row mb-none-30 mt-30">
        <div class="col-xl-6 mb-30">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between">
                        <h5 class="card-title">@lang('Payments & Withdraw Report')</h5>

                        <div class="border p-1 cursor-pointer rounded chart-title-text" id="dwDatePicker">
                            <i class="la la-calendar"></i>&nbsp;
                            <span></span> <i class="la la-caret-down"></i>
                        </div>
                    </div>
                    <div id="dwChartArea"> </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-30">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between">
                        <h5 class="card-title">@lang('Transactions Report')</h5>

                        <div class="border p-1 cursor-pointer rounded chart-title-text" id="trxDatePicker">
                            <i class="la la-calendar"></i>&nbsp;
                            <span></span> <i class="la la-caret-down"></i>
                        </div>
                    </div>

                    <div id="transactionChartArea"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-none-30 mt-5">
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By Browser') (@lang('Last 30 days'))</h5>
                    <canvas id="userBrowserChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By OS') (@lang('Last 30 days'))</h5>
                    <canvas id="userOsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">@lang('Login By Country') (@lang('Last 30 days'))</h5>
                    <canvas id="userCountryChart"></canvas>
                </div>
            </div>
        </div>
    </div>



    @include('admin.partials.cron_modal')
@endsection
@push('breadcrumb-plugins')
    <button class="btn btn-outline--primary btn-sm" data-bs-toggle="modal" data-bs-target="#cronModal">
        <i class="las la-server"></i>@lang('Cron Setup')
    </button>
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

        let dwChart = barChart(
            document.querySelector("#dwChartArea"),
            `{{ __(gs('cur_text')) }}`,
            [{
                    name: 'Payments',
                    data: []
                },
                {
                    name: 'Withdrawn',
                    data: []
                }
            ],
            [],
        );

        let trxChart = lineChart(
            document.querySelector("#transactionChartArea"),
            [{
                    name: "Plus Transactions",
                    data: []
                },
                {
                    name: "Minus Transactions",
                    data: []
                }
            ],
            []
        );


        const depositWithdrawChart = (startDate, endDate) => {

            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }

            const url = `{{ route('admin.chart.deposit.withdraw') }}`;

            $.get(url, data,
                function(data, status) {
                    if (status == 'success') {
                        dwChart.updateSeries(data.data);
                        dwChart.updateOptions({
                            xaxis: {
                                categories: data.created_on,
                            }
                        });
                    }
                }
            );
        }

        const transactionChart = (startDate, endDate) => {

            const data = {
                start_date: startDate.format('YYYY-MM-DD'),
                end_date: endDate.format('YYYY-MM-DD')
            }

            const url = `{{ route('admin.chart.transaction') }}`;


            $.get(url, data,
                function(data, status) {
                    if (status == 'success') {


                        trxChart.updateSeries(data.data);
                        trxChart.updateOptions({
                            xaxis: {
                                categories: data.created_on,
                            }
                        });
                    }
                }
            );
        }



        $('#dwDatePicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#dwDatePicker span',
            start, end));
        $('#trxDatePicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#trxDatePicker span',
            start, end));

        changeDatePickerText('#dwDatePicker span', start, end);
        changeDatePickerText('#trxDatePicker span', start, end);

        depositWithdrawChart(start, end);
        transactionChart(start, end);

        $('#dwDatePicker').on('apply.daterangepicker', (event, picker) => depositWithdrawChart(picker.startDate, picker
            .endDate));
        $('#trxDatePicker').on('apply.daterangepicker', (event, picker) => transactionChart(picker.startDate, picker
            .endDate));

        piChart(
            document.getElementById('userBrowserChart'),
            @json(@$chart['user_browser_counter']->keys()),
            @json(@$chart['user_browser_counter']->flatten())
        );

        piChart(
            document.getElementById('userOsChart'),
            @json(@$chart['user_os_counter']->keys()),
            @json(@$chart['user_os_counter']->flatten())
        );

        piChart(
            document.getElementById('userCountryChart'),
            @json(@$chart['user_country_counter']->keys()),
            @json(@$chart['user_country_counter']->flatten())
        );





        const ffmpeg = "{{ gs('ffmpeg_status') }}";
        const storage = "{{ gs('is_storage') }}";




        if (storage == 1) {

            $(document).ready(function() {

                const redirectUrl = "{{ route('admin.storage.index') }}"
                $.ajax({
                    type: "get",
                    url: "{{ route('admin.check.space') }}",

                    success: function(response) {


                        if (response.status == true) {
                            $('.storageAlert')
                                .removeClass('d-none')
                                .html(`
                                    <div class="custom-alert alert alert--danger" role="alert">
                                        <span class="alert__icon">
                                            <i class="far fa-bell"></i>
                                        </span>
                                        <div class="alert__content">
                                            <h5 class="alert-heading">@lang('Storage Limit Reached')</h5>
                                            <p>@lang('Unable to upload the file as the selected storage location has reached its capacity. Please free up space or upgrade your storage. <a href="${redirectUrl}">Click here</a>')</p>
                                        </div>
                                    </div>
                                `);
                        } else {
                            $('.storageAlert').addClass('d-none').html('');
                        }

                    }
                });
            });
        }




        if (ffmpeg == 1) {

            $(document).ready(function() {

                const redirectUrl = "{{ route('admin.setting.system.configuration') }}"
                $.ajax({
                    type: "get",
                    url: "{{ route('admin.setting.check.ffmpeg') }}",

                    success: function(response) {
                        if (response.status == 'error') {
                            $('.ffmpegAlert').removeClass('d-none').html(`
                                <div class="custom-alert alert alert--danger" role="alert">
                                    <span class="alert__icon">
                                        <i class="far fa-bell"></i>
                                    </span>
                                    <div class="alert__content">
                                        <h5 class="alert-heading">@lang('FFmpeg is required')</h5>
                                        <p>@lang('FFmpeg is essential for processing and converting video files on this platform. Without FFmpeg, video format conversions will not work properly. If you do not use FFmpeg, you can <a href="${redirectUrl}">Disable FFmpeg</a> it in the settings.')</p>
                                    </div>
                                </div>
                            `);
                        } else {
                            $('.ffmpegAlert').addClass('d-none').html('');
                        }

                    }
                });
            });
        }
    </script>
@endpush
@push('style')
    <style>
        .apexcharts-menu {
            min-width: 120px !important;
        }

        .custom-alert {
            align-items: flex-start;
            gap: 16px;
            padding: 16px;
        }

        .custom-alert.alert--danger {
            background-color: rgb(235 34 34 / 10%);
            border-left: 5px solid #eb2222;
        }

        .custom-alert .alert__icon {
            height: 36px;
            width: 36px;
            background: rgb(235 34 34 / 10%);
            color: #eb2222;
            border-radius: 50%;
            display: grid;
            place-content: center;
            flex-shrink: 0;
        }

        .alert__content {
            flex: 1;
        }

        .alert-heading {
            margin-bottom: 6px;
        }
    </style>
@endpush
