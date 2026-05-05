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
                                    <th>@lang('Name')</th>
                                    <th>@lang('Owner')</th>
                                    <th>@lang('Buyer')</th>
                                    <th>@lang('Videos')</th>
                                    <th>@lang('Playlists')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Purchased Date')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchasedPlans as $plan)
                                    <tr>
                                        <td>{{ __(@$plan->plan->name) }}</td>
                                        <td>
                                            {{ __($plan->owner?->fullname) }} <br>
                                            <a href="{{ route('admin.users.detail', $plan->owner_id) }}">
                                                <span>@</span>{{ $plan->owner?->username }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ __($plan->user?->fullname) }} <br>
                                            <a href="{{ route('admin.users.detail', $plan->user_id) }}">
                                                <span>@</span>{{ $plan->user?->username }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ @$plan->plan->videos->count() }}
                                        </td>
                                        <td>
                                            {{ @$plan->plan->playlists->count() }}
                                        </td>
                                        <td>
                                            {{ @$plan->plan->price > 0 ? showAmount(@$plan->plan->price) : '----' }}
                                        </td>
                                        <td>{{ showDateTime($plan->created_at) }}<br>{{ diffForHumans($plan->created_at) }}</td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.plan.videos.list', $plan->plan_id) }}"
                                                    class="btn btn-sm btn-outline--info">
                                                    <i class="las la-video me-2"></i>@lang('Video List')
                                                </a>

                                                <a href="{{ route('admin.plan.playlist.list', $plan->plan_id) }}"
                                                    class="btn btn-sm btn-outline--primary">
                                                    <i class="las la-list me-2"></i>@lang('Playlist List')
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
                @if ($purchasedPlans->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($purchasedPlans) }}
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
