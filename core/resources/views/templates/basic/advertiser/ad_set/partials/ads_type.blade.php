
<div class="advertisement-card">
    <div class="advertisement-card__top">
        <h6 class="advertisement-card__title"> @lang('Ads Type') </h6>
    </div>
 
    <div class="advertisement-card__content">
        <h6 class="title"> @lang('Choose Ads Type') </h6>
        <div class="ads-info">
            <div class="form-check form--radio">
                <input class="form-check-input" id="flexRadioDefault1" name="ad_type" value="1" @if(request()->ad_type == Status::ALL_VIEWS || @$advertisement->ad_type == Status::ALL_VIEWS) checked @endif  type="radio">
                <label class="form-check-label" for="flexRadioDefault1">
                    <span class="label-title"> @lang('All Views') </span>
                    <span class="label-text"> @lang('User can view the ad and then click on a link'). </span>
                </label>
            </div>
            <div class="form-check form--radio">
                <input class="form-check-input" id="flexRadioDefault2" name="ad_type"  value="2" @if(request()->ad_type == Status::SKIPPABLE || @$advertisement->ad_type == Status::SKIPPABLE) checked @endif type="radio">
                <label class="form-check-label" for="flexRadioDefault2">
                    <span class="label-title"> @lang('Skippable in-stream ads'). </span>
                    <span class="label-text"> @lang('User can skip this ad.')</span>
                </label>
            </div>
            <div class="form-check form--radio">
                <input class="form-check-input" id="flexRadioDefault3" name="ad_type" value="3" @if(request()->ad_type == Status::NON_SKIPPABLE || @$advertisement->ad_type == Status::NON_SKIPPABLE) checked @endif type="radio">
                <label class="form-check-label" for="flexRadioDefault3">
                    <span class="label-title"> @lang('Non-skippable in-stream ads'). </span>
                    <span class="label-text"> @lang('User can\'t skipped this ad '). </span>
                </label>
            </div>
            <div class="form-check form--radio">
                <input class="form-check-input" id="flexRadioDefault4" name="ad_type" value="4" @if(request()->ad_type== Status::IN_FEED || @$advertisement->ad_type == Status::IN_FEED) checked @endif type="radio">
                <label class="form-check-label" for="flexRadioDefault4">
                    <span class="label-title"> @lang('In-feed video ads'). </span>
                    <span class="label-text"> @lang('The ads show in feed page'). </span>
                </label>
            </div>

        
        </div>
    </div>
</div>
