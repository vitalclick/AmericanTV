@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two custom-data-table">
                            @php
                                use Carbon\Carbon;
                            @endphp


                            <thead>
                                <tr>
                                    <th>@lang('Advertisement Name')</th>
                                    <th>@lang('Start Date - End Date')</th>
                                    <th>@lang('Ad Schedule Type')</th>
                                    <th>@lang('Ad Reached')</th>
                                    <th>@lang('Ad Engagement')</th>
                                    <th>@lang('Ad Running')</th>
                                    <th>@lang('Ad Type')</th>
                                    <th>@lang('Daily Budget | Ad Cost')</th>
                                    <th>@lang('Status')</th>
                                  
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($campaign->advertisements as $advertisement)
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
                                        <td>{{ __($advertisement->title) }}</td>

                                        <td>{{ showDateTime($advertisement->start_date, 'Y-m-d') }} -
                                            {{ showDateTime($advertisement->end_date, 'Y-m-d') }}</td>

                                        <td>
                                            @if ($advertisement->schedule_type == 1)
                                                @lang('Daily')
                                            @elseif ($advertisement->schedule_type == 2)
                                                @lang('Custom')
                                            @endif
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

                                        <td>{{ round($totalAdDays) }} @lang('Days')</td>
                                        <td>
                                            @php
                                                echo $advertisement->adTypeBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            {{ showAmount($advertisement->daily_costs) }} <br>
                                            {{ showAmount($advertisement->total_amount) }}

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
           
            </div><!-- card end -->
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder='Name' />
@endpush
