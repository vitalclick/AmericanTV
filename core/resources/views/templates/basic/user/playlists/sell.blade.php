@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">

        <div class="col-md-12">
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
                            <th>@lang('Playlist Title')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Buyer')</th>
                            <th>@lang('Trx')</th>
                            <th>@lang('Transacted')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sellPlaylists as $playlist)
                            <tr>

                                <td>
                                    <strong>{{ __($playlist->playlist->title) }}</strong>
                                </td>
                                <td>
                                        {{ showAmount($playlist->amount) }}
                                </td>
                                <td>{{ __($playlist->user?->username) }}</td>

                                <td>
                                    <strong>{{ $playlist->trx }}</strong>
                                </td>

                                <td>
                                    {{ showDateTime($playlist->created_at) }}<br>{{ diffForHumans($playlist->created_at) }}
                                </td>
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

                @if ($sellPlaylists->hasPages())
                    @php echo paginateLinks($sellPlaylists) @endphp
                @endif
            </div>
        </div>

    </div>
@endsection


@push('style-lib')
    <link href="{{ asset('assets/global/css/select2.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush
