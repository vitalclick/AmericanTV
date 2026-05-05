@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="advertising-heading">
            <h3 class="advertising-heading__title">
                @lang('Payment History')
            </h3>
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
                        <th>@lang('Trx')</th>
                        <th>@lang('Transacted')</th>
                        <th>@lang('Amount')</th>
                        <th>@lang('Pay amount')</th>
                        <th>@lang('Payment Status')</th>

 
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $trx)
                        <tr>
                            <td>
                                <strong>{{ $trx->trx }}</strong>
                            </td>

                            <td>
                                <span>{{ showDateTime($trx->created_at) }}<br>{{ diffForHumans($trx->created_at) }}</span>
                            </td>

                            <td>
                                <div class="w-100">
                                    <span
                                          class="fw-bold @if ($trx->trx_type == '+') text--success @else text--danger @endif">
                                        {{ showAmount($trx->amount) }}
                                    </span><br>
                                    <small class="text--danger">@lang('Charge'): {{ showAmount($trx->charge) }} </span>
                                </div>
                            </td>
                            <td>
                                <span
                                      class="fw-bold @if ($trx->trx_type == '+') text--success @else text--danger @endif">
                                    {{ showAmount($trx->final_amount) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    echo $trx->statusBadge;
                                @endphp
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($payments->hasPages())
                @php echo paginateLinks($payments) @endphp
            @endif
        </div>
    </div>
@endsection


@push('style-lib')
    <link href="{{ asset('assets/global/css/select2.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush
