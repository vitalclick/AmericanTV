<div class="home-body channel-body">
    @if (!blank($plans))
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h3>@lang('Choose Your Plan')</h3>
                <p>@lang('Select the subscription plan that works best for you')</p>
            </div>
        </div>

        <div class="row gy-4 justify-content-center">
            @foreach ($plans as $plan)
                <div class="col-xl-3 col-lg-4 col-sm-6 col-xsm-6">
                    <div class="card custom--card b-radius-5">
                        <div class="card-header text-center">
                            <h4 class="card-title mb-0">{{ __($plan->name) }}</h4>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="text-center mb-4">
                                <h2 class="display-8 fw-bold body-title">{{ showAmount($plan->price) }}
                                </h2>
                                <p class="text-muted">@lang('Per month')</p>
                            </div>

                            <ul class="list--group mb-4">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $plan->videos->count() }} @lang('Videos')</span>
                                    <i class="las la-eye view-videos" data-plan-id="{{ $plan->id }}"></i>
                                </li>
                                <li class="list--group-item d-flex justify-content-between align-items-center mt-2">
                                    <span>{{ $plan->playlists()->count() }} @lang('Playlists')</span>
                                    <i class="las la-eye view-playlists" data-plan-id="{{ $plan->id }}"></i>
                                </li>
                            </ul>

                            <div class="mt-auto text-center">
                                @if (auth()->user() && auth()->user()->hasValidPlan($plan->id))
                                    <a href="{{ getPlanVideoUrl($plan) }}" class="btn btn--success w-100">
                                        @lang('Watch Now')
                                    </a>
                                @else
                                    <button type="submit" class="btn btn--base w-100 subscribe-now"
                                        data-resource="{{ $plan }}">
                                        @lang('Subscribe Now')
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>
    @else
        <div class="empty-container">
            @include('Template::partials.empty')
        </div>
    @endif
</div>

{{-- View videos modal --}}
<div class="modal custom--modal view-video--modal scale-style fade" id="viewVideosModal" data-bs-backdrop="static"
    aria-labelledby="viewVideosModal" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Videos')</h5>
                <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body video-view-list videos-list">
                <div class="text-center d-none spinner mt-4 w-100" id="videos-loading-spinner">
                    <i class="las la-spinner"></i>
                </div>
                <div class="videos-wrapper">

                </div>
            </div>
        </div>
    </div>
</div>

{{-- View playlists modal --}}
<div class="modal custom--modal view-video--modal scale-style fade" id="viewPlaylistsModal" data-bs-backdrop="static"
    aria-labelledby="viewPlaylistsModal" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Playlists')</h5>
                <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body playlist-view-list videos-list">
                <div class="text-center d-none spinner mt-4 w-100" id="playlists-loading-spinner">
                    <i class="las la-spinner"></i>
                </div>
                <div class="playlists-wrapper">

                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal custom--modal payment-modal scale-style fade" id="planGatewayModal"
    aria-labelledby="playlistModalLabel" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content ">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Please purchase this plan to access its content')</h5>

                <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form class="deposit-form" action="{{ route('user.deposit.insert') }}" method="post">
                @csrf
                <input name="currency" type="hidden">
                <input name="plan_id" type="hidden">
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
                                            <svg class="check-circle" width="13" height="10"
                                                viewBox="0 0 13 10" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
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
                                        <p class="text mb-0">@lang('Plan')</p>
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

<div class="modal custom--modal view-video--modal scale-style fade" id="playlistVideosModal"
    data-bs-backdrop="static" aria-labelledby="viewVideosModal" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Playlist Videos')</h5>

                <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body playlist-videos-list videos-list">
                <div class="text-center d-none spinner mt-4 w-100" id="playlists-videos-loading-spinner">
                    <i class="las la-spinner"></i>
                </div>
                <div class="playlist-videos-wrapper">

                </div>
            </div>
        </div>
    </div>
</div>

