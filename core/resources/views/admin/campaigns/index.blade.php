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
                                    <th>@lang('Title')</th>
                                    <th>@lang('Slug')</th>

                                    <th>@lang('Total Amount')</th>
                                    <th>@lang('Available Amount')</th>
                                    <th>@lang('Payment Status')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($campaigns as $campaign)
                                    <tr>
                                        <td>{{ __($campaign->title) }}</td>
                                        <td>{{ __($campaign->slug) }}</td>
                                        <td>
                                            {{ showAmount($campaign->total_amount ) }}
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
                                            @php
                                                echo $campaign->campaignStatus;
                                            @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.campaign.detail', $campaign->id) }}"
                                                    class="btn btn-sm btn-outline--primary"><i
                                                        class="las la-desktop"></i>@lang('Detail')</a>
                                                @if (@$campaign->status)
                                                    <button class="btn btn-sm btn-outline--danger confirmationBtn"
                                                        data-action="{{ route('admin.campaign.status', $campaign->id) }}"
                                                        data-question="@lang('Are you sure want to disable this campaign?')"><i
                                                            class="las la-eye-slash"></i>@lang('Disable')</button>
                                                @else
                                                    <button class="btn btn-sm btn-outline--success confirmationBtn"
                                                        data-action="{{ route('admin.campaign.status', $campaign->id) }}"
                                                        data-question="@lang('Are you sure want to enable this campaign?')"><i
                                                            class="las la-eye"></i>@lang('Enable')</button>
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
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($campaigns->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($campaigns) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>



    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder='Name' />
@endpush
