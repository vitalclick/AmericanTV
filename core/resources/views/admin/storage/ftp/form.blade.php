@extends('admin.layouts.app')
@section('panel')

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{route('admin.storage.save.ftp', @$ftp->id)}}" method="post">
                    @csrf
                    <div class="card-body">
                        <div class="row config">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Name')</label>
                                    <input class="form-control" type="text" name="name" value="{{@$ftp->name}}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('FTP Hosting Root Access Path')</label>
                                    <input class="form-control" type="text" name="ftp[host_domain]" placeholder="@lang('https://yourdomain.com/foldername')"
                                           value="{{@$ftp->config?->host_domain}}" required>
                                    <small class="text-danger">@lang('https://yourdomain.com/foldername')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required"> @lang('Host')</label>
                                    <input class="form-control" type="text" name="ftp[host]" placeholder="@lang('Host')"
                                           value="{{@$ftp->config?->host}}" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Username')</label>
                                    <input class="form-control" type="text" name="ftp[username]" placeholder="@lang('Username')"
                                           value="{{@$ftp->config?->username}}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Password')</label>
                                    <input class="form-control" type="text" name="ftp[password]" placeholder="@lang('Password')"
                                           value="{{@$ftp->config?->password}}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="required">@lang('Port')</label>
                                    <input class="form-control" type="text" name="ftp[port]" placeholder="@lang('Port')"
                                           value="{{@$ftp->config?->port}}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">@lang('Upload Root Folder')</label>
                                    <input class="form-control" type="text" name="ftp[root_path]" placeholder="@lang('/html_public/something')" value="{{@$ftp->config?->root_path}}" required>
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