@extends('admin.layouts.app')

@section('panel')
    <div class="row">

        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">

                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Title')</th>
                                    <th>@lang('Owner')</th>
                                    <th>@lang('Buyer')</th>
                                    <th>@lang('Number of Videos')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Purchased Date')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchasedPlaylists as $playlist)
                                    <tr>
                                        <td>{{ __($playlist->playlist->title) }}</td>
                                        <td>
                                            {{ __($playlist->owner?->fullname) }} <br>
                                            <a href="{{ route('admin.users.detail', $playlist->owner_id) }}">
                                                <span>@</span>{{ $playlist->owner?->username }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ __($playlist->user?->fullname) }} <br>
                                            <a href="{{ route('admin.users.detail', $playlist->user_id) }}">
                                                <span>@</span>{{ $playlist->user?->username }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ $playlist->playlist->videos->count() }}
                                        </td>
                                        <td>
                                            {{ $playlist->playlist->price > 0 ? showAmount($playlist->playlist->price) : '----' }}
                                        </td>
                                        <td>{{ showDateTime($playlist->created_at) }}<br>{{ diffForHumans($playlist->created_at) }}</td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.playlist.videos.list', $playlist->playlist->id) }}"
                                                    class="btn btn-sm btn-outline--info">
                                                    <i class="las la-video"></i>@lang('Video List')
                                                </a>
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
                @if ($purchasedPlaylists->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($purchasedPlaylists) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>


    </div>
@endsection



@push('breadcrumb-plugins')
        <x-search-form placeholder="Search Username" dateSearch='yes' />
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/daterangepicker.min.js') }}"></script>
@endpush

@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/daterangepicker.css') }}">
@endpush
