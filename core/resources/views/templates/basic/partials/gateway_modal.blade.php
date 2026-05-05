<div class="modal custom--modal payment-modal scale-style fade" id="paymentConfirmationModal" aria-labelledby="playlistModalLabel"
    aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content ">
            <div class="modal-header">
                <h5 class="modal-title">@lang('You need to purchase this playlist to unlock its content.')</h5>

                <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form class="deposit-form" action="{{ route('user.deposit.insert') }}" method="post">
                @csrf
                <input name="currency" type="hidden">
                <input name="playlist_id" type="hidden">
                <div class="gateway-card">
                    <div class="row justify-content-center gy-sm-4 gy-3">

                        <div class="col-lg-6">
                            <div class="payment-system-list is-scrollable gateway-option-list">
                                @foreach ($gatewayCurrency as $data)
                                    <label
                                        class="payment-item @if ($loop->index > 4) d-none @endif gateway-option"
                                        for="{{ titleToKey($data->name) }}">
                                        <div class="payment-item-left">
                                            <div class="payment-item__thumb">
                                                <img class="payment-item__thumb-img"
                                                    src="{{ getImage(getFilePath('gateway') . '/' . $data->method->image) }}"
                                                    alt="@lang('payment-thumb')">
                                            </div>
                                            <span class="payment-item__name">{{ __($data->name) }}</span>
                                        </div>

                                        <span class="check-type-icon">
                                            <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor"
                                                    stroke-linecap="round"></path>
                                            </svg>
                                        </span>

                                        <input class="payment-item__radio gateway-input"
                                            id="{{ titleToKey($data->name) }}" name="gateway"
                                            data-gateway='@json($data)'
                                            data-min-amount="{{ showAmount($data->min_amount) }}"
                                            data-max-amount="{{ showAmount($data->max_amount) }}" type="radio"
                                            value="{{ $data->method_code }}" hidden
                                            @if (old('gateway')) @checked(old('gateway') == $data->method_code) @else @checked($loop->first) @endif>
                                    </label>
                                @endforeach
                                @if ($gatewayCurrency->count() > 4)
                                    <button class="payment-item__btn more-gateway-option" type="button">
                                        <p class="payment-item__btn-text">@lang('Show All Payment Options')</p>
                                        <span class="payment-item__btn__icon"><i
                                                class="fas fa-chevron-down"></i></i></span>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <input class="form-control form--control amount" name="amount" type="hidden"
                                placeholder="@lang('00.00')" autocomplete="off">
                            <div class="payment-system-list border-style">
                                <div class="deposit-info">
                                    <div class="deposit-info__title">
                                        <p class="text mb-0">@lang('Item')</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text"><span class="item-name"></span>
                                        </p>
                                    </div>
                                </div>
                                <hr>
                                <div class="deposit-info">
                                    <div class="deposit-info__title">
                                        <p class="text mb-0">@lang('Amount')</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text"><span class="item-price">00 {{ gs('cur_text') }}</span>
                                        </p>
                                    </div>
                                </div>
                                <hr>

                                <div class="deposit-info">
                                    <div class="deposit-info__title">
                                        <p class="text has-icon">@lang('Processing Charge')
                                            <span class="proccessing-fee-info" data-bs-toggle="tooltip"
                                                title="@lang('Processing charge for payment gateways')"><i class="las la-info-circle"></i>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text"><span class="processing-fee">@lang('0.00')</span>
                                            {{ __(gs('cur_text')) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="deposit-info total-amount pt-3">
                                    <div class="deposit-info__title">
                                        <p class="text">@lang('Total')</p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text"><span class="final-amount">@lang('0.00')</span>
                                            {{ __(gs('cur_text')) }}</p>
                                    </div>
                                </div>

                                <div class="deposit-info gateway-conversion d-none total-amount pt-2">
                                    <div class="deposit-info__title">
                                        <p class="text">@lang('Conversion')
                                        </p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text"></p>
                                    </div>
                                </div>
                                <div class="deposit-info conversion-currency d-none total-amount pt-2">
                                    <div class="deposit-info__title">
                                        <p class="text">
                                            @lang('In') <span class="gateway-currency"></span>
                                        </p>
                                    </div>
                                    <div class="deposit-info__input">
                                        <p class="text">
                                            <span class="in-currency"></span>
                                        </p>

                                    </div>
                                </div>
                                <div class="d-none crypto-message mb-3">
                                    <div class="note-text">
                                        <span class="icon"><i class="fas fa-info-circle"></i></span>
                                        <p>
                                            @lang('Conversion with') <span class="gateway-currency"></span>
                                            @lang('and final value will Show on next step')
                                        </p>
                                    </div>
                                </div>
                                <button class="btn btn--base w-100" type="submit" disabled>
                                    @lang('Payment Confirm')
                                </button>
                                <div class="info-text pt-3">
                                    <p class="text note-text">
                                        <span class="icon"><i class="fas fa-info-circle"></i></span>
                                        @lang('Ensuring your funds grow safely through our secure payment process with world-class payment options.')
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- login modal --}}
@include($activeTemplate . 'partials.login_alert_modal')

