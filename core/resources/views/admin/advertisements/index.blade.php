@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Title')</th>
                                    <th>@lang('User')</th>
                                    <th>@lang('url')</th>
                                    <th>@lang('Type')</th>
                                    <th>@lang('Impression')</th>
                                    <th>@lang('Click')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($advertisements as $advertisement)
                                    <tr>
                                        <td>{{ __($advertisement->title) }}</td>
                                        <td>
                                            {{ __($advertisement->user->fullname) }}
                                            <br>
                                            <a href="{{ route('admin.users.detail', $advertisement->user_id) }}"><span>@</span>{{ $advertisement->user->username }}</a>
                                        </td>
                                        <td>
                                            @if ($advertisement->url)
                                                <a href="{{ $advertisement->url }}" title="{{ $advertisement->url }}" target="__blank"><i class="las la-link"></i>@lang('Url')</a>
                                            @else
                                                <span class="text-muted">--</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                echo $advertisement->adTypeBadge;
                                            @endphp
                                        </td>
                                        <td>{{ formatNumber($advertisement->impression) }} <br>
                                            <small>@lang('Available'):{{ formatNumber($advertisement->available_impression) }} </small>
                                        </td>
                                        <td>{{ formatNumber($advertisement->click) }} <br>
                                            <small>@lang('Available'):{{ formatNumber($advertisement->available_click) }} </small>
                                        </td>
                                        <td>{{ showAmount($advertisement->total_amount) }}</td>
                                        <td>
                                            @php
                                                echo $advertisement->statusBadge;
                                            @endphp
                                        </td>

                                        <td>
                                            <div class="button--group">
                                                <a class="btn btn-sm btn-outline--primary" href="{{route('admin.advertisement.edit', $advertisement->id)}}"><i class="las la-pencil-alt"></i>@lang('Edit')</a>
                                            
                                                @if ($advertisement->status == Status::RUNNING)
                                                    <button class="btn btn-sm btn-outline--danger confirmationBtn" @if($advertisement->payment_status != Status::PAYMENT_SUCCESS) disabled  @endif data-action="{{ route('admin.advertisement.status', $advertisement->id) }}" data-question="@lang('Are you sure want to pause this advetisement.')?"><i class="las la-play"></i>@lang('Pause')</button>
                                                @elseif($advertisement->status == Status::PAUSE)
                                                    <button class="btn btn-sm btn-outline--success confirmationBtn" @if($advertisement->payment_status != Status::PAYMENT_SUCCESS) disabled  @endif data-action="{{ route('admin.advertisement.status', $advertisement->id) }}" data-question="@lang('Are you sure want to run this advetisement.')?"><i class="las la-pause"></i>@lang('Running')</button>
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
                @if ($advertisements->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($advertisements) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <x-confirmation-modal />
@endsection


@push('breadcrumb-plugins')
    <x-search-form placeholder="Username / Title" />
@endpush
