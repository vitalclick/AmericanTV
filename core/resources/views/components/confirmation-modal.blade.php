<div id="confirmationModal" class="modal custom--modal fade @if ($frontend) scale-style @endif"
    tabindex="-1" role="dialog">
    <div class="modal-dialog @if ($frontend) modal-dialog-centered @endif" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Confirmation Alert!')</h5>
                <button type="button" class="close btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form method="POST">
                @csrf
                <div class="modal-body">
                    <p class="question"></p>
                    {{$slot}}
                </div>

                <div class="modal-footer">
                    <button type="button"
                        class="btn--sm btn @if ($frontend) btn--white outline @else btn--dark @endif"
                        data-bs-dismiss="modal">@lang('No')</button>
                    <button type="submit"
                        class="btn--sm btn @if ($frontend) btn--white @else btn--primary @endif">@lang('Yes')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
    <script>
        (function($) {
            "use strict";
            $(document).on('click', '.confirmationBtn', function() {
                var modal = $('#confirmationModal');
                let data = $(this).data();
                modal.find('.question').text(`${data.question}`);
                modal.find('form').attr('action', `${data.action}`);
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush
