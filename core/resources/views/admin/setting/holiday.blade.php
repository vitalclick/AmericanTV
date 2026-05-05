@extends('admin.layouts.app')

@section('panel')
    <div class="card mb-5">
        <div class="card-header"><b class="lead">@lang('Weekly Holidays')</b></div>
        <form action="{{ route('admin.setting.offday') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="row">
                    @foreach (week() as $day)
            
                    
                        <div class="form-group col-lg-3 col-sm-6 col-md-4">
                            <label class="form-control-label">{{ __($day) }}</label>
                            <input name="off_days[{{ $day }}]" data-height="50" data-width="100%" data-size="large" data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('Holiday')" data-off="@lang('Withdraw day')" type="checkbox" @if (@gs()->off_days[$day]) checked @endif>
                        </div>
                    @endforeach

                        <div class="form-group mb-0 mt-3">
                            <button class="btn btn--primary w-100 h-45" type="submit">@lang('Submit')</button>
                        </div>
                
                </div>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th scope="col">@lang('SL')</th>
                                    <th scope="col">@lang('Title')</th>
                                    <th scope="col">@lang('Date')</th>
                                    <th scope="col">@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($holidays as $holiday)
                                    <tr>
                                        <td>{{ $holidays->firstItem() + $loop->index }}</td>
                                        <td>{{ __($holiday->title) }}</td>
                                        <td>{{ $holiday->day_off }}</td>
                                        <td>
                                            <button
                                                    class="btn btn-sm btn-outline--danger removeBtn" data-action="{{ route('admin.setting.remove', $holiday->id) }}">
                                                <i class="la la-trash"></i> @lang('Delete')
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">@lang('Data not found')</td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($holidays->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($holidays) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>

    <div class="modal fade" id="addHoliday">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Add Holiday')</h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.setting.holiday.submit') }}" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Title')</label>
                            <input class="form-control" name="title" type="text" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('Enter Date')</label>
                            <input class="form-control" name="date" type="date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--primary w-100 h-45" type="submit">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="removeModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="removeModalLabel">@lang('Remove Holiday')</h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <span aria-hidden="true"><i class="las la-times"></i></span>
                    </button>
                </div>
                <form action="" method="post">
                    @csrf
                    <div class="modal-body">
                        <p>@lang('Are you sure to remove this holiday?')</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--dark" data-bs-dismiss="modal" type="button">@lang('No')</button>
                        <button class="btn btn--primary" type="submit">@lang('Yes')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <button class="btn btn-outline--primary btn-sm" data-bs-toggle="modal" data-bs-target="#addHoliday">
        <i class="las la-plus"></i> @lang('Add New')
    </button>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            $('.removeBtn').on('click', function() {
                var modal = $('#removeModal');
                modal.find('form').attr('action', $(this).data('action'));
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
