@extends('admin.layouts.app')
@section('panel')
    <div class="row justify-content-center gy-4">


        <div class="col-12">
            <div class="row gy-4">

                @if (gs('ads_module'))
                    <div class="col-xxl-3 col-sm-6">
                        <x-widget style="7"
                            link="{{ route('admin.campaign.index', $user->id) }}"
                            title="Total Campaigns" icon="las la-bullhorn" value="{{ $totalCampaign }}" bg="indigo"
                            type="2" />
                    </div>
                @endif

                <div class="col-xxl-3 col-sm-6">
                        <x-widget style="7"
                            :link="gs('ads_module') 
                                ? route('admin.advance.ads.index', $user->id) . '?search=' . $user->username 
                                : route('admin.advertisement.index', $user->id) . '?search=' . $user->username"
                            title="Total Advertisements" icon="las la-ad" value="{{ $widget['total_ads'] }}" bg="indigo" type="2" />
                    </div>

                    <div class="col-xxl-3 col-sm-6">
                        <x-widget style="7"
                            :link="gs('ads_module') 
                                ? route('admin.advance.ads.running', $user->id) . '?search=' . $user->username 
                                : route('admin.advertisement.running', $user->id) . '?search=' . $user->username"
                            title="Running Advertisements" icon="las la-pause" value="{{ $widget['running_ads'] }}" bg="8" type="2" />
                    </div>

                    <div class="col-xxl-3 col-sm-6">
                        <x-widget style="7"
                            :link="gs('ads_module') 
                                ? route('admin.advance.ads.pause', $user->id) . '?search=' . $user->username 
                                : route('admin.advertisement.pause', $user->id) . '?search=' . $user->username"
                            title="Pause Advertisements" icon="la la-play" value="{{ $widget['pause_ads'] }}" bg="6" type="2" />
                    </div>


                @if (!gs('ads_module'))
                    <div class="col-xxl-3 col-sm-6">
                        <x-widget style="7"
                            link="{{ route('admin.advertisement.click', $user->id) }}?search={{ $user->username }}"
                            title="Clickable Advertisements" icon="las la-mouse" value="{{ $widget['click_ads'] }}"
                            bg="17" type="2" />
                    </div>


                    <div class="col-xxl-3 col-sm-6">
                        <x-widget style="6"
                            link="{{ route('admin.advertisement.impression', $user->id) }}?search={{ $user->username }}"
                            title="Impresision Advertisements" icon="las la-eye" value="{{ $widget['impressions_ads'] }}"
                            bg="success" type="2" />
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <x-widget style="6"
                            link="{{ route('admin.advertisement.both', $user->id) }}?search={{ $user->username }}"
                            title="Both Type Advertisements" icon="las la-video" value="{{ $widget['both_type_ads'] }}"
                            bg="8" type="2" />
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <x-widget style="6"
                            link="{{ route('admin.report.transaction', $user->id) }}?search={{ $user->username }}"
                            title="Total Spent Amount" icon="la la-usd" value="{{ showAmount($widget['total_spent_amount']) }}"
                            bg="6" type="2" />
                    </div>
    
                    <div class="col-xxl-3 col-sm-6">
                        <x-widget style="6"
                            link="{{ route('admin.report.transaction', $user->id) }}?search={{ $user->username }}"
                            title="Last Seven Days Spent Amount" icon="las la-money-bill-wave-alt"
                            value="{{ showAmount($widget['last_seven_days_spent']) }}" bg="17" type="2" />
                    </div>
                @endif

                @if(gs('ads_module'))
                   <div class="col-xxl-3 col-sm-6">
                        <x-widget style="6"
                            link="{{route('admin.advance.ads.index', $user->id) }}"
                            title="Daily Scheduled Ads" icon="la la-sync" value="{{$dailyAds}}"
                            bg="2" type="2" />
                    </div>
    
                      <div class="col-xxl-3 col-sm-6">
                        <x-widget style="6"
                            link="{{route('admin.advance.ads.index', $user->id) }}"
                            title="Custom Scheduled Ads" icon="la la-chart-area" value="{{$customAds }}"
                            bg="1" type="2" />
                    </div>

                   
                    <div class="col-xxl-3 col-sm-6">
                        <x-widget style="6"
                            link="{{route('admin.advance.ads.index', $user->id) }}"
                            title="Total Ads Budget" icon="la la-usd" value="{{ showAmount($totalBudget) }}"
                            bg="6" type="2" />
                    </div>
    
                    <div class="col-xxl-3 col-sm-6">
                        <x-widget style="6"
                            link="{{route('admin.advance.ads.index', $user->id) }}"
                            title="Available Budget" icon="las la-money-bill-wave-alt"
                            value="{{ showAmount($availableBudget) }}" bg="17" type="2" />
                    </div>
                @endif


            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title">@lang('Report for Impressions & Click')</h4>
                    <div class="border p-1 cursor-pointer rounded chart-title-text" id="dataPicker">
                        <i class="la la-calendar"></i>&nbsp;
                        <span></span> <i class="la la-caret-down"></i>
                    </div>
                </div>
                <div class="card-body">

                    <div class="adsReport"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5>@lang('Advertiser Information')</h5>
                </div>
                <div class="card-body">
                    @if ($user->advertiser_data)
                        <ul class="list-group">
                            @foreach ($user->advertiser_data as $val)
                                @continue(!$val->value)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ __($val->name) }}
                                    <span>
                                        @if ($val->type == 'checkbox')
                                            {{ implode(',', $val->value) }}
                                        @elseif($val->type == 'file')
                                            @if ($val->value)
                                                <a
                                                    href="{{ route('admin.download.attachment', encrypt(getFilePath('verify') . '/' . $val->value)) }}"><i
                                                        class="fa-regular fa-file"></i> @lang('Attachment') </a>
                                            @else
                                                @lang('No File')
                                            @endif
                                        @else
                                            <p>{{ __($val->value) }}</p>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <h5 class="text-center">@lang('Data not found')</h5>
                    @endif

                    @if ($user->advertiser_status == Status::ADVERTISER_REJECTED)
                        <div class="my-3">
                            <h6>@lang('Rejection Reason')</h6>
                            <p>{{ $user->advertiser_rejection_reason }}</p>
                        </div>
                    @endif

                    @if ($user->advertiser_status == Status::ADVERTISER_PENDING)
                        <div class="d-flex flex-wrap justify-content-end mt-3">
                            <button class="btn btn-outline--danger me-3" data-bs-toggle="modal"
                                data-bs-target="#advertiserRejectionModal"><i
                                    class="las la-ban"></i>@lang('Reject')</button>
                            <button class="btn btn-outline--success confirmationBtn" data-question="@lang('Are you sure to approve this documents?')"
                                data-action="{{ route('admin.advertiser.data.approve', $user->id) }}"><i
                                    class="las la-check"></i>@lang('Approve')</button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>


    <div id="advertiserRejectionModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Reject Advertiser Documents')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.advertiser.data.reject', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-primary p-3">
                            @lang('If you reject these documents, the user will be able to submit new ones, which will replace the previous documents.')
                        </div>

                        <div class="form-group">
                            <label>@lang('Rejection Reason')</label>
                            <textarea class="form-control" name="reason" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection


@push('breadcrumb-plugins')
    <a href="{{ route('admin.users.login', $user->id) }}" target="_blank" class="btn btn-sm btn-outline--primary"><i
            class="las la-sign-in-alt"></i>@lang('Login as Advertiser')</a>
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


        let adsReport = lineChart(
            document.querySelector(".adsReport"),
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

            const url = @json(route('admin.advertiser.report', $user->id));

            $.get(url, data,
                function(data, status) {



                    if (status == 'success') {

                        adsReport.updateSeries(data.data);

                        adsReport.updateOptions({
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







        $('#dataPicker').daterangepicker(dateRangeOptions, (start, end) => changeDatePickerText('#dataPicker span', start,
            end));


        changeDatePickerText('#dataPicker span', start, end);

        videoChart(start, end);


        $('#dataPicker').on('apply.daterangepicker', (event, picker) => videoChart(picker.startDate, picker.endDate));
    </script>
@endpush
