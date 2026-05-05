@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="advertising-heading">
            <h3 class="advertising-heading__title"><span class="icon"><i class="vti-advertising"></i></span>
                @lang('Advertising')</h3>
            <a class="btn btn--base" href="{{ route('user.advertiser.create.ad') }}"><i class="vti-plus"></i>
                @lang('Create ad')</a>
        </div>
        <div class="advertising-card-wrapper">
            <div class="advertising-card">
                <span class="advertising-card__icon"><i class="fas fa-chart-line"></i></span>
                <div class="advertising-card__content">
                    <h3 class="advertising-card__title">@lang('Total Click Purchased')</h3>
                    <h2 class="advertising-card__amount">{{ formatNumber($totalClick) }}</h2>
                </div>
            </div>
            <div class="advertising-card">
                <span class="advertising-card__icon"><i class="fas fa-mouse"></i></span>
                <div class="advertising-card__content">
                    <h3 class="advertising-card__title">@lang('Total Impression Purchased')</h3>
                    <h2 class="advertising-card__amount">{{ formatNumber($totalImpression) }}</h2>
                </div>
            </div>

            <div class="advertising-card">
                <span class="advertising-card__icon "><i class="fas fa-eye"></i></span>
                <div class="advertising-card__content">
                    <h3 class="advertising-card__title">@lang('Available Impressions')</h3>
                    <h2 class="advertising-card__amount">{{ formatNumber($availableImpression) }}</h2>
                </div>
            </div>

            <div class="advertising-card">
                <span class="advertising-card__icon"><i class="fas fa-hand-pointer"></i></span>
                <div class="advertising-card__content">
                    <h3 class="advertising-card__title">@lang('Available Clicks')</h3>
                    <h2 class="advertising-card__amount">{{ formatNumber($availableClick) }}</h2>
                </div>
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
                        <th>@lang('Status')</th>
                        <th>@lang('Title')</th>
                        <th>@lang('Ad Type')</th>
                        <th>@lang('Available Token')</th>
                        <th>@lang('Results')</th>
                        <th>@lang('Categories')</th>
                        <th>@lang('Payment Status')</th>
                        <th>@lang('Action')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($advertisements as $advertisement)
                        <tr>
                            <td>
                                @php
                                    echo $advertisement->statusBadge;
                                @endphp
                            </td>
                            <td>{{ __($advertisement->title) }}</td>
                            <td>
                                @php
                                    echo $advertisement->adTypeBadge;
                                @endphp
                            </td>

                            <td>
                                <div>
                                    <div class="result table--result text--success">
                                        <span class="icon"><i class="fas fa-chart-line"></i></span>
                                        <span class="text">{{ $advertisement->available_impression }}
                                            @lang('impression')</span>
                                    </div>
                                    <div class="result table--result text--voilet">
                                        <small class="icon"><i class="fas fa-mouse"></i></small>
                                        <small class="text">{{ $advertisement->available_click ?? 0 }}
                                            @lang('Click')</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div>
                                    <div class="result table--result text--warning">
                                        <span class="icon"><i class="fas fa-chart-line"></i></span>
                                        <span
                                            class="text">+{{ $advertisement->advertisementAnalytics()->impression()->count() }}
                                            @lang('impression')</span>
                                    </div>
                                    <div class="result table--result text--white">
                                        <small class="icon"><i class="fas fa-mouse"></i></small>
                                        <small
                                            class="text">+{{ $advertisement->advertisementAnalytics()->click()->count() }}
                                            @lang('Click')</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <button class="action-btn active-btn categoryBtn" type="button"
                                    data-categories="{{ $advertisement->categories }}">
                                    <i class="fas fa-tags"></i>
                                    @lang('Show all')</button>
                            </td>
                            <td>
                                @php
                                echo $advertisement->paymentStatusBadge;
                            @endphp
                            </td>
                            <td data-label="Action">
                                <div class="action-buttons">
                                    @if ($advertisement->status == Status::RUNNING)
                                        <button class="action-btn pause-btn confirmationBtn"
                                        @if($advertisement->payment_status != Status::PAYMENT_SUCCESS)
                                            disabled @endif
                                            data-action="{{ route('user.advertiser.status', $advertisement->id) }}"
                                            data-question="@lang('Are you sure want to pause this ad')?" type="button" data-bs-toggle="tooltip"
                                            title="@lang('Pause this ad')">
                                            <i class="fas fa-play"></i>
                                            @lang('Pause')</button>
                                    @elseif($advertisement->status == Status::PAUSE)
                                        <button class="action-btn active-btn confirmationBtn" @if($advertisement->payment_status != Status::PAYMENT_SUCCESS)
                                            disabled @endif
                                            data-action="{{ route('user.advertiser.status', $advertisement->id) }}"
                                            data-question="@lang('Are you sure want to running this ad')?" type="button" data-bs-toggle="tooltip"
                                            title="@lang('Running this ad')">
                                            <i class="fas fa-pause"></i>
                                            @lang('Running')</button>
                                            

                                    @elseif($advertisement->payment_status == Status::PAYMENT_INITIATE)
                                        <a href="{{ route('user.deposit.index', $advertisement->id) }}"
                                            class="action-btn info-btn" type="button" data-bs-toggle="tooltip"
                                            title="@lang('Make a payment for this advertisement')">
                                            <i class="fas fa-money-bill-wave"></i>
                                            @lang('Pay Now')</a>
                                    @endif

                                </div>
                            </td>
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

            @if ($advertisements->hasPages())
                @php echo paginateLinks($advertisements) @endphp
            @endif
        </div>
    </div>



    {{-- categories  --}}
    <div class="custom--modal scale-style modal fade" id="categoriesModal" aria-labelledby="exampleModalLabel"
        aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modale-title mb-0" id="exampleModalLabel">@lang('Categories')</h5>
                    <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="categories-body"></div>
                </div>
            </div>
        </div>
    </div>

    <x-confirmation-modal frontend="true" />
@endsection
@push('style')
<style>
    .action-btn:disabled{
        background-color:hsl(0deg 0% 35.43%)
    }
</style>
    
@endpush
@push('script')
    <script>
        (function($) {
            'use strict';
            $('.categoryBtn').on('click', function() {
                let categories = $(this).data('categories');
                let categoriesHtml = '';
                categories.forEach(category => {
                    categoriesHtml += `<span class="badge badge--white me-1">${category.name}</span>`;
                });
                $('.categories-body').html(categoriesHtml);
                $('#categoriesModal').modal('show');
            })


        })(jQuery)
    </script>
@endpush