@push('style')
    <style>
        .purchase-now {
            cursor: !important;
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            const auth = "{{ auth()->user() }}";
            if (!auth) {
                $(document).on('click', '.purchase-now', function(e) {


                    $('#existModalCenter').modal('show');
                });

            } else {

                $(document).on('click', '.purchase-now', function(e) {
                    e.preventDefault();
                    const modal = $('#paymentConfirmationModal');
                    const playlist = $(this).data('resource');

                    modal.find('input[name="playlist_id"]').val(playlist.id);
                    modal.find('input[name="amount"]').val(parseFloat(playlist.price).toFixed(2)).trigger(
                        'input');

                    modal.find('.item-name').text(playlist.title);
                    modal.find('.item-price').text(
                        `${parseFloat(playlist.price).toFixed(2)} {{ gs('cur_text') }}`);

                    modal.modal('show');
                });


                var amount = parseFloat($('.amount').val() || 0);

                var gateway, minAmount, maxAmount;

                $('.amount').on('input', function(e) {
                    amount = parseFloat($(this).val());
                    if (!amount) {
                        amount = 0;
                    }
                    calculation();
                });

                $('.gateway-input').on('change', function(e) {
                    gatewayChange();
                });

                function gatewayChange() {
                    let gatewayElement = $('.gateway-input:checked');
                    let methodCode = gatewayElement.val();

                    gateway = gatewayElement.data('gateway');
                    minAmount = gatewayElement.data('min-amount');
                    maxAmount = gatewayElement.data('max-amount');

                    let processingFeeInfo =
                        `${parseFloat(gateway.percent_charge).toFixed(2)}% with ${parseFloat(gateway.fixed_charge).toFixed(2)} {{ __(gs('cur_text')) }} charge for payment gateway processing fees`
                    $(".proccessing-fee-info").attr("data-bs-original-title", processingFeeInfo);
                    calculation();
                }

                gatewayChange();

                $(".more-gateway-option").on("click", function(e) {
                    let paymentList = $(".gateway-option-list");
                    paymentList.find(".gateway-option").removeClass("d-none");
                    $(this).addClass('d-none');
                    paymentList.animate({
                        scrollTop: (paymentList.height() - 60)
                    }, 'slow');
                });

                function calculation() {
                    if (!gateway) return;
                    $(".gateway-limit").text(minAmount + " - " + maxAmount);

                    let percentCharge = 0;
                    let fixedCharge = 0;
                    let totalPercentCharge = 0;

                    if (amount) {
                        percentCharge = parseFloat(gateway.percent_charge);
                        fixedCharge = parseFloat(gateway.fixed_charge);
                        totalPercentCharge = parseFloat(amount / 100 * percentCharge);
                    }

                    let totalCharge = parseFloat(totalPercentCharge + fixedCharge);
                    let totalAmount = parseFloat((amount || 0) + totalPercentCharge + fixedCharge);

                    $(".final-amount").text(totalAmount.toFixed(2));
                    $(".processing-fee").text(totalCharge.toFixed(2));
                    $("input[name=currency]").val(gateway.currency);
                    $(".gateway-currency").text(gateway.currency);

                    if (amount < Number(gateway.min_amount) || amount > Number(gateway.max_amount)) {
                        $(".deposit-form button[type=submit]").attr('disabled', true);
                    } else {
                        $(".deposit-form button[type=submit]").removeAttr('disabled');
                    }

                    if (gateway.currency != "{{ gs('cur_text') }}" && gateway.method.crypto != 1) {
                        $('.deposit-form').addClass('adjust-height')

                        $(".gateway-conversion, .conversion-currency").removeClass('d-none');
                        $(".gateway-conversion").find('.deposit-info__input .text').html(
                            `1 {{ __(gs('cur_text')) }} = <span class="rate">${parseFloat(gateway.rate).toFixed(2)}</span>  <span class="method_currency">${gateway.currency}</span>`
                        );
                        $('.in-currency').text(parseFloat(totalAmount * gateway.rate).toFixed(gateway.method.crypto ==
                            1 ?
                            8 : 2))
                    } else {
                        $(".gateway-conversion, .conversion-currency").addClass('d-none');
                        $('.deposit-form').removeClass('adjust-height')
                    }

                    if (gateway.method.crypto == 1) {
                        $('.crypto-message').removeClass('d-none');
                    } else {
                        $('.crypto-message').addClass('d-none');
                    }
                }

                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl)
                })
                $('.gateway-input').change();

            }

        })(jQuery);
    </script>
@endpush
