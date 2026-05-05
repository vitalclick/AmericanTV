<div class="advertisement-card">
    <div class="advertisement-card__top">
        <h6 class="advertisement-card__title"> @lang('Targeting') </h6>
    </div>

    @php

        $locations = $advertisement?->countries->pluck('country')->toArray() ?? [];
        $expectedCountries = $advertisement?->expectedCountries->pluck('country')->toArray() ?? [];
        $selectedCategoryIds = $advertisement?->categories->pluck('category_id')->toArray() ?? [];

    @endphp
    <div class="tab-content" id="pills-tabContent">
        <div class="tab-pane fade show active" id="pills-create" role="tabpanel" aria-labelledby="pills-create-tab"
            tabindex="0">
            <div class="audience-information-wrapper">
                <div class="audience-information">
                    <div class="row gy-3">
                        <div class="col-sm-6 select2-parent">
                            <label class="form--label"> @lang('Country') <span class="text--base"
                                    data-bs-toggle="tooltip" title="@lang('If you create ads for some specific countries, you can select them here')"><i
                                        class="las la-info-circle"></i></span> </label>
                            <select class="select2-basic" name="countries[]" multiple
                                aria-label="Default select example">
                                @foreach ($countries as $country)
                                    <option value="{{ $country->country }}"
                                        @if (in_array($country->country, $locations)) selected @endif>{{ $country->country }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form--switch form--switch-sm mt-2">
                                <input class="form-check-input" id="allCountries" name="is_all_countries" value="1"
                                    type="checkbox" @if (@$advertisement->is_all_countries == Status::YES) checked @endif>

                                <label class="form-check-label" for="allCountries">
                                    @lang('All Countries')
                                </label>
                            </div>
                        </div>




                        <div class="col-sm-6 select2-parent ">
                            <label class="form--label"> @lang('Categories') <span class="text--base"
                                    data-bs-toggle="tooltip" title="@lang('If you create ads for some specific categories, you can select them here')"><i
                                        class="las la-info-circle"></i></span> </label>

                            <select class="form-select form--control select2-basic" name="categories[]" multiple
                                aria-label="Default select example">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        @if (in_array($category->id, $selectedCategoryIds)) selected @endif>{{ $category->name }}</option>
                                @endforeach
                            </select>

                            <div class="form--switch form--switch-sm mt-2">
                                <input class="form-check-input" id="AllCategories" name="is_all_categories"
                                    value="1" type="checkbox" @if (@$advertisement->is_all_categories == Status::YES) checked @endif>
                                <label class="form-check-label" for="AllCategories">
                                    @lang('All Categories')
                                </label>
                            </div>
                        </div>

                        <div
                            class="col-sm-6 select2-parent exceptCountry @if (empty(@$expectedCountries)) d-none @endif">
                            <label class="form--label"> @lang('Except Country') <span class="text--base"
                                    data-bs-toggle="tooltip" title="@lang('If you create ads for all countries except some specific ones, you can select them here')"><i
                                        class="las la-info-circle"></i></span> </label>

                            <select class="form-select form--control select2-basic" name="except_countries[]" multiple
                                aria-label="Default select example">
                                @foreach ($countries as $country)
                                    <option value="{{ $country->country }}"
                                        @if (in_array($country->country, $expectedCountries)) selected @endif>{{ $country->country }}
                                    </option>
                                @endforeach
                            </select>

                        </div>


                        <div
                            class="col-sm-6 select2-parent exceptCategory @if (@$advertisement->is_all_categories == Status::NO && !in_array($category->id, $selectedCategoryIds)) d-none @endif">
                            <label class="form--label"> @lang('Except Categories') <span class="text--base"
                                    data-bs-toggle="tooltip" title="@lang('If you create ads for all categories except some specific ones, you can select them here')"><i
                                        class="las la-info-circle"></i></span> </label>
                            <select class="form-select form--control select2-basic" name="except_categories[]" multiple
                                aria-label="Default select example">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        @if (!in_array($category->id, $selectedCategoryIds)) selected @endif>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>




                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@push('script')
    <script>
        $(document).ready(function() {

            $('[name="is_all_countries"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('[name="countries[]"]').prop('disabled', true);
                    $('[name="countries[]"]').val('').trigger('change');
                    $('[name="except_countries[]"]').prop('disabled', false);
                    $('.exceptCountry').removeClass('d-none');
                } else {
                    $('[name="countries[]"]').prop('disabled', false);
                    $('[name="except_countries[]"]').prop('disabled', true);
                    $('[name="except_countries[]"]').val('').trigger('change');
                    $('.exceptCountry').addClass('d-none');
                }
            }).trigger('change');

            $('[name="is_all_categories"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('[name="categories[]"]').prop('disabled', true);
                    $('[name="categories[]"]').val('').trigger('change');
                    $('[name="except_categories[]"]').prop('disabled', false);
                    $('.exceptCategory').removeClass('d-none');
                } else {
                    $('[name="categories[]"]').prop('disabled', false);
                    $('[name="except_categories[]"]').prop('disabled', true);
                    $('[name="except_categories[]"]').val('').trigger('change');
                    $('.exceptCategory').addClass('d-none');
                }
            }).trigger('change');
        });
    </script>
@endpush
