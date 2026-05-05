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
                                    <th>@lang('Title')</th>
                                    <th>@lang('User')</th>
                                    <th>@lang('Visibility')</th>
                                    <th>@lang('Number of Videos')</th>
                                    <th>@lang('Subscription')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($playlists as $playlist)
                                    <tr>
                                        <td>{{ __($playlist->title) }}</td>
                                        <td>
                                            {{ __($playlist->user?->fullname) }} <br>
                                            <a href="{{ route('admin.users.detail', $playlist->user_id) }}">
                                                <span>@</span>{{ $playlist->user?->username }}
                                            </a>
                                        </td>
                                        <td>
                                            @php
                                                echo $playlist->statusBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            {{ $playlist->videos->count() }}
                                        </td>
                                        <td>
                                            @if ($playlist->playlist_subscription)
                                                <span class="badge badge--success">@lang('Yes')</span>
                                            @else
                                                <span class="badge badge--dark">@lang('No')</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $playlist->price > 0 ? showAmount($playlist->price) : '----' }}
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <button class="btn btn-sm btn-outline--primary editPlaylist"
                                                    data-playlist="{{ $playlist }}"
                                                    data-action="{{ route('admin.playlist.update', $playlist->id) }}">
                                                    <i class="las la-pencil-alt"></i>@lang('Edit')
                                                </button>
                                                <a href="{{ route('admin.playlist.videos.list', $playlist->id) }}"
                                                    class="btn btn-sm btn-outline--info">
                                                    <i class="las la-video"></i>@lang('Video List')
                                                </a>
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
                @if ($playlists->hasPages())
                    <div class="card-footer py-4">
                        @php echo paginateLinks($playlists) @endphp
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>


    {{-- NEW MODAL --}}
    <div class="modal custom--modal top-slide" id="playlistModal" tabindex="-1" aria-labelledby="playlistModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close modal-close-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form method="post" class="playlistForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="" class="form--label">@lang('Title')</label>
                            <input class="form-control" type="text" name="title" value="{{ old('title') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="" class="form--label">@lang('Description')</label>
                            <textarea name="description" class="form-control" cols="30" rows="10">{{ old('description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="" class="form--label">@lang('Visibility')</label>

                            <div class="check-type-wrapper">
                                <label for="category01" class="check-type check-type-success">
                                    <input class="check-type-input" type="radio" value="0" name="visibility"
                                        id="category01">
                                    <span class="check-type-label">@lang('Public')</span>
                                </label>
                                <label for="category02" class="check-type check-type-warning">
                                    <input class="check-type-input" type="radio" value="1" name="visibility"
                                        id="category02">
                                    <span class="check-type-label">@lang('Private')</span>
                                </label>
                            </div>
                        </div>


                        @if (gs('is_playlist_sell'))
                            <div class="form-group">

                                <div class="form-group">
                                    <label for="playlist_subscription">@lang('Playlist Purchase')</label>
                                    <input type="checkbox" class="playlist-subscription-toggle" data-width="100%"
                                        data-size="large" data-onstyle="-success" data-offstyle="-danger"
                                        data-bs-toggle="toggle" data-height="35" data-on="@lang('Enable')"
                                        data-off="@lang('Disable')" id="playlist_subscription"
                                        name="playlist_subscription">
                                </div>
                            </div>

                            <div class="form-group stock-price">
                                <label class="form--label">@lang('Playlist Price')</label>
                                <div class="input-group">
                                    <input class="form--control form-control" name="price" type="number"
                                        placeholder="Price" step="any">
                                    <span class="input-group-text btn--base">{{ __(gs('cur_text')) }}</span>
                                </div>
                            </div>
                        @endif

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Update')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form placeholder='Title/username' />
@endpush

@push('script')
    <script>
        (function($) {

            "use strict";

            $('#playlist_subscription').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.stock-price').show();
                } else {
                    $('.stock-price').hide();
                }
            });

            $('.editPlaylist').on('click', function() {
                const modal = $('#playlistModal');
                var data = $(this).data('playlist');

                const url = $(this).data('action');
                modal.find('form').attr('action', url);
                modal.find('[name="title"]').val(data.title);
                modal.find('[name="description"]').val(data.description);

                modal.find('[name="visibility"]').prop('checked', false); // Uncheck all first
                modal.find(`[name="visibility"][value="${data.visibility}"]`).prop('checked', true);

                modal.find('.modal-title').text('@lang('Edit playlist')');

                modal.find('#playlist_subscription').prop('checked', data.playlist_subscription == 1);
                modal.find('[name="price"]').val(parseFloat(data.price).toFixed(2));

                modal.find('#playlist_subscription').bootstrapToggle();

                if (data.playlist_subscription == 1) {
                    modal.find('#playlist_subscription').bootstrapToggle('on');
                    $('.stock-price').show();
                } else {
                    modal.find('#playlist_subscription').bootstrapToggle('off');
                    $('.stock-price').hide();
                }

                modal.modal('show');
            });

        })(jQuery);
    </script>
@endpush
