  <div class="advertisement-card">
      <div class="advertisement-card__top">
          <div class="d-flex justify-content-between">
              <h6 class="advertisement-card__title">@lang('Ad Details')</h6>
              <div class="form--switch form--switch-sm mt-2">
                  <input class="form-check-input" id="clickable" name="is_clickable" value="1" type="checkbox"
                      @if (@$advertisement->is_clickable == Status::YES) checked @endif>
                  <label class="form-check-label" for="clickable">
                      @lang('Clickable')
                  </label>
              </div>

          </div>
      </div>
      <div class="advertisement-card__content">
          <div class="row gy-4">
              <div class="col-sm-6">
                  <label class="form--label"> @lang('Button Label') </label>
                  <input class="form--control" name="button_label"
                      value="{{ old('button_label', @$advertisement->button_label) }}" placeholder="Click Here"
                      type="text"  disabled>
              </div>
              <div class="col-sm-6">
                  <label class="form--label"> @lang('Action Url') </label>
                  <input class="form--control" value="{{ old('action_url', @$advertisement->url) }}"
                      placeholder="https://example.com" type="text" name="action_url"  disabled>
              </div>
              <div class="col-sm-12">
                  <label class="form--label"> @lang('Your Logo') </label>
                  <input type="file" name="logo" class="form--control" disabled @required(!$advertisement)>
              </div>
          </div>
      </div>
  </div>


  @push('script')
  <script>
      (function ($) {
          "use strict";
          $('input[name="is_clickable"]').on('change', function () {
              if ($(this).is(':checked')) {
                  $('input[name="button_label"]').attr('disabled', false);
                  $('input[name="action_url"]').attr('disabled', false);
                  $('input[name="logo"]').attr('disabled', false);
              } else {
                  $('input[name="button_label"]').attr('disabled', true);
                  $('input[name="action_url"]').attr('disabled', true);
                  $('input[name="logo"]').attr('disabled', true);
              }
          })
      })(jQuery);
  </script>
      
  @endpush