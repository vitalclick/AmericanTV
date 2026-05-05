@extends($activeTemplate . 'layouts.master')

@section('content')
    @php
        $content = getContent('withdraw_method_page.content', true);

    @endphp
    <div class="dashboard-content">
        <form action="{{ route('user.withdraw.method.submit') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row justify-content-center gy-4">

                <div class="col-12">
                    <div class="alert alert--info" role="alert">
                        <div class="alert__icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <p class="alert__message">
                            <span class="fw-bold d-block mb-1">{{ __($content->data_values->title) }}</span>
                            {{ __($content->data_values->subtitle) }}
                        </p>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card custom--card">
                        <div class="card-header">
                            <h5 class="card-title">@lang('Setup Withdraw Method')</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group select2-parent">
                                        <label class="form-label">@lang('Method')</label>
                                        <select class="form-select form--control form-control select2-basic"
                                                name="method_code" required>
                                            <option value="">@lang('Select One')</option>
                                            @foreach ($withdrawMethod as $data)
                                                <option value="{{ $data->id }}" data-resource="{{ $data }}"
                                                        data-form='<x-withdraw-form identifier="id" identifierValue="{{ $data->form_id }}"/>'
                                                        {{ $data->id == @$user->withdrawSetting->withdraw_method_id ? 'selected' : null }}>
                                                    {{ __($data->name) }} ({{ showAmount($data->min_limit) }} -
                                                    {{ showAmount($data->max_limit) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <p>
                                            <small class="fst-italic">
                                                <i class="las la-info-circle"></i>
                                                @lang('Withdraw Time'): <span class="schedule_type capitalize"></span> <span
                                                      class="schedule"></span>
                                            </small>
                                        </p>
                                        <p class="d-none rate-element"></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">@lang('Amount')</label>
                                        <div class="input-group">
                                            <input type="number" step="any" name="amount"
                                                   value="{{ getAmount(@$user->withdrawSetting->amount) }}"
                                                   class="form-control form--control form-control" required>
                                            <span
                                                  class="input-group-text border-0 text-white bg--base">{{ __(gs('cur_text')) }}</span>
                                        </div>
                                        <p>
                                            <small class="fst-italic">
                                                <i class="las la-info-circle"></i>
                                                @lang('Withdraw Charge'): {{ gs('cur_sym') }}<span class="charge">0</span> |
                                                @lang('Receivable Amount'): {{ gs('cur_sym') }}<span class="receivable">0</span>
                                            </small>
                                        </p>
                                        <p class="in-site-cur d-none">
                                            <small class="fst-italic"><i class="las la-info-circle"></i> @lang('in')
                                                <span class="base-currency"></span> <span class="final_amo">0</span> <span
                                                      class="base-currency"></span></small>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="withdraw_form"></div>
                                    <button type="submit" class="btn btn--base w-100 mt-3">@lang('Submit')</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection



@push('style-lib')
    <link href="{{ asset('assets/global/css/select2.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush



@push('script')
    <script>
        (function($) {
            "use strict";

            $('select[name=method_code]').on('change', function() {
                if (!$('select[name=method_code]').val()) {
                    $('.charge').text(0);
                    $('.receivable').text(0);
                    $('.schedule_type').text('');
                    $('.schedule').text('');
                    $('.in-site-cur').addClass('d-none');
                    $('.rate-element').addClass('d-none');
                    return false;
                }

                var resource = $('select[name=method_code] option:selected').data('resource');


                var form = $('select[name=method_code] option:selected').data('form');


                var fixed_charge = parseFloat(resource.fixed_charge);
                var percent_charge = parseFloat(resource.percent_charge);
                var rate = parseFloat(resource.rate)
                var toFixedDigit = 2;

                $('.min').text(parseFloat(resource.min_limit).toFixed(2));
                $('.max').text(parseFloat(resource.max_limit).toFixed(2));
                var amount = parseFloat($('input[name=amount]').val());

                if (!amount) {
                    amount = 0;

                }

                $('.preview-details').removeClass('d-none');

                var charge = parseFloat(fixed_charge + (amount * percent_charge / 100)).toFixed(2);
                $('.charge').text(charge);

                if (resource.currency != '{{ __(gs('cur_text')) }}') {
                    var rateElement =
                        `<small class="fst-italic"><i class="las la-info-circle"></i><span>@lang('Conversion Rate'):</span> <span>1 {{ __(gs('cur_text')) }} = <span class="rate">${rate}</span>  <span class="base-currency">${resource.currency}</span></span></small>`;
                    $('.rate-element').html(rateElement);
                    $('.rate-element').removeClass('d-none');
                    $('.in-site-cur').removeClass('d-none');
                    $('.rate-element').addClass('d-flex');
                    $('.in-site-cur').addClass('d-flex');
                } else {
                    $('.rate-element').html('')
                    $('.rate-element').addClass('d-none');
                    $('.in-site-cur').addClass('d-none');
                    $('.rate-element').removeClass('d-flex');
                    $('.in-site-cur').removeClass('d-flex');
                }

                var receivable = parseFloat((parseFloat(amount) - parseFloat(charge))).toFixed(2);
                $('.receivable').text(receivable);
                var final_amo = parseFloat(parseFloat(receivable) * rate).toFixed(toFixedDigit);

                $('.final_amo').text(final_amo);
                $('.base-currency').text(resource.currency);
                $('.method_currency').text(resource.currency);

                $('.schedule_type').text(resource.schedule_type);

                if (resource.schedule_type == 'daily') {
                    $('.schedule').text('');
                } else {
                    $('.schedule').text(' - ' + resource.showSchedule);
                }

                $('.withdraw_form').html(form);


                $('input[name=amount]').on('input');
            }).change();

            $('input[name=amount]').on('input', function() {
                var data = $('select[name=method_code]').change();
                $('.amount').text(parseFloat($(this).val()).toFixed(2));
            });
        })(jQuery);
    </script>
@endpush
