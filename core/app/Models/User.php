<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\UserNotify;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, UserNotify;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'ver_code', 'balance', 'kyc_data',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'kyc_data'          => 'object',
        'advertiser_data'   => 'object',
        'social_links'      => 'object',
        'ver_code_send_at'  => 'datetime',
    ];

    public function loginLogs() {
        return $this->hasMany(UserLogin::class);
    }

    public function transactions() {
        return $this->hasMany(Transaction::class)->orderBy('id', 'desc');
    }

    public function deposits() {
        return $this->hasMany(Deposit::class)->where('status', '!=', Status::PAYMENT_INITIATE);
    }

    public function withdrawals() {
        return $this->hasMany(Withdrawal::class)->where('status', '!=', Status::PAYMENT_INITIATE);
    }

    public function tickets() {
        return $this->hasMany(SupportTicket::class);
    }

    public function videos() {
        return $this->hasMany(Video::class);
    }

    public function subscribers() {
        return $this->hasMany(Subscriber::class);
    }

    public function subscriptions() {
        return $this->hasMany(Subscriber::class, 'following_id');
    }

    public function videoImpression() {
        return $this->hasMany(Impression::class);
    }

    public function watchHistories() {
        return $this->hasMany(WatchHistory::class);
    }

    public function purchasedVideos() {
        return $this->hasMany(PurchasedVideo::class);
    }

    public function saleVideos() {
        return $this->hasMany(PurchasedVideo::class, 'owner_id');
    }

    public function purchasedPlaylists() {
        return $this->hasMany(PurchasedPlaylist::class);
    }

    public function purchasedPlans() {
        return $this->hasMany(PurchasedPlan::class);
    }

    public function hasValidPlan($planId) {
        $purchase = $this->purchasedPlans()->where('plan_id', $planId)->where('user_id', auth()->id())->latest()->first();

        if (!$purchase) {
            return false;
        }

        return now()->lt($purchase->expired_date);
    }

    public function salePlaylists() {
        return $this->hasMany(PurchasedPlaylist::class, 'owner_id');
    }

    public function salePlans() {
        return $this->hasMany(PurchasedPlan::class, 'owner_id');
    }

    public function watchLaters() {
        return $this->hasMany(WatchLater::class);
    }

    public function withdrawSetting() {
        return $this->belongsTo(WithdrawSetting::class, 'id', 'user_id');
    }

    public function advertisements() {
        return $this->hasMany(Advertisement::class);
    }

    public function userLikes() {
        return $this->hasMany(UserReaction::class)->where('is_like', Status::YES)->where('video_id', '!=', 0);
    }

    public function userDislikes() {
        return $this->hasMany(UserReaction::class)->where('is_like', Status::NO)->where('video_id', '!=', 0);
    }

    public function fullname(): Attribute {
        return new Attribute(
            get: fn() => $this->firstname . ' ' . $this->lastname,
        );
    }

    public function mobileNumber(): Attribute {
        return new Attribute(
            get: fn() => $this->dial_code . $this->mobile,
        );
    }
    public function purchasedVideoId(): Attribute {
        return new Attribute(
            get: fn() => $this->purchasedVideos->pluck('video_id')->toArray(),
        );
    }
    public function purchasedPlaylistId(): Attribute {
        return new Attribute(
            get: fn() => $this->purchasedPlaylists->pluck('playlist_id')->toArray(),
        );
    }
    public function watchLatterVideoId(): Attribute {
        return new Attribute(
            get: fn() => $this->watchLaters->pluck('video_id')->toArray(),
        );
    }

    // SCOPES
    public function scopeActive($query) {
        return $query->where('status', Status::USER_ACTIVE)->where('ev', Status::VERIFIED)->where('sv', Status::VERIFIED);
    }

    public function scopeBanned($query) {
        return $query->where('status', Status::USER_BAN);
    }

    public function scopeEmailUnverified($query) {
        return $query->where('ev', Status::UNVERIFIED);
    }

    public function scopeMobileUnverified($query) {
        return $query->where('sv', Status::UNVERIFIED);
    }

    public function scopeKycUnverified($query) {
        return $query->where('kv', Status::KYC_UNVERIFIED);
    }

    public function scopeKycPending($query) {
        return $query->where('kv', Status::KYC_PENDING);
    }

    public function scopeEmailVerified($query) {
        return $query->where('ev', Status::VERIFIED);
    }

    public function scopeMobileVerified($query) {
        return $query->where('sv', Status::VERIFIED);
    }

    public function scopeWithBalance($query) {
        return $query->where('balance', '>', 0);
    }

    public function scopeMonetizationRequest($query) {
        return $query->where('monetization_status', Status::MONETIZATION_APPLYING);
    }

    public function scopeMonetizationApproved($query) {
        return $query->where('monetization_status', Status::MONETIZATION_APPROVED);
    }

    public function scopePendingAdvertisers($query) {
        return $query->where('advertiser_status', Status::ADVERTISER_PENDING);
    }

    public function scopeApprovedAdvertisers($query) {
        return $query->where('advertiser_status', Status::ADVERTISER_APPROVED);
    }

    public function scopeRejectedAdvertisers($query) {
        return $query->where('advertiser_status', Status::ADVERTISER_REJECTED);
    }

    public function deviceTokens() {
        return $this->hasMany(DeviceToken::class);
    }

    public function isSubscribe() {
        $subscriptions = $this->subscriptions()->pluck('following_id')->toArray();
        return $subscriptions;
    }

    public function advertiseStatus(): Attribute {

        return new Attribute(function () {
            $html = '';
            if ($this->advertiser_status == Status::ADVERTISER_APPROVED) {
                $html = '<span class="badge badge--success">' . trans('Approved') . '</span>';
            } else if ($this->advertiser_status == Status::ADVERTISER_PENDING) {
                $html = '<span class="badge badge--warning">' . trans('Pending') . '</span>';
            } else {
                $html = '<span class="badge badge--danger">' . trans('Rejected') . '</span>';
            }
            return $html;
        });
    }

    public function monetizationStep(): Attribute {

        return new Attribute(function () {
            $html = '';
            if ($this->monetization_status == Status::MONETIZATION_APPLYING) {
                $html = '<span class="badge badge--warning">' . trans('Applying') . '</span>';
            } else if ($this->monetization_status == Status::MONETIZATION_APPROVED) {
                $html = '<span class="badge badge--success">' . trans('Active') . '</span>';
            } else if ($this->monetization_status == Status::MONETIZATION_CANCEL) {
                $html = '<span class="badge badge--danger">' . trans('Rejected') . '</span>';
            }
            return $html;
        });
    }

}
