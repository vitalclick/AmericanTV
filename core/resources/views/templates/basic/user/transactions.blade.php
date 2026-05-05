@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="col-md-12">
            <div class="advertising-table">
                <div class="table--header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">@lang('Transactions')</h3>
                    <div class="responsive-filter-card">
                        <div class="show-filter text-end">
                            <button class="btn btn--base showFilterBtn btn--sm" type="button">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" height="16" width="16"
                                    viewBox="0 0 13 13"
                                    class="src-routes-media-common-components-filters-popup-index__icon--2LUuh">
                                    <path fill="currentColor"
                                        d="M2.8 3.614V.667C2.8.297 2.51 0 2.15 0S1.5.298 1.5.667v2.947C.66 3.696 0 4.415 0 5.288S.66 6.889 1.5 6.96v5c0 .37.29.668.65.668s.65-.298.65-.668v-5c.84-.082 1.5-.8 1.5-1.673s-.66-1.602-1.5-1.674m-.15 2.033h-1a.36.36 0 0 1 0-.719h1a.36.36 0 0 1 0 .719M6.8 7.721V.667C6.8.297 6.51 0 6.15 0S5.5.298 5.5.667v7.054c-.84.082-1.5.8-1.5 1.674 0 .872.66 1.601 1.5 1.673v.893c0 .37.29.668.65.668s.65-.298.65-.668v-.893c.84-.082 1.5-.8 1.5-1.673S7.64 7.793 6.8 7.72m-.15 2.033h-1a.36.36 0 0 1 0-.719h1a.36.36 0 0 1 0 .719m4.15-8.193V.667c0-.37-.29-.667-.65-.667S9.5.298 9.5.667v.894c-.84.082-1.5.8-1.5 1.673s.66 1.602 1.5 1.674v7.053c0 .37.29.668.65.668s.65-.298.65-.668V4.908c.84-.082 1.5-.801 1.5-1.674s-.66-1.601-1.5-1.673m-.15 2.033h-1a.36.36 0 0 1 0-.72h1a.36.36 0 0 1 0 .72">
                                    </path>
                                </svg>
                                @lang('Filter')</button>
                        </div>
                        <form class="responsive-filter-form">
                            <div class="responsive-filter-title d-flex justify-content-between align-items-center">
                                <h4 class="mb-0"> @lang('Filters')</h4>
                                <span class="close-filter-btn"><i class="las la-times"></i></span>
                            </div>
                            <div class="responsive-filter-body">
                                <div class="responsive-filter-item">
                                    <label class="form-label">@lang('Transaction Number')</label>
                                    <input class="form-control form--control" name="search" type="search"
                                        value="{{ request()->search }}">
                                </div>
                                <div class="responsive-filter-item">
                                    <label class="form-label d-block">@lang('Type')</label>
                                    <select class="form-select form--control select2" name="trx_type"
                                        data-minimum-results-for-search="-1">
                                        <option value="">@lang('All')</option>
                                        <option value="+" @selected(request()->trx_type == '+')>@lang('Plus')</option>
                                        <option value="-" @selected(request()->trx_type == '-')>@lang('Minus')</option>
                                    </select>
                                </div>
                                <div class="responsive-filter-item">
                                    <label class="form-label d-block">@lang('Remark')</label>
                                    <select class="form-select form--control select2" name="remark"
                                        data-minimum-results-for-search="-1">
                                        <option value="">@lang('All')</option>
                                        @foreach ($remarks as $remark)
                                            <option value="{{ $remark->remark }}" @selected(request()->remark == $remark->remark)>
                                                {{ __(keyToTitle($remark->remark)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="responsive-filter-footer">
                                <button class="btn btn--sm btn--base">
                                    @lang('Apply')
                                </button>
                            </div>
                        </form>
                    </div>
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
                        @forelse($transactions as $trx)
                            <tr>
                                <td data-label="Trx">
                                    <strong>{{ $trx->trx }}</strong>
                                </td>

                                <td data-label="Transacted">
                                    {{ showDateTime($trx->created_at) }}<br>{{ diffForHumans($trx->created_at) }}
                                </td>

                                <td data-label="Amount">
                                    <span
                                        class="fw-bold @if ($trx->trx_type == '+') text--success @else text--danger @endif">
                                        {{ $trx->trx_type }} {{ showAmount($trx->amount) }}
                                    </span>
                                </td>

                                <td data-label="Post Balance">
                                    {{ showAmount($trx->post_balance) }}
                                </td>


                                <td data-label="Detail">{{ __($trx->details) }}</td>
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

                @if ($transactions->hasPages())
                    @php
                        echo paginateLinks($transactions);
                    @endphp
                @endif
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .select2-container--default .select2-selection--single .select2-selection__arrow:after {
            top: 0px;
        }

        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable:first-child {
            border-radius: 5px 5px 0 0 !important;
        }

        .select2-results {
            border-radius: 5px;
            overflow: hidden;
        }

        .btn--sm {
            padding: 8px 16px;
        }
    </style>
@endpush

@push('style-lib')
    <link href="{{ asset('assets/global/css/select2.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush

@push('script')
    <script>
        $('.showFilterBtn').on('click', function() {
            $('.responsive-filter-form').toggleClass('show');
        });
        $('.close-filter-btn').on('click', function() {
            $('.responsive-filter-form').removeClass('show');
        });
    </script>
@endpush
