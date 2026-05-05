@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="home-body importExport-body">
        <div class="upload-import-area">
            <div class="upload-import-area__header">
                <h3 class="upload-import-area__title">
                    @lang('Upload new video')
                </h3>
            </div>

            @if (request()->routeIs('user.shorts.*'))
                <ul class="upload-list">
                    <li
                        class="upload-list__item  @if (@$video->step == Status::THIRD_STEP) active @else  {{ menuActive(['user.shorts.upload.form', 'user.shorts.details.form', 'user.shorts.visibility.form']) }} @endif">
                        <a class="upload-list__link " href="{{ route('user.shorts.upload.form', @$video->id) }}">
                            <span class="circle">1</span>
                            <span class="text">@lang('Upload')</span>
                        </a>
                    </li>
                    <li
                        class="upload-list__item   @if (@$video->step == Status::THIRD_STEP) active @else  {{ menuActive(['user.shorts.details.form', 'user.shorts.visibility.form']) }} @endif">
                        <a class="upload-list__link @if (@$video->step < Status::FIRST_STEP) disabled-link @endif " href="{{ route('user.shorts.details.form', @$video->id) }}">
                            <span class="circle">2</span>
                            @lang('Details')
                        </a>
                    </li>

                    <li class="upload-list__item  @if (@$video->step == Status::THIRD_STEP) active @else  {{ menuActive(['user.shorts.visibility.form']) }} @endif">
                        <a class="upload-list__link @if (@$video->step < Status::SECOND_STEP) disabled-link @endif" href="{{ route('user.shorts.visibility.form', @$video->id) }}">
                            <span class="circle">3</span>
                            @lang('Visibility')
                        </a>
                    </li>
                </ul>
            @else
                <ul class="upload-list">
                    <li
                        class="upload-list__item @if (@$video->step == Status::FOURTH_STEP) active @else  {{ menuActive(['user.video.upload.form', 'user.video.details.form', 'user.video.elements.form', 'user.video.visibility.form']) }} @endif">
                        <a class="upload-list__link" href="{{ route('user.video.upload.form', @$video->id) }}">
                            <span class="circle">1</span>
                            @lang('Upload')
                        </a>
                    </li>
                    <li
                        class="upload-list__item  @if (@$video->step == Status::FOURTH_STEP) active @else {{ menuActive(['user.video.details.form', 'user.video.elements.form', 'user.video.visibility.form']) }} @endif">
                        <a class="upload-list__link @if (@$video->step < Status::FIRST_STEP) disabled-link @endif" href="{{ route('user.video.details.form', @$video->id) }}">
                            <span class="circle">2</span>
                            @lang('Details')
                        </a>
                    </li>
                    <li
                        class="upload-list__item @if (@$video->step == Status::FOURTH_STEP) active @else {{ menuActive(['user.video.elements.form', 'user.video.visibility.form']) }} @endif">
                        <a class="upload-list__link @if (@$video->step < Status::SECOND_STEP) disabled-link @endif " href="{{ route('user.video.elements.form', @$video->id) }}">
                            <span class="circle">3</span>
                            @lang('Elements')
                        </a>
                    </li>
                    <li class="upload-list__item  @if (@$video->step == Status::FOURTH_STEP) active @else {{ menuActive(['user.video.visibility.form']) }} @endif">
                        <a class="upload-list__link @if (@$video->step < Status::THIRD_STEP) disabled-link @endif" href="{{ route('user.video.visibility.form', @$video->id) }}">
                            <span class="circle">4</span>
                            @lang('Visibility')
                        </a>
                    </li>
                </ul>
            @endif

            <div class="uplaod_wrapper">

                @yield('uplaod_content')

                @if (request()->routeIs(['user.video.upload.form', 'user.shorts.upload.form']))
                    <p class="upload-import-area__desc">
                        <span>
                            @lang('By submitting your videos to ' . gs('site_name') . ', you acknowledge that you agree to ' . gs('site_name'))
                            @if (gs('agree'))
                                @php
                                    $policyPages = getContent('policy_pages.element', false, orderById: true);
                                @endphp
                                @foreach ($policyPages as $policy)
                                    <a class="link text--white fw-semibold"
                                       href="{{ route('policy.pages', $policy->slug) }}" target="__blank"
                                       target="_blank">{{ __($policy->data_values->title) }}</a>
                                    @if (!$loop->last)
                                        ,
                                    @endif
                                @endforeach
                            @endif
                            @lang('Please be sure not to violate others copyright or privacy rights').
                        </span>
                    </p>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .disabled-link {
            pointer-events: none;
            cursor: not-allowed;
            color: #6c757d;
            text-decoration: none;
        }
    </style>
@endpush
