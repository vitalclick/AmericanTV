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
                            <th>@lang('Video Title')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchasedVideos as $purchasedVideo)
                            <tr>
                                <td>
                                    <strong>{{ $purchasedVideo->trx }}</strong>
                                </td>

                                <td>
                                    {{ showDateTime($purchasedVideo->created_at) }}<br>{{ diffForHumans($purchasedVideo->created_at) }}
                                </td>

                                <td>
                                    {{ showAmount($purchasedVideo->amount) }}
                                </td>

                                <td>
                                    {{ __($purchasedVideo->video->title) }}
                                </td>


                                <td>
                                    <a href="{{ route('video.play', [$purchasedVideo->video->id, $purchasedVideo->video->slug]) }}" target="__blank" class="view-btn">
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

                @if ($purchasedVideos->hasPages())
                    @php echo paginateLinks($purchasedVideos) @endphp
                @endif
            </div>
        </div>

    </div>
@endsection
