@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">

        <div class="col-md-12">
            <div class="advertising-table">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 table--header">
                    <form class="advertising-table__search flex-grow-1 p-0 border-0">
                        <div class="form-group mb-0">
                            <input class="form--control" name="search" type="text" value="{{ request()->search }}"
                                placeholder="Search Here...">
                            <button class="search-btn" type="submit"><i class="vti-search"></i></button>
                        </div>
                    </form>

                    @if (!blank($notifications))
                        <div class="action--btn d-flex gap-3">
                            <button class="btn btn--sm btn--base confirmationBtn" data-question="@lang('Are you sure you want to remove all notifucation')?"
                                data-action="{{ route('user.notification.delete.all') }}">@lang('Remove All')</button>
                            <button class="btn btn--sm btn--success confirmationBtn" data-question="@lang('Are you sure you want to mark as read all notifucation')?"
                                data-action="{{ route('user.notification.read.all') }}">@lang('Mark all read')</button>
                        </div>
                    @endif
                </div>

                @forelse($notifications as $notification)
                    <div class="@if ($notification->is_read) notification-read-card @else notification-card @endif">
                        <div class="notification-card-wrapper">
                            <a class="d-block flex-grow-1" href="{{ route('user.notification.read', $notification->id) }}">
                                <h6 class="notification-card-title">{{ $notification->title }}</h6>
                            </a>

                            <button class="action-btn notification-btn confirmationBtn" data-question="@lang('Are you sure you want to remove this notifucation')?"
                                data-action="{{ route('user.notification.delete', $notification->id) }}"><i
                                    class="las la-trash"></i></button>
                        </div>
                    </div>

                @empty
                    <div class="empty-container empty-card-two">
                        @include("Template::partials.empty")
                    </div>
                @endforelse

                @if ($notifications->hasPages())
                    @php echo paginateLinks($notifications) @endphp
                @endif
            </div>
        </div>

    </div>

    <x-confirmation-modal frontend="true" />
@endsection
