@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="setting-content">
        <div class="change-password">
            <a href="{{ route('user.setting.security') }}" class="change-password__back"><i class="vti-left-long"></i></a>
            <h5 class="title">{{ __($pageTitle) }}</h5>

            <form method="post">
                @csrf
                <div class="form-group">
                    <label class="form--label">@lang('Current Password')</label>
                    <input type="password" class=" form--control" name="current_password" required
                           placeholder="Current password">
                </div>
                <div class="form-group">
                    <label class="form--label">@lang('Password')</label>
                    <input type="password" class="form--control @if (gs('secure_password')) secure-password @endif"
                           name="password" required placeholder="New password">
                </div>
                <div class="form-group">
                    <label class="form--label">@lang('Confirm Password')</label>
                    <input type="password" class=" form--control" name="password_confirmation" required
                           placeholder="Confirm password">
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn--base btn--lg">@lang('Submit')</button>
                </div>
            </form>

        </div>
    </div>
@endsection
@if (gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif

@push('style')
 <style>
    .hover-input-popup .input-popup {
    bottom: 73%;
}
</style>   
@endpush