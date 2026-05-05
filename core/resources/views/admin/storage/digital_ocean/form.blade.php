@extends('admin.layouts.app')
@section('panel')

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{route('admin.storage.save.digital.ocean', @$digitalOcean->id)}}" method="post">
                    @csrf
                    <div class="card-body">
                        <div class="row config">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Name')</label>
                                    <input class="form-control" type="text" name="name" value="{{@$digitalOcean->name}}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Driver')</label>
                                    <input class="form-control" type="text" name="digital_ocean[driver]" value="{{@$digitalOcean->config?->driver}}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required"> @lang('Key')</label>
                                    <input class="form-control" type="text" name="digital_ocean[key]" value="{{@$digitalOcean->config?->key}}" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Secret')</label>
                                    <input class="form-control" type="text" name="digital_ocean[secret]" value="{{@$digitalOcean->config?->secret}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Region')</label>
                                    <input class="form-control" type="text" name="digital_ocean[region]" value="{{@$digitalOcean->config?->region}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Bucket')</label>
                                    <input class="form-control" type="text" name="digital_ocean[bucket]" value="{{@$digitalOcean->config?->bucket}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('Endpoint')</label>
                                    <input class="form-control" type="text" name="digital_ocean[endpoint]" value="{{@$digitalOcean->config?->endpoint}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('Available Space')</label>
                                    <div class="input-group">
                                        <input class="form-control" type="text" name="available_space" value="{{@$ftp->available_space}}"
                                            required>
                                            <div class="input-group-text">
                                                  MB
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </div>
                </form>
            </div><!-- card end -->
        </div>
    </div>
@endsection
@push('breadcrumb-plugins')
    <x-back route="{{route('admin.storage.index')}}"/>
@endpush