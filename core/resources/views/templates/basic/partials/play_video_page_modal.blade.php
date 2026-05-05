{{-- embed modal --}}
<div class="custom--modal fade scale-style modal" id="embedModal" aria-labelledby="exampleModalLabel" aria-hidden="true"
    tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Embeded Link')</h5>
                <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="share-embed">
                    <textarea class="form--control copyText"><iframe src="{{ route('embed', [$video->id, $video->slug]) }}" width="560" height="315" frameborder="0" allowfullscreen></iframe></textarea>
                    <button class="share-embed-btn copyBtn">@lang('Copy')</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- share modal --}}

<div class="custom--modal fade scale-style modal" id="shareModal" aria-labelledby="exampleModalLabel" aria-hidden="true"
    tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Share On')</h5>
                <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="share-items">
                    <a class="share-item whatsapp"
                        href="https://api.whatsapp.com/send?text={{ route('video.play', [$video->id, $video->slug]) }}"
                        target="_blank">
                        <i class="lab la-whatsapp"></i>
                    </a>
                    <a class="share-item facebook"
                        href="https://www.facebook.com/sharer/sharer.php?u={{ route('video.play', [$video->id, $video->slug]) }}"
                        target="_blank">
                        <i class="lab la-facebook-f"></i>
                    </a>

                    <a class="share-item twitter"
                        href="https://twitter.com/intent/tweet?url={{ route('video.play', [$video->id, $video->slug]) }}&text={{ $video->title }}"
                        target="_blank">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                    <a class="share-item envelope"
                        href="mailto:?subject={{ $video->title }}&body={{ route('video.play', [$video->id, $video->slug]) }}">
                        <i class="las la-envelope"></i>
                    </a>
                </div>
                <div class="share-embed">
                    <input class="form--control copyText" type="text"
                        value="{{ route('video.play', [$video->id, $video->slug]) }}">
                    <button class="share-embed-btn copyBtn">@lang('Copy')</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- add video playlist modal --}}
<div class="modal custom--modal scale-style fade" id="addVideoModal" aria-labelledby="addVideoModal" aria-hidden="true"
    tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form class="add-video-form" method="post">
                @csrf
                <input name="video_id" type="number" value="{{ @$video->id }}" hidden>
                <div class="modal-body playlist-list">
                    @if (!blank($playlists))
                        @foreach ($playlists as $playlist)
                            <label class="check-type mb-2 w-100" for="flexCheck{{ $playlist->id }}">
                                <input class="check-type-input" id="flexCheck{{ $playlist->id }}" name="playlist_id[]"
                                    type="checkbox" value="{{ $playlist->id }}"
                                    @if (in_array($playlist->id, $video->playlists->pluck('id')->toArray())) checked @endif>
                                <span class="check-type-icon">
                                    <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor"
                                            stroke-linecap="round">
                                        </path>
                                    </svg>
                                </span>
                                <span class="check-type-label" for="flexCheck{{ $playlist->id }}">
                                    <p>{{ __($playlist->title) }}</p>
                                </span>
                            </label>
                        @endforeach
                    @else
                        <div class="justify-content-center d-flex flex-column">
                            <h6 class="text-center">@lang('No Playlist Found')</h6>
                            @auth
                                <a class="text-center"
                                    href="{{ route('preview.playlist', auth()->user()->slug) }}">@lang('Create a new playlist')</a>
                            @endauth
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button
                        class="btn btn--base submitBtn w-100 btn--sm @if (blank($playlists)) disabled @endif"
                        type="button">@lang('Add')</button>
                </div>
            </form>
        </div>
    </div>
</div>


{{-- payment modal --}}

<div class="modal custom--modal payment-modal scale-style fade" id="paymentConfirmationModal"
    aria-labelledby="playlistModalLabel" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content ">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Please purchase this video to access our premium content')</h5>

                <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form class="deposit-form" action="{{ route('user.deposit.insert') }}" method="post">
                @csrf
                <input name="currency" type="hidden">
                <input type="hidden" name="playlist_id">
                <input type="hidden" name="video_id">
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
                                value="{{ getAmount($video->price) }}" placeholder="@lang('00.00')" readonly
                                autocomplete="off">
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



    {{-- unSubcriberModal --}}
    <div class="modal scale-style fade custom--modal" id="unSubcriberModal" aria-labelledby="unSubcriberModalLabel"
        aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Confirm Alert!')</h5>
                    <button class="close modal-close-btn" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>@lang('Are you sure you want to unsubscribe this channel?')</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn--sm btn--white outline" data-bs-dismiss="modal"
                        type="button">@lang('No')</button>
                    <button class="btn btn--sm btn--white confirmUnsubscribe" type="button">@lang('Yes')</button>
                </div>
            </div>
        </div>
    </div>

    