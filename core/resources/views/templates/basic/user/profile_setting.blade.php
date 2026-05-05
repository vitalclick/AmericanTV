@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="setting-content">
        <h3 class="setting-content__title">@lang('Profile settings')</h3>
        <form action="" method="post" class="profile-setting-form row">
            @csrf
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form--label">@lang('First Name')</label>
                    <input type="text" class="form--control" name="firstname" required
                           value="{{ old('firstname', $user->firstname) }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form--label">@lang('Last Name')</label>
                    <input type="text" class="form--control" name="lastname" required
                           value="{{ old('lastname', $user->lastname) }}">
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label class="form--label">@lang('Bio')</label>
                    <textarea class="form--control" name="bio" required>{{ old('bio', $user->bio) }}</textarea>
                </div>
            </div>

            <div class="col-12">
                <div class="form-group">
                    <label class="form--label">@lang('Facebook')</label>
                    <input type="url" class="form--control" name="social_links[facebook]"
                           value="{{ old('social_links[facebook]', @$user->social_links?->facebook) }}" placeholder="URL">
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label class="form--label">@lang('X')</label>
                    <input type="url" class="form--control" name="social_links[twitter]"
                           value="{{ old('social_links[twitter]', @$user->social_links?->twitter) }}" placeholder="URL">
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label class="form--label">@lang('Instagram')</label>
                    <input type="url" class="form--control" name="social_links[instragram]"
                           value="{{ old('social_links[instragram]', @$user->social_links?->instragram) }}" placeholder="URL">
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label class="form--label">@lang('Threads')</label>
                    <input type="url" class="form--control" name="social_links[threads]"
                           value="{{ old('social_links[threads]', @$user->social_links?->threads) }}" placeholder="URL">
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label class="form--label">@lang('Descord')</label>
                    <input type="url" class="form--control" name="social_links[descord]"
                           value="{{ old('social_links[descord]', @$user->social_links?->descord) }}" placeholder="URL">
                </div>
            </div>
            <div class="col-12">
                <div class="form-group">
                    <label class="form--label">@lang('Tiktok')</label>
                    <input type="url" class="form--control" name="social_links[tiktok]"
                           value="{{ old('social_links[tiktok]', @$user->social_links?->tiktok) }}" placeholder="URL">
                </div>
            </div>


            <div class="col-12">
                <div class="form-group text-end mb-0">
                    <button type="submit" class="btn btn--base btn--lg">@lang('Save')</button>
                </div>
            </div>
        </form>
    </div>
@endsection
