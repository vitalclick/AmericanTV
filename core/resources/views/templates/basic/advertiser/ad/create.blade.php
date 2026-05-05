@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <form class="create-advertising__form" action="" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="create-advertising">
                <div class="advertising-heading">
                    <h3 class="advertising-heading__title">
                        <span class="icon"><i class="vti-advertising"></i></span>
                        {{ __($pageTitle) }}
                    </h3>
                </div>
                <div class="row gy-4">
                    <div class="col-xl-7 col-lg-7 col-md-6">
                        <div class="add-create-form">

                            <div class="form-group">
                                <label class="form--label">@lang('Select Video')</label>

                                <label class="mediaUploadLabel" for="mediaUpload">
                                    <span class="icon">
                                        <svg class="lucide lucide-clapperboard" xmlns="http://www.w3.org/2000/svg"
                                            width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <path
                                                d="M20.2 6 3 11l-.9-2.4c-.3-1.1.3-2.2 1.3-2.5l13.5-4c1.1-.3 2.2.3 2.5 1.3Z" />
                                            <path d="m6.2 5.3 3.1 3.9" />
                                            <path d="m12.4 3.4 3.1 4" />
                                            <path d="M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
                                        </svg>
                                    </span>
                                    <span class="text">@lang('Browse To Upload')</span>
                                    <input class="videoUpload" id="mediaUpload" name="ad_video" type="file" required
                                        accept="video/*">
                                </label>

                                <div class="progress d-none mt-3">
                                    <div class="progress-bar bg-success" role="progressbar" aria-valuenow="0"
                                        aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-bar-text"></span>

                            </div>


                            <div class="form-group">
                                <label class="form--label">@lang('Title')</label>
                                <input class="form--control" name="title" type="text" value="{{ old('title') }}"
                                    required>
                            </div>
                            <div class="form-group ">
                                <label class="form--label">@lang('Categories')</label>
                                <select class="select form--control select2" name="category_id[]" multiple required>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ __($category->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @php
                                $priceBoth = gs('per_click_spent') + gs('per_impression_spent');
                            @endphp
                            <div class="form-group">
                                <label class="form--label required">@lang('Ad Type')</label>

                                <div class="check-type-wrapper">
                                    <label class="check-type check-type-warning" for="inlineRadio1">
                                        <input class="check-type-input" id="inlineRadio1" name="ad_type" type="radio"
                                            value="1">
                                        <span class="check-type-icon">
                                            <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor"
                                                    stroke-linecap="round"></path>
                                            </svg>
                                        </span>
                                        <span class="check-type-label">@lang('Per Impression')
                                            ({{ gs('cur_sym') }}{{ showAmount(gs('per_impression_spent'), currencyFormat: false) }})</span>
                                    </label>
                                    <label class="check-type check-type-primary" for="inlineRadio2">
                                        <input class="check-type-input" id="inlineRadio2" name="ad_type" type="radio"
                                            value="2">
                                        <span class="check-type-icon">
                                            <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor"
                                                    stroke-linecap="round"></path>
                                            </svg>
                                        </span>
                                        <span class="check-type-label">@lang('Per Click')
                                            ({{ gs('cur_sym') }}{{ showAmount(gs('per_click_spent'), currencyFormat: false) }})</span>
                                    </label>
                                    <label class="check-type check-type-success" for="inlineRadio3">
                                        <input class="check-type-input" id="inlineRadio3" name="ad_type" type="radio"
                                            value="3">
                                        <span class="check-type-icon">
                                            <svg class="check-circle" width="13" height="10" viewBox="0 0 13 10"
                                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path class="check" d="M1 5L4.5 8.5L12.5 0.5" stroke="currentColor"
                                                    stroke-linecap="round"></path>
                                            </svg>
                                        </span>
                                        <span class="check-type-label">@lang('Both')</span>
                                    </label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-6 col-xsm-6">
                                    <div class="form-group">
                                        <label class="form--label">@lang('Impression')</label>
                                        <input class="form--control" name="impression" type="number" placeholder="0"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xsm-6">
                                    <div class="form-group">
                                        <label class="form--label">@lang('Click')</label>
                                        <input class="form--control" name="click" type="number" placeholder="0"
                                            readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group click-wrapper">
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-5 col-lg-5 col-md-6">
                        <div class="chart-box">
                            <h5 class="chart-box__title mb-2">@lang('Summary')</h5>
                            <table class="table">
                                <tbody>

                                    <tr>
                                        <td>@lang('Impressions')</td>
                                        <td>1 =
                                            {{ gs('cur_sym') }}{{ showAmount(gs('per_impression_spent'), currencyFormat: false) }}
                                        </td>
                                        <td><span class="impression">0</span> x
                                            {{ gs('cur_sym') }}{{ showAmount(gs('per_impression_spent'), currencyFormat: false) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>@lang('Click')</td>
                                        <td>1 =
                                            {{ gs('cur_sym') }}{{ showAmount(gs('per_click_spent'), currencyFormat: false) }}
                                        </td>
                                        <td><span class="click">0</span> x
                                            {{ gs('cur_sym') }}{{ showAmount(gs('per_click_spent'), currencyFormat: false) }}
                                        </td>
                                    </tr>
                                </tbody>

                            </table>
                            <table class="table pt-0 mb-3">

                                <tbody>
                                    <tr>
                                        <td colspan="2">@lang('Total')</td>

                                        <td>{{ gs('cur_sym') }}<span class="total">0</span></td>
                                    </tr>

                                </tbody>

                            </table>

                            <div class="form-group mb-0 text-end">
                                <button class="btn btn--base w-100"><i
                                        class="las la-arrow-right"></i>@lang('Processed to Payment')</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    </div>
@endsection

@push('style')
    <style>
        .chart-box {
            min-height: 230px;
        }

        .select2-search__field {
            width: 100% !important;
        }

        .form-check-label {
            cursor: pointer;
        }

        .select2-container--default .select2-selection--multiple {
            min-height: 41px !important;
            height: unset !important;
        }
    </style>
@endpush
@push('style-lib')
    <link href="{{ asset('assets/global/css/select2.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';
            $(document).ready(function() {
                summery();
            });

            const impressionField = $('[name="impression"]');
            const clickField = $('[name="click"]');

            $('[name="ad_type"]').on('change', function() {
                const value = $(this).val();

                switch (value) {
                    case '1':
                        impressionField.prop('readonly', false).prop('required', true);
                        clickField.prop('readonly', true).val('').prop('required', false);
                        $('[name="url"]').prop('required', false);
                        $('.click-wrapper').empty()
                        break;
                    case '2':
                        impressionField.prop('readonly', true).val('').prop('required', false);
                        clickField.prop('readonly', false).prop('required', true);
                        $('[name="url"]').prop('required', true);
                        clickForm();
                        break;
                    case '3':
                        impressionField.prop('readonly', false).prop('required', true);
                        clickField.prop('readonly', false).prop('required', true);
                        $('[name="url"]').prop('required', true);
                        clickForm();

                        break;
                    default:
                        impressionField.prop('readonly', true).val('');
                        clickField.prop('readonly', true).val('');
                        break;
                }

                summery();
            });

            function clickForm() {

                $('.click-wrapper').html(`
                    <div class="form-group">
                        <label class="form--label required">@lang('Url')</label>
                        <input class="form--control" name="url" type="url" value="{{ old('url') }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form--label required">@lang('Button Label')</label>
                        <input class="form--control" name="button_label" type="text" value="{{ old('button_label') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form--label">@lang('Upload Logo')</label>
                        <label class="mediaUploadLabel" for="logoUpload">
                            <span class="icon"><i class="vti-add-hook"></i></span>
                            <span class="text">@lang('Browse To Upload')</span>
                            <input class="form--control mediaUploadInput" id="logoUpload" name="logo" type="file" required accept=".jpg , .png , .jpeg">
                        </label>

                        <small class="alertText text--success"></small>
                    </div>


                `)
            }

            impressionField.add(clickField).on('input', function() {
                summery();
            });

            function summery() {
                const impressionValue = parseFloat(impressionField.val()) || 0;
                const clickValue = parseFloat(clickField.val()) || 0;

                $('.impression').text(impressionValue);

                $('.click').text(clickValue);


                const total = impressionValue * parseFloat("{{ gs('per_impression_spent') }}") + clickValue *
                    parseFloat("{{ gs('per_click_spent') }}");


                    console.log();
                    
                $('.total').text(total.toFixed(2));
            }



            $(document).on('change', '[name="logo"]', function() {
                const logo = $(this).prop('files')[0];
                if (!logo) return;

                $('.alertText').text('File Selected: ' + logo.name);




            });

            $(document).ready(function() {
                $('.videoUpload').on('change', function(event) {
                    const file = $(this)[0].files[0];
                    if (!file) return;

                    const url = "{{ route('user.advertiser.upload.ad.video') }}";
                    const formData = new FormData();
                    formData.append('video', file);


                    $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0);
                    $('.progress').removeClass('d-none');
                    $('.progress-bar-text').text('Uploading your file, please wait...');
                    let simulatedProgress = 0;
                    let interval = setInterval(function() {

                        if (simulatedProgress >= 90) {
                            simulatedProgress = 100;
                            clearInterval(interval);
                            $('.progress-bar').css('width', '100%').attr('aria-valuenow', 100);

                        } else {
                            simulatedProgress += 10;
                            $('.progress-bar').css('width', simulatedProgress + '%').attr(
                                'aria-valuenow',
                                simulatedProgress);
                        }
                    }, 1000);


                    $.ajax({
                        type: "POST",
                        url: url,
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        processData: false,
                        contentType: false,
                        dataType: "json",

                        success: function(response) {
                            if (response.status === 'success') {
                                clearInterval(interval);
                                $('.progress-bar').css('width', '100%').attr(
                                    'aria-valuenow', 100);

                                    if ("{{ @gs('is_storage') }}" == 1 && "{{$availableStorage}}" == true) {
                                        clearInterval(interval);
                                        $('.progress-bar').css('width', '100%').attr('aria-valuenow', 100);
                                        uploadFtpServer(response)
                                    } else {
                                    $('.progress-bar-text').text('Upload Complete');
                                    const url =  "{{ route('user.advertiser.processed.checkout', '') }}/" +
                                        response.data.advertisement.id;
                                    $('.create-advertising__form').attr('action', url);
                                    notify('success', response.message.success);
                                }
                            } else {
                                $('.progress-bar-text').text('');
                                notify('error', response.message.error);
                            }
                        },
                        error: function() {
                            $('.progress-bar-text').text('');
                            notify('error', '@lang('Upload failed. Please try again.')');
                        }

                    });
                });
            });

            function uploadFtpServer(response) {

                $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0);
                $('.progress-bar-text').text('Uploading your file to the server, please wait...');

                let simulatedProgress = 0;
                let interval = setInterval(function() {

                    if (simulatedProgress >= 90) {
                        simulatedProgress = 100;
                        clearInterval(interval);
                        $('.progress-bar').css('width', '100%').attr('aria-valuenow', 100);
                    } else {
                        simulatedProgress += 10;
                        $('.progress-bar').css('width', simulatedProgress + '%').attr('aria-valuenow',
                            simulatedProgress);
                    }
                }, 1000);


                $.ajax({
                    type: "get",
                    url: "{{ route('user.advertiser.upload.ad.ftp') }}/" + response.data.advertisement.id,
                    success: function(response) {
                        clearInterval(interval);
                        $('.progress-bar').css('width', '100%').attr('aria-valuenow', 100);
                        $('.progress-bar-text').text('File Successfully Uploaded');
                        if (response.status == 'success') {
                            notify('success', response.message.success);
                            const url = "{{ route('user.advertiser.processed.checkout', '') }}/" + response.data.advertisement.id;
                            $('.create-advertising__form').attr('action', url);
                        } else {
                            $('.progress-bar-text').text('');
                            notify('error', response.message.error);
                        }
                    },
                    error: function() {
                        clearInterval(interval);
                        $('.progress-bar-text').text('');
                        $('.progress-bar').css('width', '100%').attr('aria-valuenow', 100);
                        notify('error', 'FTP upload failed. Please try again.');
                    }
                });
            }



        })(jQuery);
    </script>
@endpush
