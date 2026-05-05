@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="content-top">
            <div class="left">
                <h4 class="title">{{ __($pageTitle) }}</h4>
            </div>
            <div class="right">
                <form class="search-form" action="#">
                    <div class="form-group">
                        <input class="form--control" id="search" name="search" type="text" value="{{ request()->search }}" placeholder="Search Here..." autocomplete="off">
                        <button class="search-form-btn" type="submit">
                            <i class="vti-search"></i>
                        </button>
                    </div>
                </form>
                <div class="create">
                    <button class="btn btn--base  create__btn campaignBtn">
                        <span class="icon">
                            <i class="fas fa-plus"></i>
                        </span>
                        <span class="text"> @lang('Create New') </span>
                    </button>
                </div>
            </div>
        </div>

        <div class="campaign-table mt-4">
            <table class="table--responsive--lg table">
                <thead>
                    <tr>
                        <th>@lang('Campaign Title')</th>
                        <th>@lang('Status')</th>
                        <th>@lang('Total Ads')</th>
                        <th>@lang('Total Amount')</th>
                        <th>@lang('Unpaid Amount')</th>
                        <th>@lang('Available Amount')</th>
                        <th>@lang('Payment Status')</th>
                        <th>@lang('Action')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td>
                                {{ __($campaign->title) }}
                            </td>
                            <td>
                                <div class="form--switch">
                                    <input class="form-check-input changeStatus" id="{{ $campaign->id . '_campaign' }}" data-campaign_id="{{ $campaign->id }}" type="checkbox" role="switch" @if ($campaign->status == Status::ENABLE) checked @endif>
                                    <br>

                                    @php
                                        echo $campaign->campaignStatus;
                                    @endphp
                                </div>
                            </td>
                            <td>
                                {{ $campaign->advertisements->count() }}
                            </td>
                            <td>
                                {{ showAmount($campaign->total_amount + $campaign->hold_amount) }}
                            </td>
                             <td>
                                {{ showAmount( $campaign->hold_amount) }}
                            </td>
                            <td>
                                {{ showAmount($campaign->available_amount) }}
                            </td>
                            <td>
                                @php
                                    echo $campaign->campaignPaymentStatus;
                                @endphp
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn pause-btn editBtn" data-campaign="{{ $campaign }}">@lang('Edit')</button>

                                    <a href="{{ route('user.advertiser.ad.create', $campaign->slug) }}" class="action-btn info-btn">@lang('Ads Set')</a>

                                    <a href="{{ route('user.advertiser.campaign.detail', $campaign->slug) }}" class="action-btn active-btn">@lang('Details')</a>
                                    @if ($campaign->payment_status != Status::PAYMENT_SUCCESS)
                                        <a href="{{ route('user.advertiser.campaign.gateways', $campaign->id) }}" class="action-btn success-btn">@lang('Pay Now')</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- NEW MODAL --}}
    <div class="modal custom--modal scale-style fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> @lang('Add New Campaign')</h5>
                    <button type="button" class="close modal-close-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form class="form-horizontal" method="post" action="{{ route('user.advertiser.campaign.save') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <div class="d-flex justify-content-between">
                                <label>@lang('Title')</label>
                                <a href="javascript:void(0)" class="buildSlug"><i class="las la-link"></i>@lang('Make Slug')</a>
                            </div>
                            <input type="text" class="form--control" value="{{ old('title') }}" name="title" required>
                        </div>

                        <div class="form-group ">
                            <div class="d-flex justify-content-between">
                                <label>@lang('Slug')</label>
                                <div class="slug-verification d-none"></div>
                            </div>
                            <input type="text" class="form--control" value="{{ old('slug') }}" name="slug" required>
                        </div>

                        <div class="form-group ">
                            <label class="form-label">@lang('Total Budget')</label>
                            <div class="input--group budget">
                                <input type="number" class="form--control" value="{{ old('total_budget') }}" name="total_budget" required>
                                <span class="input-text">{{ gs('cur_text') }}</span>
                            </div>
                        </div>

                        <div class="form-group addMoneyField mb-0 d-none">
                            <label class="form-label">@lang('Add Budget')</label>
                            <div class="input--group budget">
                                <input type="number" class="form--control" value="{{ old('add_budget') }}" name="add_budget">
                                <span class="input-text">{{ gs('cur_text') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--base w-100 h-45" id="btn-save" value="add">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection



@push('style')
    <style>
        .key-added {
            pointer-events: unset !important;
        }
    </style>
@endpush


@push('script')
    <script>
        (function($) {

            "use strict";
            $('.changeStatus').on('change', function() {
                var id = $(this).data('campaign_id');

                var url = "{{ route('user.advertiser.campaign.status') }}/" + id;
                var parent = $(this);

                $.ajax({
                    type: "POST",
                    url: url,
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response?.status == 'success') {
                            notify('success', response?.message);


                            if (response.campaign_status == 0) {
                                parent.siblings('span').removeClass('badge--success').addClass(
                                    'badge badge--danger');
                                parent.siblings('span').html('Disable');
                            } else {
                                parent.siblings('span').removeClass('badge--danger').addClass(
                                    'badge badge--success');
                                parent.siblings('span').html('Enable');
                            }
                            return;
                        }

                        parent.prop('checked', false);
                        notify('error', response?.message);
                    }

                });
            });



            $('.campaignBtn').on('click', function() {
                var modal = $('#createModal');
                const url = "{{ route('user.advertiser.campaign.save') }}"
                modal.find('form').attr('action', url);
                modal.find('.modal-title').text("@lang('Add New Campaign')");

                modal.find('[name="title"]').val('')
                modal.find('[name="slug"]').val('')
                modal.find('[name="total_budget"]').val('').prop('readonly', false);
                modal.find('.addMoneyField').addClass('d-none');

                modal.modal('show');
            });

            $('.editBtn').on('click', function() {
                var modal = $('#createModal');
                var data = $(this).data('campaign');
                var url = "{{ route('user.advertiser.campaign.save') }}/" + data.id;
                modal.find('form').attr('action', url);
                modal.find('.modal-title').text("@lang('Edit Category')");
                modal.find('[name="title"]').val(data.title);
                modal.find('[name="slug"]').val(data.slug);
                modal.find('[name="total_budget"]').val(parseFloat(data.total_amount).toFixed(0));
                if (data.payment_status == 1) {
                    modal.find('[name="total_budget"]').prop('readonly', true);
                    modal.find('.addMoneyField').removeClass('d-none');
                }



                modal.modal('show');
            });


            $('.buildSlug').on('click', function() {
                let closestForm = $(this).closest('form');
                let title = closestForm.find(`[name="title"]`).val();
                closestForm.find('[name=slug]').val(title);
                closestForm.find('[name=slug]').trigger('input');
            });



            $('[name=slug]').on('input', function() {
                let closestForm = $(this).closest('form');

                let slug = $(this).val();
                slug = slug.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');

                $(this).val(slug);
                if (slug) {
                    closestForm.find('.slug-verification').removeClass('d-none');
                    closestForm.find('.slug-verification').html(`
                            <small class="text--info"><i class="las la-spinner la-spin"></i> @lang('Verifying')</small>
                        `);
                    $.get("{{ route('user.advertiser.campaign.check.slug') }}", {
                        slug: slug
                    }, function(response) {
                        if (!response.exists) {
                            closestForm.find('.slug-verification').html(`
                                    <small class="text--success"><i class="las la-check"></i> @lang('Verified')</small>
                                `);
                        }
                        if (response.exists) {
                            closestForm.find('.slug-verification').html(`
                                    <small class="text--danger"><i class="las la-times"></i> @lang('Slug already exists')</small>
                                `);
                        }
                    });
                } else {
                    closestForm.find('.slug-verification').addClass('d-none');
                }
            });

        })(jQuery);
    </script>
@endpush
