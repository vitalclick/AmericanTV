@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Title')</th>
                                    <th>@lang('Username / Channel Name')</th>
                                    <th>@lang('Video Type')</th>
                                    <th>@lang('Visibility')</th>
                                    <th>@lang('Stock Video')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('Step Complete')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($videos as $video)
                                    <tr>

                                        <td>{{ __(strLimit($video->title, 30)) }}</td>
                                        <td>

                                            <a href="{{ route('admin.users.detail', @$video->user->id) }}">
                                                <span>@</span>{{ __(@$video->user->username) }} <br>
                                            </a>

                                            <span>
                                                {{ __(@$video->user->channel_name) }}

                                            </span>
                                        </td>
                                        <td>
                                            @if ($video->is_shorts_video)
                                                <span>@lang('Shorts')</span>
                                            @else
                                                <span>@lang('Reguler')</span>
                                            @endif

                                        </td>
                                        <td>
                                            @php
                                                echo $video->visibilityStatus;
                                            @endphp

                                        </td>
                                        <td>
                                            @if ($video->stock_video)
                                                <span class="badge badge--warning">@lang('Yes')</span>
                                            @else
                                                <span class="badge badge--primary">@lang('No')</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($video->stock_video)
                                                <span class="text-muted">{{ showAmount($video->price) }}</span>
                                            @else
                                                <span class="text-muted">@lang('N/A') </span>
                                            @endif
                                        </td>



                                        <td>
                                            @if ($video->is_shorts_video)
                                                <span class="text-info">{{ $video->step }}</span> @lang('of') <span class="text--success">3</span>
                                            @else
                                                <span class="text-info">{{ $video->step }}</span> @lang('of') <span class="text--success">4</span>
                                            @endif

                                        </td>
                                        <td>
                                            @php
                                                echo $video->statusBadge;
                                            @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <a href="{{ route('admin.videos.edit', @$video->id) }}" class="btn btn-outline--primary btn-sm"><i class="la la-pencil"></i>@lang('Edit')</a>
                                                <a href="@if ($video->status != Status::YES) javascript:void(0) @else{{ route('admin.videos.analytics', @$video->id) }} @endif" class="btn btn-outline--info btn-sm @if ($video->status != Status::YES) disabled @endif"><i class="las la-chart-pie"></i>@lang('Analytics')</a>
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
                @if ($videos->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($videos) }}
                    </div>
                @endif
            </div>
        </div>


    </div>
@endsection



@push('breadcrumb-plugins')
    <x-search-form placeholder="Username/Title/Channel Name " />
@endpush
