@extends($activeTemplate . 'layouts.master')
@section('content')
    @php
        $content = getContent('monetization_page.content', true);

    @endphp
    <div class="setting-content">
        <h3 class="setting-content__title mb-0">{{ __($content->data_values->title) }}</h3>
        <span class="subtitle">{{ __($content->data_values->subtitle) }}</span>
        <div class="monetization-card">
            <h3 class="monetization-card__title">{{ __($content->data_values->card_title) }}</h3>
            @if (!$user->profile_complete)
                <span class="monetization-card__tagline">{{ __($content->data_values->card_tagline) }}</span>
                <a href="{{ route('user.channel.create') }}" class="btn btn--base">@lang('Create Channel')</a>
            @elseif($totalSubscriber >= gs('minimum_subscribe') && $totalViews >= gs('minimum_views') && !$user->monetization_status)
                <span class="monetization-card__tagline">{{ __($content->data_values->congratulation_message) }}</span>
                <a href="{{ route('user.monetization.apply') }}" class="btn btn--base">@lang('Apply')</a>
            @elseif($user->monetization_status == Status::MONETIZATION_APPLYING)
                <span class="monetization-card__tagline">{{ __($content->data_values->review_message) }}</span>
            @elseif($user->monetization_status == Status::MONETIZATION_APPROVED)
                <span class="monetization-card__tagline">{{ __($content->data_values->active_message) }}</span>
            @elseif($user->monetization_status == Status::MONETIZATION_CANCEL)
                <span class="monetization-card__tagline">{{ __($content->data_values->rejected_message) }}</span>
                <a href="{{ route('user.monetization.apply') }}" class="btn btn--base">@lang('Apply Again')</a>
            @endif

            <img class="img"
                src="{{ frontendImage('monetization_page', $content->data_values->first_image, '202x137') }}"
                alt="image">
        </div>


        <div class="monetization-progress">
            <h5 class="title">@lang('Meet the conditions for application') <span class="icon"><i class="vti-info"></i></span></h5>
            <div class="progress-wrap">
                <div class="progress-item">
                    <div class="progress" data-percent="{{ $subscriberInPercent }}%">
                        <div class="progressbar"></div>
                    </div>
                    <h6 class="progress-item__caption">{{ $totalSubscriber }} @lang('Subscribers')</h6>
                    <span class="progress-item__number">{{ formatNumber(gs('minimum_subscribe')) }}</span>
                </div>

                <div class="progress-item">
                    <div class="progress" data-percent="{{ $viewInPercent }}%">
                        <div class="progressbar"></div>
                    </div>
                    <h6 class="progress-item__caption">{{ $totalViews }} @lang('views')</h6>
                    <span class="progress-item__number">{{ formatNumber(gs('minimum_views')) }}</span>
                </div>
            </div>
        </div>


        @if (gs('monetization_status') && $user->monetization_status == Status::MONETIZATION_INITIATE)
            <div class="monetization-card paid">
                <h3 class="monetization-card__title">@lang('Paid Monetization')</h3>
                <p class="monetization-card__desc">
                    @lang('Spend') {{ showAmount(gs('monetization_amount')) }} @lang('to activate monetization quickly, enjoy all the benefits, and start making money').
                </p>
                <a href="{{ route('user.deposit.index', ['id' => 0, 'monetization' => true]) }}"
                    class="btn btn--base">@lang('Active')</a>
                <img class="img"
                    src="{{ frontendImage('monetization_page', $content->data_values->second_image, '202x137') }}"
                    alt="image">
            </div>
        @endif
    </div>
@endsection

@push('script')
    <script>
        (function($) {
            'use strict';

            $(document).ready(function() {
                // Monitization Progressbar

                $('.progress').each(function() {

                    $(this).find('.progressbar').animate({
                        width: $(this).attr('data-percent')
                    }, 3000);

                });


            });

        })(jQuery)
    </script>
@endpush
