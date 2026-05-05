@extends('admin.layouts.app')
@section('panel')
<div class="row mb-none-30">
    <form method="POST">
        @csrf
        <div class="col-lg-12 col-md-12 mb-30">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group ">
                                <label> @lang('Site Title')</label>
                                <input class="form-control" type="text" name="site_name" required
                                    value="{{gs('site_name')}}">
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group ">
                                <label>@lang('Currency')</label>
                                <input class="form-control" type="text" name="cur_text" required
                                    value="{{gs('cur_text')}}">
                            </div>
                        </div>
                        <div class="col-xl-3 col-sm-6">
                            <div class="form-group ">
                                <label>@lang('Currency Symbol')</label>
                                <input class="form-control" type="text" name="cur_sym" required
                                    value="{{gs('cur_sym')}}">
                            </div>
                        </div>
                        <div class="form-group col-xl-3 col-sm-6">
                            <label> @lang('Timezone')</label>
                            <select class="select2 form-control" name="timezone">
                                @foreach($timezones as $key => $timezone)
                                <option value="{{ @$key}}" @selected(@$key==$currentTimezone)>{{ __($timezone) }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-xl-3 col-sm-6">
                            <label> @lang('Site Base Color')</label>
                            <div class="input-group">
                                <span class="input-group-text p-0 border-0">
                                    <input type='text' class="form-control colorPicker" value="{{gs('base_color')}}">
                                </span>
                                <input type="text" class="form-control colorCode" name="base_color"
                                    value="{{ gs('base_color') }}">
                            </div>
                        </div>
                        <div class="form-group col-xl-3 col-sm-6">
                            <label> @lang('Site Secondary Color')</label>
                            <div class="input-group">
                                <span class="input-group-text p-0 border-0">
                                    <input type='text' class="form-control colorPicker"
                                        value="{{gs('secondary_color')}}">
                                </span>
                                <input type="text" class="form-control colorCode" name="secondary_color"
                                    value="{{ gs('secondary_color') }}">
                            </div>
                        </div>
                        <div class="form-group col-xl-3 col-sm-6">
                            <label> @lang('Record to Display Per page')</label>
                            <select class="select2 form-control" name="paginate_number"
                                data-minimum-results-for-search="-1">
                                <option value="20" @selected(gs('paginate_number')==20 )>@lang('20 items per page')
                                </option>
                                <option value="50" @selected(gs('paginate_number')==50 )>@lang('50 items per page')
                                </option>
                                <option value="100" @selected(gs('paginate_number')==100 )>@lang('100 items per page')
                                </option>
                            </select>
                        </div>

                        <div class="form-group col-xl-3 col-sm-6 ">
                            <label> @lang('Currency Showing Format')</label>
                            <select class="select2 form-control" name="currency_format"
                                data-minimum-results-for-search="-1">
                                <option value="1" @selected(gs('currency_format')==Status::CUR_BOTH)>@lang('Show
                                    Currency Text and Symbol Both')</option>
                                <option value="2" @selected(gs('currency_format')==Status::CUR_TEXT)>@lang('Show
                                    Currency Text Only')</option>
                                <option value="3" @selected(gs('currency_format')==Status::CUR_SYM)>@lang('Show Currency
                                    Symbol Only')</option>
                            </select>
                        </div>
                        <div class="col-xl-12 col-sm-12">
                            <div class="form-group ">
                                <label>@lang('Google Api key')</label>
                                <input class="form-control" type="text" name="google_api_key" required
                                    value="{{gs('google_api_key')}}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mt-30">
                        <div class="card-header">
                            <h5>@lang('Monetization Settings')</h5>
                        </div>
                        <div class="card-body">

                            <div class="form-group ">
                                <label> @lang('Minimum Subscribe')</label>
                                <div class="input-group">
                                    <input class="form-control" type="text" name="minimum_subscribe" required
                                        value="{{gs('minimum_subscribe')}}">
                                    <span class="input-group-text"><i class="las la-bell"></i></span>
                                </div>
                            </div>

                            <div class="form-group ">
                                <label> @lang('Minimum Views')</label>
                                <div class="input-group">
                                    <input class="form-control" type="text" name="minimum_views" required
                                        value="{{gs('minimum_views')}}">
                                    <span class="input-group-text"><i class="las la-eye"></i></span>
                                </div>
                            </div>

                            <div class="form-group ">
                                <label> @lang('Pay for Paid Monetization')</label>
                                <div class="input-group">
                                    <input class="form-control" type="text" name="monetization_amount" required
                                        value="{{getAmount(gs('monetization_amount'))}}">
                                    <span class="input-group-text">{{__(gs('cur_text'))}}</span>
                                </div>
                            </div>
                            <div class="form-group ">
                                <label> @lang('Paid Monetization Status')</label>
                                
                                    <input type="checkbox" data-width="100%" data-size="large" data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-height="35" data-on="@lang('Enable')" data-off="@lang('Disable')" name="monetization_status" @if(gs('monetization_status')) checked @endif>
                        
                                
                            </div>


                        </div>
                    </div>

                

                </div>
                    <div class="col-md-6">
                        <div class="card mt-30">
                            <div class="card-header">
                                <h5>@lang('Violation Content Warning')</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group ">
                                    <label> @lang('Title')</label>
                                    <div class="input-group">
                                        <input class="form-control" type="text" name="title" required
                                            value="{{__(old('title',gs('vc_warning')?->title))}}">

                                    </div>
                                </div>
                                <div class="form-group ">
                                    <label> @lang('Description')</label>
                                    <textarea name="description" class="form-control" cols="20"
                                        rows="10">{{__(old('description',gs('vc_warning')?->description))}}</textarea>
                                </div>
                            </div>
                        </div>

                    </div>
               

            </div>
            <div class="form-group mt-3">
                <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
            </div>
    </form>
</div>
@endsection


@push('script-lib')
<script src="{{ asset('assets/admin/js/spectrum.js') }}"></script>
@endpush

@push('style-lib')
<link rel="stylesheet" href="{{ asset('assets/admin/css/spectrum.css') }}">
@endpush

@push('script')
<script>
    (function ($) {
        "use strict";


        $('.colorPicker').spectrum({
            color: $(this).data('color'),
            change: function (color) {
                $(this).parent().siblings('.colorCode').val(color.toHexString().replace(/^#?/, ''));
            }
        });

        $('.colorCode').on('input', function () {
            var clr = $(this).val();
            $(this).parents('.input-group').find('.colorPicker').spectrum({
                color: clr,
            });
        });
    })(jQuery);

</script>
@endpush
