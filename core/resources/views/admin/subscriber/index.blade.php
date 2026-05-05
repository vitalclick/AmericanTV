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
                                <th>@lang('User')</th>
                                <th>@lang('Channel Name')</th>
                                <th>@lang('Following User')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($subscribers as $subscriber)
                                <tr>
                                    <td>
                                        {{ $subscriber->followUser->fullname }} <br>
                                        <a href="{{route('admin.users.detail', $subscriber->followUser->id)}}"><span>@</span>{{ $subscriber->followUser->username }}</a>
                                    
                                    </td>
                                    <td>{{ $subscriber->followUser->channel_name }}</td>
                                    
                                    <td>{{ $subscriber->followingUser->fullname }} <br>
                                        <a href="{{route('admin.users.detail', $subscriber->followingUser->id)}}"><span>@</span>{{ $subscriber->followingUser->username }}</a>        
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
                @if ($subscribers->hasPages())
                <div class="card-footer py-4">
                    {{ paginateLinks($subscribers) }}
                </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>

    <x-confirmation-modal />
@endsection
@if($subscribers->count())
@push('breadcrumb-plugins')

<x-search-form placeholder="Username" />

@endpush
@endif
