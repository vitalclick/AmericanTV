@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-12">
            <div class="row gy-4">

                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="7" link="{{ route('admin.report.transaction', $user->id) }}" title="Balance"
                              icon="las la-money-bill-wave-alt" value="{{ showAmount($user->balance) }}" bg="indigo" type="2" />
                </div>


                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="7" link="{{ route('admin.deposit.list', $user->id) }}" title="Deposits"
                              icon="las la-wallet" value="{{ showAmount($totalDeposit) }}" bg="8" type="2" />
                </div>

                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="7" link="{{ route('admin.withdraw.data.all', $user->id) }}" title="Withdrawals"
                              icon="la la-bank" value="{{ showAmount($totalWithdrawals) }}" bg="6" type="2" />
                </div>

                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="7" link="{{ route('admin.report.transaction', $user->id) }}" title="Transactions"
                              icon="las la-exchange-alt" value="{{ $totalTransaction }}" bg="17" type="2" />
                </div>



                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="6" link="{{ route('admin.subscriber.index', $user->id) }}" title="Subscriber"
                              icon="las la-bell" value="{{ $widget['totalSubscriber'] }}" bg="success" type="2" />
                </div>


                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="6" link="{{ route('admin.videos.index', $user->id) }}" title="Total Videos"
                              icon="las la-video" value="{{ $widget['totalVideos'] }}" bg="8" type="2" />
                </div>

                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="6" link="{{ route('admin.videos.regular', $user->id) }}" title="Regular Videos"
                              icon="la la-file-video" value="{{ $widget['totalRegularVideos'] }}" bg="6" type="2" />
                </div>

                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="6" link="{{ route('admin.videos.shorts', $user->id) }}" title="Shorts Videos"
                              icon="las la-play" value="{{ $widget['totalShortsVideos'] }}" bg="17" type="2" />
                </div>



                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="6" outline="true" link="{{ route('admin.videos.public', $user->id) }}" title="Public Videos"
                              icon="las la-eye" value="{{ $widget['totalPublicVideos'] }}" bg="success" type="2" />
                </div>

                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="6" outline="true" link="{{ route('admin.videos.private', $user->id) }}" title="Private Videos"
                              icon="las la-video-slash" value="{{ $widget['totalPrivateVideos'] }}" bg="8" type="2" />
                </div>

                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="6" outline="true" link="{{ route('admin.videos.stock', $user->id) }}" title="Stock Videos"
                              icon="la la-hand-holding-usd" value="{{ $widget['totalStockVideos'] }}" bg="6" type="2" />
                </div>

                <div class="col-xxl-3 col-sm-6">
                    <x-widget style="6" outline="true" link="{{ route('admin.videos.free', $user->id) }}" title="Free Videos"
                              icon="lab la-youtube" value="{{ $widget['totalFreeVideos'] }}" bg="17" type="2" />
                </div>

            </div>

            <div class="d-flex flex-wrap gap-3 mt-4">
                <div class="flex-fill">
                    <a href="{{ route('admin.report.login.history') }}?search={{ $user->username }}"
                       class="btn btn--primary btn--shadow w-100 btn-lg">
                        <i class="las la-list-alt"></i>@lang('Logins')
                    </a>
                </div>

                <div class="flex-fill">
                    <a href="{{ route('admin.users.notification.log', $user->id) }}"
                       class="btn btn--secondary btn--shadow w-100 btn-lg">
                        <i class="las la-bell"></i>@lang('Notifications')
                    </a>
                </div>

                @if ($user->kyc_data)
                    <div class="flex-fill">
                        <a href="{{ route('admin.users.kyc.details', $user->id) }}" target="_blank"
                           class="btn btn--dark btn--shadow w-100 btn-lg">
                            <i class="las la-user-check"></i>@lang('KYC Data')
                        </a>
                    </div>
                @endif

                @if ($user->monetization_status != Status::MONETIZATION_INITIATE)
                    <div class="flex-fill">
                        <a href="{{ route('admin.users.monetization.detail', $user->id) }}" target="_blank"
                           class="btn btn--info btn--shadow w-100 btn-lg">
                            <i class="las la-user-check"></i>@lang('Monetization Data')
                        </a>
                    </div>
                @endif



                <div class="flex-fill">
                    @if ($user->status == Status::USER_ACTIVE)
                        <button type="button" class="btn btn--warning btn--gradi btn--shadow w-100 btn-lg userStatus"
                                data-bs-toggle="modal" data-bs-target="#userStatusModal">
                            <i class="las la-ban"></i>@lang('Ban User')
                        </button>
                    @else
                        <button type="button" class="btn btn--success btn--gradi btn--shadow w-100 btn-lg userStatus"
                                data-bs-toggle="modal" data-bs-target="#userStatusModal">
                            <i class="las la-undo"></i>@lang('Unban User')
                        </button>
                    @endif
                </div>
            </div>
            <form action="{{ route('admin.users.update', [$user->id]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row gy-4">
                    <div class="col-md-12">
                        <div class="card mt-30">
                            <div class="card-header">
                                <h5 class="card-title mb-0">@lang('Channel Information of') {{ __($user->fullname) }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-4 col-md-6">
                                        <label>@lang('Image')</label>
                                        <x-image-uploader name="image" :imagePath="getImage(getFilePath('userProfile') . '/' . $user->image)" :size="getFileSize('userProfile')" class="w-100" id="image" :required="false" />
                                    </div>
                                    <div class="col-xl-9 col-lg-8 col-md-6">
                                        <label>@lang('Cover Image')</label>
                                        <x-image-uploader name="cover_image" :imagePath="getImage(getFilePath('cover') . '/' . $user->cover_image)" :size="getFileSize('cover')" class="w-100" id="coverImage" :required="false" />

                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>@lang('Channel Name') </label>
                                    <input class="form-control" type="text" name="channel_name"
                                           value="{{ __(@$user->channel_name) }}" required>
                                </div>

                                <div class="form-group">
                                    <label>@lang('Channel Description') </label>
                                    <textarea class="form-control nicEdit" type="text" name="channel_name" cols="30" rows="10">{{ __($user->channel_description) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">@lang('Information of') {{ $user->fullname }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('First Name')</label>
                                            <input class="form-control" type="text" name="firstname" required
                                                   value="{{ $user->firstname }}">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-control-label">@lang('Last Name')</label>
                                            <input class="form-control" type="text" name="lastname" required
                                                   value="{{ $user->lastname }}">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Email')</label>
                                            <input class="form-control" type="email" name="email" value="{{ $user->email }}"
                                                   required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Mobile Number')</label>
                                            <div class="input-group ">
                                                <span class="input-group-text mobile-code">+{{ $user->dial_code }}</span>
                                                <input type="number" name="mobile" value="{{ $user->mobile }}" id="mobile"
                                                       class="form-control checkUser" required>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="col-md-12">
                                        <div class="form-group ">
                                            <label>@lang('Address')</label>
                                            <input class="form-control" type="text" name="address" value="{{ @$user->address }}">
                                        </div>
                                    </div>

                                    <div class=" col-md-6">
                                        <div class="form-group">
                                            <label>@lang('City')</label>
                                            <input class="form-control" type="text" name="city" value="{{ @$user->city }}">
                                        </div>
                                    </div>

                                    <div class=" col-md-6">
                                        <div class="form-group ">
                                            <label>@lang('State')</label>
                                            <input class="form-control" type="text" name="state" value="{{ @$user->state }}">
                                        </div>
                                    </div>

                                    <div class=" col-md-6">
                                        <div class="form-group ">
                                            <label>@lang('Zip/Postal')</label>
                                            <input class="form-control" type="text" name="zip" value="{{ @$user->zip }}">
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-md-6">
                                        <div class="form-group ">
                                            <label>@lang('Country') <span class="text--danger">*</span></label>
                                            <select name="country" class="form-control select2">
                                                @foreach ($countries as $key => $country)
                                                    <option data-mobile_code="{{ $country->dial_code }}" value="{{ $key }}"
                                                            @selected($user->country_code == $key)>{{ __($country->country) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class=" col-md-3">
                                        <div class="form-group">
                                            <label>@lang('Email Verification')</label>
                                            <input type="checkbox" data-width="100%" data-onstyle="-success" data-offstyle="-danger"
                                                   data-bs-toggle="toggle" data-on="@lang('Verified')" data-off="@lang('Unverified')"
                                                   name="ev" @if ($user->ev) checked @endif>
                                        </div>
                                    </div>

                                    <div class=" col-md-3">
                                        <div class="form-group">
                                            <label>@lang('Mobile Verification')</label>
                                            <input type="checkbox" data-width="100%" data-onstyle="-success" data-offstyle="-danger"
                                                   data-bs-toggle="toggle" data-on="@lang('Verified')" data-off="@lang('Unverified')"
                                                   name="sv" @if ($user->sv) checked @endif>
                                        </div>
                                    </div>
                                    <div class=" col-md-3">
                                        <div class="form-group">
                                            <label>@lang('2FA Verification') </label>
                                            <input type="checkbox" data-width="100%" data-height="50" data-onstyle="-success"
                                                   data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('Enable')"
                                                   data-off="@lang('Disable')" name="ts" @if ($user->ts) checked @endif>
                                        </div>
                                    </div>
                                    <div class=" col-md-3">
                                        <div class="form-group">
                                            <label>@lang('KYC') </label>
                                            <input type="checkbox" data-width="100%" data-height="50" data-onstyle="-success"
                                                   data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('Verified')"
                                                   data-off="@lang('Unverified')" name="kv" @if ($user->kv == Status::KYC_VERIFIED) checked @endif>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 mt-30">
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>






    <div id="userStatusModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if ($user->status == Status::USER_ACTIVE)
                            @lang('Ban User')
                        @else
                            @lang('Unban User')
                        @endif
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.users.status', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if ($user->status == Status::USER_ACTIVE)
                            <h6 class="mb-2">@lang('If you ban this user he/she won\'t able to access his/her dashboard.')</h6>
                            <div class="form-group">
                                <label>@lang('Reason')</label>
                                <textarea class="form-control" name="reason" rows="4" required></textarea>
                            </div>
                        @else
                            <p><span>@lang('Ban reason was'):</span></p>
                            <p>{{ $user->ban_reason }}</p>
                            <h4 class="text-center mt-3">@lang('Are you sure to unban this user?')</h4>
                        @endif
                    </div>
                    <div class="modal-footer">
                        @if ($user->status == Status::USER_ACTIVE)
                            <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                        @else
                            <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('No')</button>
                            <button type="submit" class="btn btn--primary">@lang('Yes')</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.users.login', $user->id) }}" target="_blank" class="btn btn-sm btn-outline--primary"><i
           class="las la-sign-in-alt"></i>@lang('Login as User')</a>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict"




            let mobileElement = $('.mobile-code');
            $('select[name=country]').on('change', function() {
                mobileElement.text(`+${$('select[name=country] :selected').data('mobile_code')}`);
            });

        })(jQuery);
    </script>
@endpush
