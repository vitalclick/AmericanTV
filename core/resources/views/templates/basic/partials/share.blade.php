<div class="custom--modal scale-style modal fade" id="shareModal" aria-labelledby="exampleModalLabel" aria-hidden="true" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">@lang('Share On')</h4>
                <span class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                    <i class="las la-times"></i>
                </span>
            </div>
            <div class="modal-body">
                <div class="share-items"></div>
                <div class="share-embed">
                    <input class="form--control copyText" type="text" value="{{ route('video.play', [@$video->id, @$video->slug]) }}">
                    <button class="share-embed-btn copyBtn">@lang('Copy')</button>
                </div>
            </div>
        </div>
    </div>
</div>


@push('script')
    <script>
        $(document).on('click', '.copyBtn', function(e) {

            var input = $(this).parent('.share-embed').find('.copyText');
            if (input && input.select) {
                input.select();
                try {
                    document.execCommand('SelectAll')
                    document.execCommand('Copy', false, null);
                    input.blur();
                    notify('success', `Copied successfully`);
                } catch (err) {
                    alert('Please press Ctrl/Cmd + C to copy');
                }
            }
        });
    </script>
@endpush
