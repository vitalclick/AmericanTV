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
                    <button class="btn btn--base btn--sm createBtn" type="button">
                        <span class="icon"><i class="las la-plus"></i></span>
                        <span class="text">@lang('Add New')</span>
                    </button>
                </form>


                <table class="table table--responsive--lg">
                    <thead>
                        <tr>
                            <th>@lang('Name')</th>
                            <th>@lang('Price')</th>
                            <th>@lang('Videos')</th>
                            <th>@lang('Playlist')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>

                        @forelse($plans as $plan)
                            <tr>
                                <td>
                                    <a href="{{ route('user.manage.plan.details', $plan->slug) }}"><span class="fw-bold text--base">
                                            {{ __(@$plan->name) }}</span></a>
                                </td>
                                <td>
                                    {{ showAmount($plan->price) }}
                                </td>
                                <td>
                                    <a href="{{ route('user.manage.plan.details', $plan->slug) }}?tab=videos">
                                        <span class="fw-bold count">{{ $plan->videos_count }}</span>
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('user.manage.plan.details', $plan->slug) }}?tab=playlists">
                                        <span class="fw-bold count">{{ $plan->playlists_count }}</span>
                                    </a>
                                </td>
                                <td>
                                    @php echo $plan->statusBadge @endphp
                                </td>
                                <td>
                                    <div class="dropdown action--dropdown">
                                        <button class="btn btn--sm btn--base dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="las la-cog"></i> @lang('Actions')
                                        </button>
                                        <ul class="dropdown-menu plan-dropdown">
                                            <li>
                                                <button class="dropdown-item editBtn" type="button"
                                                        data-plan="{{ $plan }}">
                                                    <i class="las la-pencil-alt me-1"></i> @lang('Edit')
                                                </button>
                                            </li>
                                            <li>
                                                <a href="{{ route('user.manage.plan.details', $plan->slug) }}"
                                                   class="dropdown-item">
                                                    <i class="las la-eye me-1"></i> @lang('Details')
                                                </a>
                                            </li>
                                            <li>
                                                @if (@$plan->status)
                                                    <a class="dropdown-item confirmationBtn" href="javascript:void(0)"
                                                       data-action="{{ route('user.manage.plan.status', $plan->id) }}"
                                                       data-question="@lang('Are you sure want to disable this plan?')">
                                                        <i class="las la-eye-slash me-1"></i> @lang('Disable')
                                                    </a>
                                                @else
                                                    <a class="dropdown-item confirmationBtn" href="javascript:void(0)"
                                                       data-action="{{ route('user.manage.plan.status', $plan->id) }}"
                                                       data-question="@lang('Are you sure want to enable this plan?')">
                                                        <i class="las la-eye me-1"></i> @lang('Enable')
                                                    </a>
                                                @endif
                                            </li>
                                            <li>
                                                <button class="dropdown-item addVideo" type="button"
                                                        data-action="{{ route('user.manage.plan.add.video', $plan->id) }}"
                                                        data-plan_id="{{ $plan->id }}"
                                                        >
                                                    <i class="las la-video me-1"></i> @lang('Add Videos')
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item addPlaylist" type="button"
                                                        data-action="{{ route('user.manage.plan.add.playlist', $plan->id) }}"
                                                        data-plan_id="{{ $plan->id }}"
                                                        data-selected='@json($plan->playlists->pluck('id'))'>
                                                    <i class="las la-list me-1"></i> @lang('Add Playlists')
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
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

    <x-confirmation-modal frontend="true" />
    @include('Template::user.plans.modal')
@endsection


@push('style')
    <style>
        .plan-dropdown {
            background: hsl(var(--black));
            padding: 0;
            border: 0 !important;
            border-radius: 6px;
            overflow: hidden;
        }

        .plan-dropdown .dropdown-item {
            padding: 5px 10px;
            color: hsl(var(--white));
        }

        .plan-dropdown .dropdown-item:hover {}

        .plan-dropdown .dropdown-item:hover {
            background: hsl(var(--base));
            color: hsl(var(--static-white));
            border: 0;
        }

        @media screen and (max-width: 424px) {
            .modal-close-btn {
                position: absolute;
                top: 10px;
                right: 10px;
            }

            .custom--modal .modal-header {
                padding-top: 40px;
            }
        }

        @media screen and (max-width: 367px) {
            .advertising-table__search {
                display: block !important;
            }

            .createBtn {
                margin-top: 10px !important;
            }
        }

        .count {
            font-size: 18px;
        }

        .table tbody tr td {
            font-size: 16px;
        }

        .table tbody tr td a {
            color: inherit;
        }

        .table tbody tr td a:hover {
            color: hsl(var(--base));
        }

        .action--dropdown .dropdown-item:hover,
        .action--dropdown .dropdown-item:focus,
        .action--dropdown .dropdown-item:active {
            background-color: hsl(var(--base));
            outline: none;
        }
    </style>
@endpush
