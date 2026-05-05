@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">

        <div class="col-md-12">
            <div class="advertising-table">
                <form class="advertising-table__search">
                    <div class="form-group">
                        <input class="form--control" name="search" value="{{ request()->search }}" type="text" placeholder="Search Here...">
                        <button class="search-btn" type="submit"><i class="vti-search"></i></button>
                    </div>
                </form>

                <table class="table table--responsive--lg">
                    <thead>
                        <tr>
                            <th>@lang('Trx')</th>
                            <th>@lang('Transacted')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Playlist Title')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchasedPlaylists as $purchasedPlaylist)
                            <tr>
                                <td>
                                    <strong>{{ $purchasedPlaylist->trx }}</strong>
                                </td>

                                <td>
                                    {{ showDateTime($purchasedPlaylist->created_at) }}<br>{{ diffForHumans($purchasedPlaylist->created_at) }}
                                </td>

                                <td>
                                    {{ showAmount($purchasedPlaylist->amount) }}
                                </td>

                                <td>
                                    <strong>{{ __($purchasedPlaylist->playlist->title) }}</strong>
                                </td>


                                <td>
                                    <a href="{{ route('preview.playlist.videos', [$purchasedPlaylist->playlist->slug, $purchasedPlaylist->playlist->user->slug]) }}" target="__blank" class="view-btn">
                                        <i class="las la-play"></i>

                                    </a>
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

                @if ($purchasedPlaylists->hasPages())
                    @php echo paginateLinks($purchasedPlaylists) @endphp
                @endif
            </div>
        </div>

    </div>
@endsection

