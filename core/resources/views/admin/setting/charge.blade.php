@extends('admin.layouts.app')
@section('panel')
    <div class="row mb-none-30">
        <form method="POST">
            @csrf
            <div class="col-lg-12 col-md-12">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group ">
                                    <label>@lang('Video Sell Charge')</label> <span title="@lang('Set the percentage to charge from video sales. Set to 0 for no charge.')"><i class="las la-info-circle"></i></span>
                                    <div class="input-group">
                                        <input class="form-control" name="video_sell_charge" type="number" step="any" value="{{ getAmount(gs('video_sell_charge')) }}" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group ">
                                    <label>@lang('Playlist Sell Charge')</label> <span title="@lang('Set the percentage to charge from playlists sales. Set to 0 for no charge.')"><i class="las la-info-circle"></i></span>
                                    <div class="input-group">
                                        <input class="form-control" name="playlist_sell_charge" type="number" step="any" value="{{ getAmount(gs('playlist_sell_charge')) }}" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group ">
                                    <label>@lang('Monthly Plan Sell Charge')</label> <span title="@lang('Set the percentage to charge from monthly subscription. Set to 0 for no charge.')"><i class="las la-info-circle"></i></span>
                                    <div class="input-group">
                                        <input class="form-control" name="plan_sell_charge" type="number" step="any" value="{{ getAmount(gs('plan_sell_charge')) }}" required>
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group mt-3">
                    <button class="btn btn--primary w-100 h-45" type="submit">@lang('Submit')</button>
                </div>
        </form>
    </div>
@endsection


@push('script-lib')
    <script src="{{ asset('assets/admin/js/spectrum.js') }}"></script>
@endpush

@push('style-lib')
    <link href="{{ asset('assets/admin/css/spectrum.css') }}" rel="stylesheet">
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";


            $('.colorPicker').spectrum({
                color: $(this).data('color'),
                change: function(color) {
                    $(this).parent().siblings('.colorCode').val(color.toHexString().replace(/^#?/, ''));
                }
            });

            $('.colorCode').on('input', function() {
                var clr = $(this).val();
                $(this).parents('.input-group').find('.colorPicker').spectrum({
                    color: clr,
                });
            });
        })(jQuery);
    </script>
@endpush
