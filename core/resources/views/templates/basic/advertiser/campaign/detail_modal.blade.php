
<div class="modal fade custom--modal modal-two" id="campaignDetailModal">
    <div class="modal-dialog modal-dialog-centered modal-xl ">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header__left">
                    <h6 class="modal-title">   <i class="las la-bullhorn"></i> @lang('Campaign'): {{ __($campaign->title) }} </h6>
                </div>
                <button class="btn-close" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <span class="btn-close__icon"> <i class="las la-times"></i> </span>
                </button>
            </div>
            <div class="modal-body">
                @include('templates.basic.advertiser.campaign.detail_table', ['campaign' => $campaign])    
            </div>
        </div>
    </div>
</div>
