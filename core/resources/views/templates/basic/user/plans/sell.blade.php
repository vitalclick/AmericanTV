@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">

        <div class="col-md-12">
            <div class="advertising-table">
                <form class="advertising-table__search d-flex justify-content-between">
                    <div class="form-group">
                        <input class="form--control" name="search" value="{{ request()->search }}" type="text"
                            placeholder="Search Here...">
                        <button class="search-btn" type="submit"><i class="vti-search"></i></button>
                    </div>
                </form>

                <table class="table table--responsive--lg">
                    <thead>
                        <tr>
                            <th>@lang('Plan')</th>
                            <th>@lang('Price')</th>
                            <th>@lang('Videos')</th>
                            <th>@lang('Playlist')</th>
                            <th>@lang('Buyer')</th>
                            <th>@lang('TRX')</th>
                            <th>@lang('Transacted')</th>
                        </tr>
                    </thead>
                    <tbody>

                        @forelse($sellPlans as $plan)
                            <tr>
                                <td>
                                    <span class="fw-bold"> {{ __(@$plan->plan->name) }}</span>
                                </td>
                                <td>
                                    {{ showAmount($plan->amount) }}
                                </td>
                                <td>
                                    {{ $plan->plan->videos->count() }}
                                </td>
                                <td>
                                    {{ $plan->plan->playlists->count() }}
                                </td>
                                <td>
                                    {{ __($plan->user?->username) }}
                                </td>
                                <td>{{ $plan->trx }}</td>

                                <td>{{ showDateTime($plan->created_at) }}<br>{{ diffForHumans($plan->created_at) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted text-center empty-msg" colspan="100%">
                                    <div class="empty-container empty-card-two">
                                        @include('Template::partials.empty')
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($sellPlans->hasPages())
                    @php echo paginateLinks($sellPlans) @endphp
                @endif
            </div>
        </div>
    </div>
@endsection
