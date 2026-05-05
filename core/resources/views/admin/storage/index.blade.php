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
                                    <th>@lang('Stroge Type')</th>
                                    <th>@lang('Available Space')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($storages as $k=>$storage)
                                    <tr>
                                        <td>
                                            {{ __($storage->name) }}
                                        </td>
                                        <td>
                                            @php
                                                echo $storage->storageType;
                                            @endphp
                                        </td>
                                        <td>
                                            {{ $storage->available_space }} <span>MB</span>
                                        </td>

                                        <td>
                                            @php
                                                echo $storage->statusBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">


                                                @php
                                                    $url = '#';
                                                    if (@$storage->type == Status::WASABI_SERVER) {
                                                        $url = route('admin.storage.wasabi.form', @$storage->id);
                                                    } elseif (@$storage->type == Status::DIGITAL_OCEAN_SERVER) {
                                                        $url = route('admin.storage.digital.ocean.form', @$storage->id);
                                                    } elseif (@$storage->type == Status::FTP_SERVER) {
                                                        $url = route('admin.storage.ftp.form', @$storage->id);
                                                    }
                                                @endphp

                                                <a href="{{ $url }}"
                                                    class="btn btn-sm btn-outline--primary editGatewayBtn">
                                                    <i class="la la-pencil"></i>@lang('Edit')
                                                </a>

                                                <button class="btn btn-sm btn-outline--success checkBtn"
                                                    data-storage={{ $storage }}
                                                    data-action={{ route('admin.storage.check.config', $storage->id) }}>
                                                    <i class="las la-check"></i>
                                                    @lang('Check')</button>

                                                @if ($storage->status == Status::DISABLE)
                                                    <button class="btn btn-sm btn-outline--success ms-1 confirmationBtn"
                                                        data-question="@lang('Are you sure to enable this storage?')"
                                                        data-action="{{ route('admin.storage.status', $storage->id) }}">
                                                        <i class="la la-eye"></i>@lang('Enable')
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm btn-outline--danger ms-1 confirmationBtn"
                                                        data-question="@lang('Are you sure to disable this storage?')"
                                                        data-action="{{ route('admin.storage.status', $storage->id) }}">
                                                        <i class="la la-eye-slash"></i>@lang('Disable')
                                                    </button>
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
            </div><!-- card end -->
        </div>
    </div>

    <x-confirmation-modal />
@endsection
@push('breadcrumb-plugins')
    <x-search-form />
    <div class="dropdown">
        <button class="btn btn-outline--primary dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            @lang('Add New')
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="{{ route('admin.storage.wasabi.form') }}">@lang('Wasabi')</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.storage.digital.ocean.form') }}">@lang('Digital Ocean')</a></li>
            <li><a class="dropdown-item" href="{{ route('admin.storage.ftp.form') }}">@lang('FTP')</a></li>
        </ul>
    </div>
@endpush



@push('script')
    <script>
        $('.checkBtn').on('click', function() {
            let storage = $(this).data('storage');
            let action = $(this).data('action');
            const btn = $(this);


            btn.prop('disabled', true);
            btn.html(`<i class="las la-spinner la-spin"></i> @lang('Checking...')`);

            $.ajax({
                type: "get",
                url: action,
                success: function(response) {
                    if (response.status === 'success') {
                        btn.html(`<i class="las la-check"></i> @lang('Connected')`);
                        btn.removeClass('btn-outline--danger').addClass('btn-outline--success');
                        notify('success', response.message);
                    } else {
                        btn.html(`<i class="las la-times"></i> @lang('Failed')`);
                        btn.removeClass('btn-outline--success').addClass('btn-outline--danger');
                        notify('error', response.message);
                    }
                    btn.prop('disabled', false);
                }

            });
        });
    </script>
@endpush