@push('style')
    <style>
        .view-playlists,
        .view-videos {
            cursor: pointer;
        }

        .videos-list {
            max-height: 500px;
            overflow-y: auto;
        }

        .spinner {
            text-align: center;
            margin-top: 20px;
        }

        .spinner i {
            font-size: 45px;
            color: #ff0000;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .custom--card .body-title {
            margin-bottom: 5px;
        }

        .playlist-thumb {
            width: 80px;
            height: 45px;
            object-fit: cover;
        }

        .list-group-item:not(:last-child) {
            border-bottom: 1px solid hsl(var(--white)/.1);
            padding-bottom: 5px;
        }

        @media (min-width: 425px) and (max-width:575px) {
            .col-xsm-6 {
                width: 50%;
            }
        }

        @media (max-width: 575px) {
            .custom--card .btn {
                padding: 9px 16px;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            $('.view-videos').on('click', function() {
                const modal = $('#viewVideosModal');
                const planId = $(this).data('plan-id');

                modal.modal('show');

                $('.videos-wrapper').empty();
                currentVideosPage = 1;
                lastVideosPage = false;

                modal.data('plan-id', planId);

                loadVideos();
            });

            let currentVideosPage = 1;
            let lastVideosPage = false;

            var videoViewList = $('.video-view-list');

            videoViewList.scroll(function() {
                if (videoViewList.scrollTop() + videoViewList.height() >= videoViewList[0].scrollHeight - 50 &&
                    !lastVideosPage) {
                    currentVideosPage++;
                    loadVideos();
                }
            });

            let videosSearchTimer;

            $('#viewVideosModal').find('input[name="search"]').on('keyup', function() {
                const searchTerm = $(this).val().trim();

                clearTimeout(videosSearchTimer);

                videosSearchTimer = setTimeout(function() {
                    currentVideosPage = 1;
                    lastVideosPage = false;
                    $('.videos-wrapper').empty();
                    loadVideos(searchTerm);
                }, 500);
            });

            function loadVideos(searchTerm = '') {
                const modal = $('#viewVideosModal');
                const planId = modal.data('plan-id');

                const route = "{{ route('plan.videos', ':id') }}".replace(':id', planId);
                $('#videos-loading-spinner').removeClass('d-none');

                $.ajax({
                    url: `${route}?page=${currentVideosPage}&search=${searchTerm}`,
                    type: 'GET',
                    success: function(response) {
                        $('#videos-loading-spinner').addClass('d-none');

                        if (response.status === 'success' && response.data.videos.data.length > 0) {
                            $.each(response.data.videos.data, function(index, video) {

                                var imagePath =
                                    "{{ asset(getFilePath('thumbnail') . '/thumb_' . '12.png') }}";
                                imagePath = imagePath.replace('12.png', video.thumb_image);

                                var videoHTML = `
                                    <div class="d-flex align-items-center plans-video p-3 border-bottom">
                                        <div class="video-thumb me-3">
                                            <img src="${imagePath}" alt="thumb_image" class="check-type-img">
                                        </div>
                                        <div class="video-info">
                                            <h5 class="mb-1">${video.title}</h5>
                                        </div>
                                    </div>
                                `;

                                $('.videos-wrapper').append(videoHTML);
                            });

                            if (currentVideosPage >= response.data.last_page) {
                                lastVideosPage = true;
                            }
                        } else {
                            lastVideosPage = true;

                            if (currentVideosPage === 1 && $('.videos-wrapper').children().length === 0) {
                                var emptyHTML = `
                                    <div class="text-muted text-center empty-msg">
                                        <div class="empty-container empty-card-two">
                                            @include('Template::partials.empty')
                                        </div>
                                    </div>
                                `;
                                $('.videos-wrapper').html(emptyHTML);
                            }
                        }
                    },
                    error: function() {
                        $('#videos-loading-spinner').addClass('d-none');
                        $('.videos-wrapper').html(
                            '<div class="text-center py-4">Error loading videos</div>');
                    }
                });
            }

            $('.view-playlists').on('click', function() {
                const modal = $('#viewPlaylistsModal');
                const planId = $(this).data('plan-id');

                modal.modal('show');

                $('.playlists-wrapper').empty();
                currentPlaylistsPage = 1;
                lastPlaylistsPage = false;

                modal.data('plan-id', planId);

                loadPlaylists();
            });

            let currentPlaylistsPage = 1;
            let lastPlaylistsPage = false;

            var playlistViewList = $('.playlist-view-list');

            playlistViewList.scroll(function() {
                if (playlistViewList.scrollTop() + playlistViewList.height() >= playlistViewList[0]
                    .scrollHeight - 50 && !lastPlaylistsPage) {
                    currentPlaylistsPage++;
                    loadPlaylists();
                }
            });

            let playlistsSearchTimer;

            $('#viewPlaylistsModal').find('input[name="search"]').on('keyup', function() {
                const searchTerm = $(this).val().trim();

                clearTimeout(playlistsSearchTimer);

                playlistsSearchTimer = setTimeout(function() {
                    currentPlaylistsPage = 1;
                    lastPlaylistsPage = false;
                    $('.playlists-wrapper').empty();
                    loadPlaylists(searchTerm);
                }, 500);
            });

            function loadPlaylists(searchTerm = '') {
                const modal = $('#viewPlaylistsModal');
                const planId = modal.data('plan-id');

                const route = "{{ route('plan.playlists', ':id') }}".replace(':id', planId);
                $('#playlists-loading-spinner').removeClass('d-none');

                $.ajax({
                    url: `${route}?page=${currentPlaylistsPage}&search=${searchTerm}`,
                    type: 'GET',
                    success: function(response) {
                        $('#playlists-loading-spinner').addClass('d-none');

                        if (response.status === 'success' && response.data.playlists.data.length > 0) {
                            $.each(response.data.playlists.data, function(index, playlist) {
                                var imagePath = playlist.image_path;

                                var playlistHTML = `
                                    <div class="playlist-item d-flex align-items-center p-3 border-bottom">
                                        <div class="playlist-thumb me-3">
                                            <img src="${imagePath}" alt="playlist_thumb" class="rounded">
                                        </div>
                                        <div class="playlist-info">
                                            <h5 class="mb-1">${playlist.title}</h5>
                                            <div class="d-flex gap-3 align-items-center">
                                                <p class="text-muted mb-0 small">${playlist.videos_count} videos</p>
                                                <a href="javascript:void(0)" class="show-playlist-videos" data-playlist-id="${playlist.id}" data-playlist-title="${playlist.title}">
                                                    <i class="las la-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                `;

                                $('.playlists-wrapper').append(playlistHTML);
                            });

                            if (currentPlaylistsPage >= response.data.last_page) {
                                lastPlaylistsPage = true;
                            }
                        } else {
                            lastPlaylistsPage = true;

                            if (currentPlaylistsPage === 1 && $('.playlists-wrapper').children()
                                .length === 0) {
                                var emptyHTML = `
                                    <div class="text-muted text-center empty-msg">
                                        <div class="empty-container empty-card-two">
                                            @include('Template::partials.empty')
                                        </div>
                                    </div>
                                `;
                                $('.playlists-wrapper').html(emptyHTML);
                            }
                        }
                    },
                    error: function() {
                        $('#playlists-loading-spinner').addClass('d-none');
                        $('.playlists-wrapper').html(
                            '<div class="text-center py-4">Error loading playlists</div>');
                    }
                });
            }


            const auth = "{{ auth()->user() }}";

            if (!auth) {
                $(document).on('click', '.subscribe-now', function(e) {
                    $('#existModalCenter').modal('show');
                });

            } else {

                $(document).on('click', '.subscribe-now', function(e) {
                    e.preventDefault();
                    const modal = $('#planGatewayModal');
                    const plan = $(this).data('resource');
                    modal.find('input[name="plan_id"]').val(plan.id);
                    modal.find('input[name="amount"]').val(parseFloat(plan.price).toFixed(2)).trigger(
                        'input');
                    modal.find('.item-name').text(plan.name);
                    modal.find('.item-price').text(
                        `${parseFloat(plan.price).toFixed(2)} {{ gs('cur_text') }}`);
                    modal.modal('show')
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


            $(document).on('click', '.show-playlist-videos', function() {
                const modal = $('#playlistVideosModal');
                const playlistId = $(this).data('playlist-id');

                modal.modal('show');

                $('.playlist-videos-wrapper').empty();
                currentVideosPage = 1;
                lastVideosPage = false;

                modal.data('playlist-id', playlistId);

                loadPlaylistVideos();
            });

            var videoViewList = $('.video-view-list');

            videoViewList.scroll(function() {
                if (videoViewList.scrollTop() + videoViewList.height() >= videoViewList[0].scrollHeight - 50 &&
                    !lastVideosPage) {
                    currentVideosPage++;
                    loadPlaylistVideos();
                }
            });

            function loadPlaylistVideos() {
                const modal = $('#playlistVideosModal');
                const playlistId = modal.data('playlist-id');

                const route = "{{ route('plan.playlist.videos', ':id') }}".replace(':id', playlistId);
                $('#playlists-videos-loading-spinner').removeClass('d-none');

                $.ajax({
                    url: `${route}?page=${currentVideosPage}`,
                    type: 'GET',
                    success: function(response) {
                        $('#playlists-videos-loading-spinner').addClass('d-none');

                        if (response.status === 'success' && response.data.videos.data.length > 0) {
                            $.each(response.data.videos.data, function(index, video) {

                                var imagePath =
                                    "{{ asset(getFilePath('thumbnail') . '/thumb_' . '12.png') }}";
                                imagePath = imagePath.replace('12.png', video.thumb_image);

                                var videoHTML = `
                            <div class="d-flex align-items-center plan-video p-3 border-bottom">
                                <div class="video-thumb me-3">
                                    <img src="${imagePath}" alt="thumb_image" class="check-type-img">
                                </div>
                                <div class="video-info">
                                    <h5 class="mb-1">${video.title}</h5>
                                </div>
                            </div>
                        `;

                                $('.playlist-videos-wrapper').append(videoHTML);
                            });

                            if (currentVideosPage >= response.data.last_page) {
                                lastVideosPage = true;
                            }
                        } else {
                            lastVideosPage = true;

                            if ($('.playlist-videos-wrapper').is(':empty') && currentVideosPage === 1) {
                                $('.playlist-videos-wrapper').html(
                                    '<div class="text-center py-4">@lang('No videos found')</div>');
                            }
                        }
                    },
                    error: function() {
                        $('#playlists-videos-loading-spinner').addClass('d-none');
                        $('.playlist-videos-wrapper').html(
                            '<div class="text-center py-4">Error loading videos</div>');
                    }
                });
            }


        })(jQuery);
    </script>
@endpush

@push('style')
    <style>
        @media (max-width: 424px) {
            .plans-video {
                flex-direction: column;
                gap: 20px;
                padding: 0 !important;
                padding-bottom: 15px !important;
            }

            .video-thumb {
                width: 100%;
                margin-right: 0 !important;
            }

            .video-thumb img {
                max-width: 100%;
            }

            .video-info {
                width: 100%;
            }
        }

        .show-playlist-videos {
            line-height: 100%;
        }
    </style>
@endpush
