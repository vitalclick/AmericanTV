<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 routes
|--------------------------------------------------------------------------
| Mounted at /api/v1 from bootstrap/app.php. Sanctum-token authentication;
| no session, no CSRF. Controllers live under App\Http\Controllers\Api\V1.
|
| This file declares the surface — most controller methods are not yet
| implemented and will be filled in during Phase 0/1 of the mobile API rollout.
| Inventory is the source of truth; see core/docs/api/openapi-v1.yaml.
*/

Route::group([], function () {
    // -------------------- Public --------------------
    Route::prefix('auth')->group(function () {
        Route::post('register', 'AuthController@register');
        Route::post('login', 'AuthController@login');
        Route::post('forgot-password', 'AuthController@forgotPassword');
        Route::post('verify-code', 'AuthController@verifyCode');
        Route::post('reset-password', 'AuthController@resetPassword');
        Route::post('social/{provider}', 'AuthController@socialLogin');
    });

    Route::get('feed', 'DiscoveryController@feed');
    Route::get('videos', 'DiscoveryController@listVideos');
    Route::get('videos/{slug}', 'DiscoveryController@showVideo');
    Route::get('shorts', 'DiscoveryController@listShorts');
    Route::get('categories', 'DiscoveryController@categories');
    Route::get('channels/{slug}', 'DiscoveryController@channel');
    Route::get('playlists/{slug}', 'DiscoveryController@playlist');
    Route::get('playlists/{slug}/videos', 'DiscoveryController@playlistVideos');
    Route::get('plans/{slug}', 'DiscoveryController@plan');

    // -------------------- Authenticated --------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', 'AuthController@logout');
        Route::post('auth/refresh', 'AuthController@refresh');
        Route::post('auth/email/send', 'AuthController@sendEmailCode');
        Route::post('auth/email/verify', 'AuthController@verifyEmail');
        Route::post('auth/mobile/send', 'AuthController@sendMobileCode');
        Route::post('auth/mobile/verify', 'AuthController@verifyMobile');
        Route::post('auth/2fa/verify', 'AuthController@verify2fa');

        // Playback (signed source URLs + view tracking)
        Route::get('videos/{id}/source', 'PlaybackController@source');
        Route::get('shorts/{id}/source', 'PlaybackController@shortsSource');
        Route::post('videos/{id}/view', 'PlaybackController@recordView');

        // Engagement
        Route::post('videos/{id}/reaction', 'EngagementController@reactToVideo');
        Route::get('videos/{id}/comments', 'EngagementController@listComments');
        Route::post('videos/{id}/comments', 'EngagementController@postComment');
        Route::post('comments/{id}/reply', 'EngagementController@replyToComment');
        Route::post('comments/{id}/reaction', 'EngagementController@reactToComment');
        Route::post('channels/{id}/subscribe', 'EngagementController@subscribeChannel');

        Route::get('watch-later', 'EngagementController@listWatchLater');
        Route::post('watch-later/{videoId}', 'EngagementController@addWatchLater');
        Route::delete('watch-later/{videoId}', 'EngagementController@removeWatchLater');

        Route::get('history', 'EngagementController@listHistory');
        Route::delete('history/{videoId}', 'EngagementController@removeHistory');
        Route::delete('history', 'EngagementController@clearHistory');

        // Account
        Route::get('me', 'MeController@show');
        Route::patch('me/profile', 'MeController@updateProfile');
        Route::patch('me/security/password', 'MeController@changePassword');
        Route::get('me/wallet', 'MeController@wallet');
        Route::get('me/transactions', 'MeController@transactions');
        Route::get('me/earnings', 'MeController@earnings');
        Route::get('me/notifications', 'MeController@notifications');
        Route::post('me/notifications/{id}/read', 'MeController@markNotificationRead');
        Route::post('me/notifications/read-all', 'MeController@markAllNotificationsRead');

        // Push tokens (writes to existing DeviceToken model)
        Route::post('me/device-tokens', 'MeController@registerDeviceToken');
        Route::delete('me/device-tokens', 'MeController@unregisterDeviceToken');

        // Purchases — the only mobile purchase entrypoint.
        Route::post('purchases/iap/verify', 'IapPurchaseController@verify');
        Route::post('purchases/iap/restore', 'IapPurchaseController@restore');
    });

    // -------------------- Server-to-server webhooks --------------------
    // Verified via platform signature inside the controller, not Sanctum.
    Route::post('webhooks/apple-notifications', 'Webhooks\AppleNotificationsController@handle');
    Route::post('webhooks/google-rtdn', 'Webhooks\GoogleRtdnController@handle');
    Route::post('webhooks/cloudflare-stream', 'Webhooks\CloudflareStreamController@handle');
    Route::post('webhooks/revenuecat', 'Webhooks\RevenueCatWebhookController@handle');
});
