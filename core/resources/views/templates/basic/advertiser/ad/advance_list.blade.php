@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">

        <div class="dashboard-card-wrapper advertisement-card-wrapper">


            <div class="dashboard-card">
                <div class="dashboard-card__shape">
                    <img src="{{ asset($activeTemplateTrue . 'images/ds-shape.png') }}" alt="">
                </div>
                <div class="left">
                    <h5 class="dashboard-card__title">@lang('Total Campaign') </h5>
                    <h1 class="dashboard-card__number"> {{ $totalCampaign }} </h1>
                </div>
                <span class="dashboard-card__icon"> <img src="{{ asset($activeTemplateTrue . 'images/icon-img/dc-4.png') }}"
                        alt=""></span>
            </div>



            <div class="dashboard-card">
                <div class="dashboard-card__shape">
                    <img src="{{ asset($activeTemplateTrue . 'images/ds-shape.png') }}" alt="">
                </div>
                <div class="left">
                    <h5 class="dashboard-card__title">@lang('Total Ads')</h5>
                    <h1 class="dashboard-card__number"> {{ $totalAds }} </h1>
                </div>
                <span class="dashboard-card__icon"><img src="{{ asset($activeTemplateTrue . 'images/icon-img/dc-1.png') }}"
                        alt=""></span>
            </div>
            <div class="dashboard-card">
                <div class="dashboard-card__shape">
                    <img src="{{ asset($activeTemplateTrue . 'images/ds-shape.png') }}" alt="">
                </div>
                <div class="left">
                    <h5 class="dashboard-card__title"> @lang('Total Daily Cost') </h5>
                    <h1 class="dashboard-card__number">
                        {{ gs('cur_sym') }}{{ showAmount($totalDailyBudget, currencyFormat: false) }} </h1>
                </div>
                <span class="dashboard-card__icon"><img
                        src="{{ asset($activeTemplateTrue . 'images/icon-img/increase-65.png') }}" alt=""></span>
            </div>
            <div class="dashboard-card">
                <div class="dashboard-card__shape">
                    <img src="{{ asset($activeTemplateTrue . 'images/ds-shape.png') }}" alt="">
                </div>
                <div class="left">
                    <h5 class="dashboard-card__title"> @lang('Total Cost Amount') </h5>
                    <h1 class="dashboard-card__number">
                        {{ gs('cur_sym') }}{{ showAmount($totalCosts, currencyFormat: false) }} </h1>
                </div>
                <span class="dashboard-card__icon"><img
                        src="{{ asset($activeTemplateTrue . 'images/icon-img/total_cost.png') }}"
                        alt=""></span>
            </div>

        </div>



        <div class="advertising-table">
            <form class="advertising-table__search">
                <div class="form-group">
                    <input class="form--control" name="search" value="{{ request()->search }}" type="text"
                        placeholder="Search Here...">
                    <button class="search-btn" type="submit"><i class="vti-search"></i></button>
                </div>
            </form>
            <table class="table table--responsive--lg">
                <thead>
                    <tr>
                        <th>@lang('Ad Title')</th>
                        <th>@lang('Campaign Title')</th>
                        <th>@lang('Ad Reached')</th>
                        <th>@lang('Ad Engagement')</th>
                        <th>@lang('Daily Budget | Total Costs')</th>
                        <th>@lang('Target Country')</th>
                        <th>@lang('Ad Type')</th>
                        <th>@lang('Status')</th>
                        <th>@lang('Action')</th>

                    </tr>
                </thead>
                <tbody>
                    @forelse ($advertisements as $advertisement)
                        <tr>
                            <td>
                                <p class="campaign-item__title">{{ __($advertisement->title) }}</p>
                                <span class="campaign-item__date">
                                    {{ showDateTime($advertisement->created_at) }}</span>
                            </td>

                            <td>
                                {{ __($advertisement->campaign->title) }}
                            </td>

                            <td>
                                {{ formatNumber($advertisement->ad_reached) }} <br>
                                <small class="text--success">@lang('Ads Reached'):
                                    {{ formatNumber($advertisement->adReaches()->count()) }} </small>
                            </td>
                            <td>
                                {{ formatNumber($advertisement->ad_engagement) }} <br>
                                <small class="text--success">@lang('Ads Engagement'):
                                    {{ formatNumber($advertisement->advertisementAnalytics()->count()) }} </small>

                            </td>
                            <td>
                                {{ showAmount($advertisement->daily_costs) }} <br>
                                {{ showAmount($advertisement->total_amount) }}
                            </td>

                            <td>
                                <button class="action-btn active-btn countryBtn" type="button"
                                    data-countries="{{ $advertisement->countries }}">
                                    <i class="fas fa-tags"></i>
                                    @lang('Show all')</button>
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
                                <div class="action-btns">
                                    @if ($advertisement->status == Status::RUNNING)
                                        <button class="action-btn  pause-btn confirmationBtn"
                                            data-action="{{ route('user.advertiser.status', $advertisement->id) }}"
                                            data-question="@lang('Are you sure want to pause this advertisement?')">
                                            <i class="las la-pause"></i>@lang('Pause')
                                        </button>
                                    @elseif($advertisement->status == Status::PAUSE)
                                        <button class="action-btn info-btn confirmationBtn"
                                            data-action="{{ route('user.advertiser.status', $advertisement->id) }}"
                                            data-question="@lang('Are you sure want to pause this advertisement?')">
                                            <i class="las la-pause"></i>@lang('Running')
                                        </button>
                                    @endif

                                    @if ($advertisement->status == Status::ADVERTISEMENT_REJECTED)
                                        <button class="action-btn delete-btn rejectedDetailsBtn"
                                            data-reject_reason="{{ $advertisement->reject_reason }}" >
                                            <i class="las la-ban"></i> @lang('Detail')</button>
                                    @endif

                                    <a href="{{ route('user.advertiser.ad.analytics', $advertisement->id) }}" class="action-btn  success-btn">@lang('Analytics')</a>

                                </div>
                            </td>


                        </tr>
                    @empty
                        <tr>
                            <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                        </tr>
                    @endforelse

                </tbody>
            </table>

            @if ($advertisements->hasPages())
                @php echo paginateLinks($advertisements) @endphp
            @endif
        </div>
    </div>



    {{-- categories  --}}
    <div class="custom--modal scale-style modal fade" id="countriesModal" aria-labelledby="exampleModalLabel"
        aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modale-title mb-0" id="exampleModalLabel">@lang('Countries')</h5>
                    <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="countries-body"></div>
                </div>
            </div>
        </div>
    </div>

    

        <div class="modal custom--modal scale-style fade" id="rejectionReason">
            <div class="modal-dialog modal-dialog-centered" role="document">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Advertisement Rejection Reason')</h5>
                        <button type="button" class="close modal-close-btn" data-bs-dismiss="modal" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="rejectedReason"></p>
                    </div>
                </div>
            </div>
        </div>
  


    <x-confirmation-modal frontend="true" />
@endsection

@push('style')
    <style>
        .action-btn:disabled {
            background-color: hsl(0deg 0% 35.43%)
        }
    </style>
@endpush
@push('script')
    <script>
        (function($) {
            'use strict';
            $('.countryBtn').on('click', function() {

                let countries = $(this).data('countries');
                let countriesHtml = '';
                countries.forEach(item => {
                    countriesHtml += `<span class="badge badge--white me-1">${item.country}</span>`;
                });
                $('.countries-body').html(countriesHtml);
                $('#countriesModal').modal('show');
            })

            $('.rejectedDetailsBtn').on('click', function() {
                let rejectReason = $(this).data('reject_reason');
                $('.rejectedReason').text(rejectReason);
                $('#rejectionReason').modal('show');
            });

        })(jQuery)
    </script>
@endpush
