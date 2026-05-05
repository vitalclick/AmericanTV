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
                            <th>@lang('Channel')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>

                        @forelse($plans as $plan)
                            <tr>
                                <td>
                                    <span class="fw-bold"> {{ __(@$plan->plan->name) }}</span>
                                </td>
                                <td>
                                    {{ showAmount($plan->plan->price) }}
                                </td>
                                <td>
                                    {{ @$plan->plan->videos_count }}
                                </td>
                                <td>
                                    {{ @$plan->plan->playlists_count }}
                                </td>
                                <td>
                                    <a class="text--base"
                                       href="{{ route('preview.channel', @$plan->plan->user->slug) }}">{{ $plan->plan->user->channel_name }}</a>
                                </td>
                                <td>
                                    <a href="{{ getPlanVideoUrl($plan->plan) }}"
                                       target="__blank" class="view-btn">
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
                @if ($plans->hasPages())
                    @php echo paginateLinks($plans) @endphp
                @endif
            </div>
        </div>
    </div>
@endsection
