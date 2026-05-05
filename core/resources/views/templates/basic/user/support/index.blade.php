@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="advertising-table">
            <div class="table--header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <form class="advertising-table__search flex-grow-1 p-0 border-0">
                    <div class="form-group mb-0">
                        <input class="form--control" name="search" type="text" value="{{ request()->search }}"
                               placeholder="Search Here...">
                        <button class="search-btn" type="submit"><i class="vti-search"></i></button>
                    </div>
                </form>

                <a class="btn ticket--btn" href="{{ route('ticket.open') }}">
                        <i class="far fa-list-alt"></i>
                    @lang('New Ticket')
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table--responsive--lg">
                    <thead>
                        <tr>
                            <th>@lang('Subject')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Priority')</th>
                            <th>@lang('Last Reply')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supports as $support)
                            <tr>
                                <td> <a class="fw-bold" href="{{ route('ticket.view', $support->ticket) }}">
                                        [@lang('Ticket')#{{ $support->ticket }}] {{ __($support->subject) }} </a></td>
                                <td>
                                    @php echo $support->statusBadge; @endphp
                                </td>
                                <td>
                                    @if ($support->priority == Status::PRIORITY_LOW)
                                        <span class="badge badge--dark">@lang('Low')</span>
                                    @elseif($support->priority == Status::PRIORITY_MEDIUM)
                                        <span class="badge  badge--warning">@lang('Medium')</span>
                                    @elseif($support->priority == Status::PRIORITY_HIGH)
                                        <span class="badge badge--danger">@lang('High')</span>
                                    @endif
                                </td>
                                <td>{{ diffForHumans($support->last_reply) }} </td>
                                <td>
                                    <a class="view-btn" href="{{ route('ticket.view', $support->ticket) }}">
                                        <i class="las la-desktop"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted text-center empty-msg" colspan="100%">
                                    <div class="empty-container empty-card-two">
                                        @include("Template::partials.empty")

                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($supports->hasPages())
                {{ paginateLinks($supports) }}
            @endif
        </div>
    </div>
@endsection
