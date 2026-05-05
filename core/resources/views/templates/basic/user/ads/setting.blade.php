@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-content">
        <div class="card custom--card">
            <div class="card-header">
                <h5 class="card-title">{{ __($pageTitle) }}</h5>
            </div>
            <div class="card-body">
                <div class="row gy-5">
                    <div class="col-md-6">
                        <div class="form-group form-group rounded-3 overflow-hidden">
                            <video class="video-player" data-poster="{{ getImage(getFilePath('thumbnail') . '/' . $video->thumb_image) }}" controls>
                                @foreach ($video->videoFiles as $file)
                                    <source src="{{ getVideo($file->file_name, $video) }}" type="video/mp4" size="{{ $file->quality }}" />
                                @endforeach
                            </video>
                        </div>

                        <div class="text-end">
                            <button class="btn btn--base btn--sm w-100 addDuration"><i
                                   class="las la-plus"></i>@lang('Add Ad play Duration')</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <form action="{{ route('user.ad.play.duration', $video->slug) }}" method="post">
                            @csrf
                            <div class="form-group duration-wrapper">
                                <label class="form--label">@lang('Ad Play Duration')</label>
                                @php
                                    $playDurations = old('play_durations', $video->adPlayDurations ?? []);
                                    $countPlaydurations = count($playDurations);

                                @endphp

                                @foreach ($playDurations as $key => $duration)
                                    <div class="form-group input-group durationField">
                                        <input class="form--control form-control" name="play_durations[]" type="text" value="{{ is_object($duration) ? $duration->play_duration : $duration }}" readonly required>
                                        <span class="input-group-text"><i class="las la-clock"></i></span>

                                        <button class="btn btn--danger btn--sm removeDuration" type="button">
                                            <i class="las la-times"></i>
                                        </button>
                                    </div>
                                @endforeach

                            </div>
                            <div class="form-group submitBtn @if (blank($playDurations)) d-none @endif">
                                <button class="btn btn--base">@lang('Submit')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style-lib')
    <link href="{{ asset('assets/global/css/plyr.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/plyr.js') }}"></script>
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            $(document).ready(function() {

                const controls = [
                    'play-large',
                    'play',
                    'progress',
                    'current-time',
                    'duration',
                    'setting'
                ];

                const player = new Plyr('.video-player', {
                    controls,
                    ratio: '16:9',

                });

                var adTimesSet = [];
                var adsSetForInterval;
                var playDurations = "{{ $countPlaydurations }}"; 
                var adminInterval = parseInt("{{ gs('ad_config')->per_minute }}");
                var adsPerInterval = parseInt("{{ gs('ad_config')->ad_views }}");

                $(document).ready(function() {
                
                    var duration = @json($playDurations)
                
                

                    var existingAdDurations = duration || '[]';
                    

                    $.each(existingAdDurations, function (indexInArray, adTime) { 
                         
                        var minutes = Math.floor(adTime.play_duration / 60);
                        var intervalBlock = Math.floor(minutes / adminInterval);
                        
                        adTimesSet.push({
                            intervalBlock: intervalBlock,
                            time: adTime.play_duration
                        });
                    });
                    if (existingAdDurations.length > 0) {
                        $('.submitBtn').removeClass('d-none');
                    }

                
                    
                });

                
                $('.addDuration').on('click', function() {
                
                    var currentTime = player.currentTime;
                    var minutes = Math.floor(currentTime / 60);
                    var intervalBlock = Math.floor(minutes / adminInterval);

                
                    adsSetForInterval = adTimesSet.filter(time => time.intervalBlock === intervalBlock).length;

                    if (adsSetForInterval < adsPerInterval) {
                        var seconds = (currentTime % 60).toFixed(0);
                        var formattedTime = `${minutes}.${seconds.padStart(2, '0')}`;

                    
                        $('.duration-wrapper').append(`
            <div class="form-group input-group durationField">
                <input type="text" name="play_durations[]" class="form--control form-control" value="${formattedTime}" readonly required>
                <span class="input-group-text"><i class="las la-clock"></i></span>
                <button class="btn btn--danger btn--sm removeDuration"><i class="las la-times"></i></button>
            </div>
        `);
                        $('.submitBtn').removeClass('d-none');
                        adTimesSet.push({
                            intervalBlock: intervalBlock,
                            time: formattedTime
                        });
                    } else {
                        notify('error','Maximum number of ads for this interval has already been added.');
                    }
                });

            
                $(document).on('click', '.removeDuration', function() {
                    var inputValue = $(this).siblings('input').val();
                    adTimesSet = adTimesSet.filter(adTime => adTime.time !== inputValue);
                    $(this).parent().remove();
                });

            });


        })(jQuery);
    </script>
@endpush
