@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">

        <div class="wallet-setting d-flex flex-wrap justify-content-between">
            <div class="wallet-setting__buttons">
                <h6 class="wallet-setting__tagline">@lang('Available balance')</h6>
                <h2 class="wallet-setting__balance">
                    <sup>{{ __(gs('cur_text')) }}</sup>{{ showAmount($user->balance, currencyFormat: false) }}
                </h2>
                @if (@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)
                    @lang('You\'ve') <span class="text--success withdraw-detail__balance">
                        @if (@$user->withdrawSetting->amount > $user->balance)
                            {{ showAmount($user->balance) }}@else{{ showAmount(@$user->withdrawSetting->amount) }}
                        @endif
                    </span> @lang('for payout to your wallet.')
                @else
                    <p class="mt-2 withdraw-detail__desc">
                        @lang('Please, setup the payout method for withdrawals.')
                    </p>
                @endif

                @if (@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)
                    <h5 class="text-muted mt-3 withdraw-detail__desc">@lang('Next payout request will create') :
                        <span class="text--primary"
                              id="countdown">{{ showDateTime(@$user->withdrawSetting->next_withdraw_date, 'M d, Y') }}</span>
                    </h5>
                @endif
            </div>
            <div class="wallet-setting__wallet">
                <h5 class="title">@lang('My Payout Mathod')</h5>
                @if (@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE && @$user->withdrawSetting->withdrawMethod->image)
                    <div class="withdraw-method-image">
                        <img src="{{ getImage(getFilePath('withdrawMethod') . '/' . @$user->withdrawSetting->withdrawMethod->image, getFileSize('withdrawMethod')) }}"
                             alt="@lang('Image')">
                    </div>
                @else
                    <h6 class="mt-2 text-muted withdraw-detail__desc">@lang('You\'ve no payout method')</h6>
                @endif

                <div class="buttons">
                    <a class="btn btn--base" href="{{ route('user.withdraw') }}">
                        <span class="icon"><i class="vti-wallet"></i></span>
                        <span class="text">@lang('Set Payout')</span>
                    </a>
                </div>
            </div>
        </div>


        <div class="advertising-table mt-4">
            <div class="table--header">
                <h3 class="withdraw__title mb-0">@lang('Withdraw history')</h3>
            </div>
            <table class="table table--responsive--md">
                <thead>
                    <tr>
                        <th>@lang('Date')</th>
                        <th>@lang('Method')</th>
                        <th>@lang('Amount')</th>
                        <th>@lang('Conversion')</th>
                        <th>@lang('Status')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($withdraws as $withdraw)
                        <tr>
                            <td><span class="date">{{ showDateTime($withdraw->created_at, 'd F y') }}</span></td>
                            <td>
                                <span class="method-thumb"><img
                                         src="{{ getImage(getFilePath('withdrawMethod') . '/' . @$withdraw->method->image) }}"
                                         alt="@lang('image')"></span>
                            </td>
                            <td><span class="amount">
                                    {{ gs('cur_sym') }}{{ showAmount($withdraw->amount, currencyFormat: false) }}
                                </span></td>
                            <td class="text-center">
                                {{ showAmount(1) }} = {{ showAmount($withdraw->rate, currencyFormat: false) }}
                                {{ __($withdraw->currency) }}
                                <br>
                                <strong>{{ showAmount($withdraw->final_amount, currencyFormat: false) }}
                                    {{ __($withdraw->currency) }}</strong>
                            </td>
                            <td>
                                @php echo $withdraw->statusBadge @endphp
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td class="text-muted text-center empty-msg" colspan="100%">
                                <div class="empty-container empty-card-two">
                                    @include("Template::partials.empty")
                                </div>
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
            @if ($withdraws->hasPages())
                {{ paginateLinks($withdraws) }}
            @endif
        </div>

    </div>
@endsection


@push('script')
    <script>
        (function($) {
            'use strict';
            @if (@$user->withdrawSetting->withdrawMethod->status == Status::ENABLE)

                var countDownDate = new Date(
                    "{{ showDateTime(@$user->withdrawSetting->next_withdraw_date, 'M d, Y') }}").getTime();
                var x = setInterval(function() {
                    var now = new Date().getTime();
                    var distance = countDownDate - now;

                    var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    document.getElementById("countdown").innerHTML = days + "d " + hours + "h " + minutes +
                        "m " + seconds + "s ";

                    if (distance < 0) {
                        clearInterval(x);
                        document.getElementById("countdown").innerHTML = "Created";
                    }
                }, 1000);
            @endif

        })(jQuery)
    </script>
@endpush
