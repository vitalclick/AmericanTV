@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two custom-data-table">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('User')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Videos')</th>
                                    <th>@lang('Playlist')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($plans as $plan)
                                    <tr>
                                        <td>{{ __($plan->name) }}</td>
                                        <td>
                                            {{ __($plan->user?->fullname) }} <br>
                                            <a href="{{ route('admin.users.detail', $plan->user_id) }}">
                                                <span>@</span>{{ $plan->user?->username }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ showAmount($plan->price) }}
                                        </td>

                                        <td>
                                            {{ $plan->videos_count }}
                                        </td>
                                        <td>
                                            {{ $plan->playlists_count }}
                                        </td>

                                        <td>
                                            @php
                                                echo $plan->statusBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-outline--primary btn-sm dropdown-toggle" type="button"
                                                    id="actionDropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="false">
                                                    <i class="la la-cogs me-1"></i>@lang('Actions')
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0"
                                                    aria-labelledby="actionDropdown">
                                                    <li>
                                                        <button class="dropdown-item editBtn"
                                                            data-plan="{{ $plan }}"
                                                            data-action="{{ route('admin.plan.update', $plan->id) }}">
                                                            <i class="las la-pencil-alt me-1"></i> @lang('Edit')
                                                        </button>
                                                    </li>

                                                    <li>
                                                        @if (@$plan->status)
                                                            <a class="dropdown-item confirmationBtn"
                                                                href="javascript:void(0)"
                                                                data-action="{{ route('admin.plan.status', $plan->id) }}"
                                                                data-question="@lang('Are you sure want to disable this plan?')">
                                                                <i class="las la-eye-slash me-1"></i> @lang('Disable')
                                                            </a>
                                                        @else
                                                            <a class="dropdown-item confirmationBtn"
                                                                href="javascript:void(0)"
                                                                data-action="{{ route('admin.plan.status', $plan->id) }}"
                                                                data-question="@lang('Are you sure want to enable this plan?')">
                                                                <i class="las la-eye me-1"></i> @lang('Enable')
                                                            </a>
                                                        @endif
                                                    </li>

                                                    <li>
                                                        <a href="{{ route('admin.plan.videos.list', $plan->id) }}"
                                                            class="dropdown-item d-flex align-items-center py-2">
                                                            <i class="las la-video me-2"></i>@lang('Video List')
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('admin.plan.playlist.list', $plan->id) }}"
                                                            class="dropdown-item d-flex align-items-center py-2">
                                                            <i class="las la-list me-2"></i>@lang('Playlist List')
                                                        </a>
                                                    </li>
                                                </ul>
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
                @if ($plans->hasPages())
                    <div class="card-footer py-4">
                        @php echo paginateLinks($plans) @endphp
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>

    <div id="createModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <span type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </span>
                </div>
                <form method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form--label">@lang('Name')</label>
                            <input type="text" class="form-control" name="name" required
                                placeholder="@lang('Enter plan name')">
                        </div>
                        <div class="form-group">
                            <label class="form--label">@lang('Price')</label>
                            <div class="input-group">
                                <input class="form-control" name="price" type="number" placeholder="@lang('Enter Price')"
                                    step="any" required>
                                <span class="input-group-text btn--base border-0">{{ __(gs('cur_text')) }}</span>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Update')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder='Title/username' />
@endpush

@push('script')
    <script>
        (function($) {

            "use strict";

            $('.editBtn').on('click', function() {
                var modal = $('#createModal');
                var data = $(this).data('plan');

                const url = $(this).data('action');
                modal.find('form').attr('action', url);

                modal.find('form').attr('action', url);
                modal.find('.modal-title').text("@lang('Edit Plan')");
                modal.find('[name="name"]').val(data.name);
                modal.find('[name="price"]').val(parseFloat(data.price).toFixed(2));
                modal.modal('show');
            });

        })(jQuery);
    </script>
@endpush
