@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two custom-data-table">
                            <thead>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Slug')</th>
                                    <th>@lang('Icon')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $category)
                                    <tr>
                                        <td>{{ __($category->name) }}</td>
                                        <td>{{ __($category->slug) }}</td>
                                        <td>
                                            @php
                                                echo $category->icon;
                                            @endphp
                                        </td>
                                        <td>
                                            @php
                                                echo $category->statusBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <button class="btn btn-sm btn-outline--primary editBtn"
                                                    data-category="{{ $category }}"><i
                                                        class="las la-pencil-alt"></i>@lang('Edit')</button>
                                                @if (@$category->status)
                                                    <button class="btn btn-sm btn-outline--danger confirmationBtn"
                                                        data-action="{{ route('admin.category.status', $category->id) }}"
                                                        data-question="@lang('Are you sure want to disable this category?')"><i
                                                            class="las la-eye-slash"></i>@lang('Disable')</button>
                                                @else
                                                    <button class="btn btn-sm btn-outline--success confirmationBtn"
                                                        data-action="{{ route('admin.category.status', $category->id) }}"
                                                        data-question="@lang('Are you sure want to enable this category?')"><i
                                                            class="las la-eye"></i>@lang('Enable')</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($categories->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($categories) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>


    {{-- NEW MODAL --}}
    <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="createModalLabel"> @lang('Add New')</h4>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><i
                            class="las la-times"></i></button>
                </div>
                <form class="form-horizontal" method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <div class="d-flex justify-content-between">
                                <label>@lang(' Name')</label>
                                <a href="javescript:void(0)" class="buildSlug"><i
                                        class="las la-link"></i>@lang('Make Slug')</a>
                            </div>
                            <input type="text" class="form-control" value="{{ old('name') }}" name="name" required>
                        </div>

                        <div class="form-group">
                            <div class="d-flex justify-content-between">
                                <label>@lang('Slug')</label>
                                <div class="slug-verification d-none"></div>
                            </div>
                            <input type="text" class="form-control" value="{{ old('slug') }}" name="slug" required>
                        </div>
                        <div class="form-group">
                            <label>@lang('Icon')</label>
                            <div class="input-group">
                                <input type="text" class="form-control iconPicker icon" autocomplete="off" name="icon"
                                    value="{{ old('icon') }}" required>
                                <span class="input-group-text  input-group-addon" data-icon="las la-home"
                                    role="iconpicker"></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45" id="btn-save"
                            value="add">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder='Name' />
    <button type="button" class="btn btn-sm btn-outline--primary createBtn"><i
            class="las la-plus"></i>@lang('Add New')</button>
@endpush

@push('style')
    <style>
        .key-added {
            pointer-events: unset !important;
        }
    </style>
@endpush



@push('style-lib')
    <link href="{{ asset('assets/admin/css/fontawesome-iconpicker.min.css') }}" rel="stylesheet">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/fontawesome-iconpicker.js') }}"></script>
@endpush


@push('script')
    <script>
        (function($) {

            "use strict";


            $('.iconPicker').iconpicker().on('iconpickerSelected', function(e) {
                $(this).closest('.form-group').find('.iconpicker-input').val(
                    `<i class="${e.iconpickerValue}"></i>`);
            });


            $('.createBtn').on('click', function() {
                var modal = $('#createModal');
                const url = "{{ route('admin.category.save') }}"
                modal.find('form').attr('action', url);
                modal.find('.modal-title').text("@lang('Add Categroy')");

                modal.find('[name="name"]').val('')
                modal.find('[name="slug"]').val('')
                modal.find('[name="icon"]').val('');

                modal.modal('show');
            });

            $('.editBtn').on('click', function() {
                var modal = $('#createModal');
                var data = $(this).data('category');
                var url = "{{ route('admin.category.save') }}/" + data.id;
                modal.find('form').attr('action', url);
                modal.find('.modal-title').text("@lang('Edit Category')");
                modal.find('[name="name"]').val(data.name);
                modal.find('[name="slug"]').val(data.slug);
                modal.find('[name="icon"]').val(data.icon);
                modal.modal('show');
            });


            $('.buildSlug').on('click', function() {

                let closestForm = $(this).closest('form');
                let title = closestForm.find(`[name="name"]`).val();
                closestForm.find('[name=slug]').val(title);
                closestForm.find('[name=slug]').trigger('input');
            });



            $('[name=slug]').on('input', function() {
                let closestForm = $(this).closest('form');

                let slug = $(this).val();
                slug = slug.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');


                $(this).val(slug);
                if (slug) {
                    closestForm.find('.slug-verification').removeClass('d-none');
                    closestForm.find('.slug-verification').html(`
                            <small class="text--info"><i class="las la-spinner la-spin"></i> @lang('Verifying')</small>
                        `);
                    $.get("{{ route('admin.category.check.slug') }}", {
                        slug: slug
                    }, function(response) {
                        if (!response.exists) {
                            closestForm.find('.slug-verification').html(`
                                    <small class="text--success"><i class="las la-check"></i> @lang('Verified')</small>
                                `);
                        }
                        if (response.exists) {
                            closestForm.find('.slug-verification').html(`
                                    <small class="text--danger"><i class="las la-times"></i> @lang('Slug already exists')</small>
                                `);
                        }
                    });
                } else {
                    closestForm.find('.slug-verification').addClass('d-none');
                }
            });

        })(jQuery);
    </script>
@endpush
