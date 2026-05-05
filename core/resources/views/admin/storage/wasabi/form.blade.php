@extends('admin.layouts.app')
@section('panel')
 
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{route('admin.storage.save.wasabi', @$wasabi->id)}}" method="post">
                    @csrf
                    <div class="card-body">
                        <div class="row config">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Name')</label>
                                    <input class="form-control" type="text" name="name" value="{{@$wasabi->name}}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Driver')</label>
                                    <input class="form-control" type="text" name="wasabi[driver]" value="{{@$wasabi->config?->driver}}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required"> @lang('Key')</label>
                                    <input class="form-control" type="text" name="wasabi[key]" value="{{@$wasabi->config?->key}}" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Secret')</label>
                                    <input class="form-control" type="text" name="wasabi[secret]" value="{{@$wasabi->config?->secret}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Region')</label>
                                    <input class="form-control" type="text" name="wasabi[region]" value="{{@$wasabi->config?->region}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Bucket')</label>
                                    <input class="form-control" type="text" name="wasabi[bucket]" value="{{@$wasabi->config?->bucket}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('Endpoint')</label>
                                    <input class="form-control" type="text" name="wasabi[endpoint]" value="{{@$wasabi->config?->endpoint}}"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('Available Space')</label>
                                    <div class="input-group">
                                        <input class="form-control" type="text" name="available_space" value="{{@$wasabi->available_space}}"
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
