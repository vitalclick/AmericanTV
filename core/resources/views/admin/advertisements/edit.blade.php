@extends('admin.layouts.app')
@section('panel')
<form action="{{ route('admin.advertisement.update', $advertisement->id) }}" method="post" enctype="multipart/form-data">
    <div class="row">

            @csrf
            <div class="col-lg-6">

                <div class="card">
                    <div class="card-body">

                        <div class="form-group">
                            <label>@lang('Title')</label>
                            <input class="form-control" name="title" type="text" value="{{ __($advertisement->title) }}" required>
                        </div>

                        <div class="form-group ">
                            <label class="form--label">@lang('Categories')</label>
                            <select class="select form--control select2" name="category_id[]" multiple required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @if ($advertisement->categories->pluck('id')->contains($category->id)) selected @endif>
                                        {{ __($category->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        <div class="form-group">
                            <label class="required">@lang('Ad Type')</label>

                            <div class="d-flex flex-wrap justify-content-between">

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" id="inlineRadio1" name="ad_type" type="radio" value="1" @if ($advertisement->ad_type == Status::IMPRESSION) checked @endif>
                                    <label class="form-check-label" for="inlineRadio1">@lang('Per Impression') ({{ gs('cur_sym') }}{{ showAmount(gs('per_impression_spent'), currencyFormat: false) }})</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" id="inlineRadio2" name="ad_type" type="radio" value="2" @if ($advertisement->ad_type == Status::CLICK) checked @endif>
                                    <label class="form-check-label" for="inlineRadio2">@lang('Per Click') ({{ gs('cur_sym') }}{{ showAmount(gs('per_click_spent'), currencyFormat: false) }})</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input " id="inlineRadio3" name="ad_type" type="radio" value="3" @if ($advertisement->ad_type == Status::BOTH) checked @endif>
                                    <label class="form-check-label " for="inlineRadio3">@lang('Both')</label>
                                </div>

                            </div>

                        </div>

                        <div class="row">
                            <div class="col-sm-6 col-xsm-6">
                                <div class="form-group">
                                    <label>@lang('Impression')</label>
                                    <input class="form-control" name="impression" type="number" @if ($advertisement->ad_type == Status::CLICK) readonly @endif value="{{ $advertisement->impression }}" placeholder="0">
                                </div>
                            </div>
                            <div class="col-sm-6 col-xsm-6">
                                <div class="form-group">
                                    <label>@lang('Click')</label>
                                    <input class="form-control" name="click" type="number"  @if ($advertisement->ad_type == Status::IMPRESSION) readonly @endif value="{{ $advertisement->click }}" placeholder="0">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>@lang('Paid Amount')</label>
                            <div class="input-group">
                                <input class="form-control" type="number" name="total_amount" value="{{ getAmount($advertisement->total_amount) }}" placeholder="0">
                                <span class="input-group-text">{{ __(gs('cur_text')) }}</span>

                            </div>
                        </div>


                        <div class="click-wrapper">

                            @if ($advertisement->ad_type == Status::CLICK || $advertisement->ad_type == Status::BOTH )
                                <div class="form-group">
                                    <label>@lang('Url')</label>
                                    <input class="form-control" name="url" type="url" value="{{ $advertisement->url }}" required>
                                </div>
    
                                <div class="form-group">
                                    <label>@lang('Button Label')</label>
                                    <input class="form-control" name="button_label" type="text" value="{{ __($advertisement->button_label) }}" required>
                                </div>
                                <div class="form-group">
                                    <label>@lang('Logo')</label>
                                    <x-image-uploader name="logo" :imagePath="getImage(getFilePath('adLogo') . '/' . $advertisement->logo)" :size="getFileSize('adLogo')" :required="false" />
                                </div>
                            @endif
                        </div>

                        
                        <div class="form-group">
                            <label>@lang('Select Media')</label>
                            
                            <input class="form-control " name="ad_video" type="file" accept="video/*">
                        </div>
                        
                        <div class="form-group">
                            <label >@lang('Status')</label>
                            <input type="checkbox" data-width="100%" data-size="large" data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-height="35" data-on="@lang('Running')" data-off="@lang('Pause')" name="status" @if($advertisement->status == Status::RUNNING) checked @endif>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">@lang('Ad Video')</h5>
                    </div>
                    <div class="card-body">

                        <video class="video-player" controls>
                            <source src="{{ getAd($advertisement->ad_file, $advertisement) }}" type="video/mp4" />
                        </video>
                    </div>
                </div>
            </div>

            <div class="form-group mt-3">
                <button class="btn btn--primary w-100 h-45">@lang('Submit')</button>
            </div>
            
            
        </div>
    </form>
@endsection

@push('style')
    <style>
        

        .plyr__control--overlaid {
            background: #4634ff !important;
        }

        .plyr--video .plyr__control:focus-visible,
        .plyr--video .plyr__control:hover,
        .plyr--video .plyr__control[aria-expanded=true] {
            background: #4634ff !important;

        }

        .plyr--full-ui input[type=range] {
            color: #4634ff !important;

        }

        .plyr__menu__container .plyr__control[role=menuitemradio][aria-checked=true]:before {
            background: #4634ff !important;

            background: #4634ff !important;

        }
    </style>
@endpush


@push('style-lib')
    <link href="{{ asset('assets/global/css/plyr.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
@endpush
@push('script')
    <script>
        (function($) {
            controls = [

                'rewind',
                'play-large',
                'play',
            
                'fast-forward',
                'progress',
                'current-time',
                'duration',
                'mute',
                'settings',
                'fullscreen',
            ];

            $(document).ready(function() {
                const singleplayer = new Plyr('.video-player', {
                    controls,
                    autoplay:true,
                    ratio: '16:9',
                });

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

             
            });

            
            function clickForm() {

$('.click-wrapper').html(`
      <div class="form-group">
                                <label>@lang('Url')</label>
                                <input class="form-control" name="url" type="url" value="{{ $advertisement->url }}" required>
                            </div>

                            <div class="form-group">
                                <label>@lang('Button Label')</label>
                                <input class="form-control" name="button_label" type="text" value="{{ __($advertisement->button_label) }}" required>
                            </div>
                            <div class="form-group">
                                <label>@lang('Logo')</label>
                                <x-image-uploader name="logo" :imagePath="getImage(getFilePath('adLogo') . '/' . $advertisement->logo)" :size="getFileSize('adLogo')" :required="false" />
                            </div>


`)
}
            

        })(jQuery);
    </script>
@endpush
