@extends('admin.layouts.app')
@section('panel')
    @php
        use Carbon\Carbon;
    @endphp

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('User')</th>
                                    <th>@lang('Ad Title')</th>
                                    <th>@lang('Campaign Title')</th>
                                    <th>@lang('Ad Reached')</th>
                                    <th>@lang('Ad Engagement')</th>
                                    <th>@lang('Daily Budget | Total Costs')</th>
                                    <th>@lang('Ad Schedule Type')</th>
                                    <th>@lang('Ad Type')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($advertisements as $advertisement)
                                    @php
                                        $totalAdDays = 0;

                                        if ($advertisement->schedule_type == 1) {
                                            $start = Carbon::parse($advertisement->start_date);
                                            $end = Carbon::parse($advertisement->end_date);

                                            $totalAdDays += $start->diffInDays($end) + 1;
                                        }

                                        if ($advertisement->schedule_type == 2 && $advertisement->schedules) {
                                            foreach ($advertisement->schedules as $schedule) {
                                                $start = Carbon::parse($schedule->custom_start_date);
                                                $end = Carbon::parse($schedule->custom_end_date);

                                                $totalAdDays += $start->diffInDays($end) + 1;
                                            }
                                        }

                                    @endphp


                                    <tr>
                                        <td>
                                            {{ __($advertisement->user?->fullname) }} <br>

                                            <a
                                                href="{{ route('admin.users.detail', $advertisement->user_id) }}"><span>@</span>{{ $advertisement->user?->username }}</a>
                                        </td>
                                        <td>
                                            <div class="campaign-item">
                                                <div class="campaign-item__content">
                                                    <p class="campaign-item__title ">{{ __($advertisement->title) }}</p>

                                                    <small class="text--primary">@lang('For '){{ round($totalAdDays) }}
                                                        @lang('Days')</small>


                                                </div>
                                            </div>
                                        </td>


                                        <td>
                                            {{ __($advertisement->campaign?->title) }}
                                        </td>

                                        <td>
                                            {{ formatNumber($advertisement->ad_reached) }} <br>
                                            <small class="text--success">@lang('Ads Reached'):
                                                {{ formatNumber($advertisement->adReaches()->count()) }} </small>
                                        </td>
                                        <td>
                                            {{ formatNumber($advertisement->ad_engagement) }} <br>
                                            <small class="text--success">@lang('Ads Engagement'):
                                                {{ formatNumber($advertisement->advertisementAnalytics()->count()) }}
                                            </small>

                                        </td>


                                        <td>
                                            {{ showAmount($advertisement->daily_costs) }} <br>
                                            {{ showAmount($advertisement->total_amount) }}
                                        </td>

                                        <td>
                                            @if ($advertisement->schedule_type == 1)
                                                @lang('Daily')
                                            @elseif ($advertisement->schedule_type == 2)
                                                @lang('Custom')
                                            @endif
                                        </td>



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
                                        <td>
                                            <div class="button--group">
                                                <a class="btn btn-sm btn-outline--primary"
                                                    href="{{ route('admin.advance.ads.detail', $advertisement->id) }}"><i
                                                        class="las la-desktop"></i>@lang('Detail')</a>

                                                @if ($advertisement->status == Status::RUNNING)
                                                    <button class="btn btn-sm btn-outline--danger confirmationBtn"
                                                        data-action="{{ route('admin.advance.ads.status', $advertisement->id) }}"
                                                        data-question="@lang('Are you sure want to pause this advertisement.?')">
                                                        <i class="las la-pause"></i>@lang('Pause')
                                                    </button>
                                                @elseif($advertisement->status == Status::PAUSE)
                                                    <button class="btn btn-sm btn-outline--success confirmationBtn"
                                                        data-action="{{ route('admin.advance.ads.status', $advertisement->id) }}"
                                                        data-question="@lang('Are you sure want to running this advertisement.?')">
                                                        <i class="las la-play"></i>@lang('Running')
                                                    </button>
                                                @endif
                                            </div>
                                        </td>

                                    </tr>
                                @empty
                                <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse



                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($advertisements->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($advertisements) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection




@push('breadcrumb-plugins')
    <x-search-form placeholder="Username / Title" />
@endpush
