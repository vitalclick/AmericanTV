<div class="modal scale-style fade custom--modal" id="existModalCenter" tabindex="-1" role="dialog"
    aria-labelledby="existModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="existModalLongTitle">@lang('You are with us')</h4>
                <span type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </span>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    @lang('To continue, please') <a href="{{ route('user.login') }}" class="text--white fw-bold">@lang(' log in ')
                    </a>
                    @lang('to') {{ __(gs('site_name')) }}
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--white outline btn--sm"
                    data-bs-dismiss="modal">@lang('Close')</button>
                <a href="{{ route('user.login') }}" class="btn btn--sm btn--white">@lang('Login')</a>
            </div>
        </div>
    </div>
</div>
