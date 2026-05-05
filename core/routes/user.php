<?php

use Illuminate\Support\Facades\Route;

Route::namespace('User\Auth')
    ->name('user.')
    ->middleware('guest')
    ->group(function () {
        Route::controller('LoginController')->group(function () {
            Route::get('/login', 'showLoginForm')->name('login');
            Route::post('/login', 'login');
            Route::get('logout', 'logout')->middleware('auth')->withoutMiddleware('guest')->name('logout');
        });

        Route::controller('RegisterController')->group(function () {
            Route::get('register', 'showRegistrationForm')->name('register');
            Route::post('register', 'register');
            Route::post('check-user', 'checkUser')->name('checkUser')->withoutMiddleware('guest');
        });

        Route::controller('ForgotPasswordController')
            ->prefix('password')
            ->name('password.')
            ->group(function () {
                Route::get('reset', 'showLinkRequestForm')->name('request');
                Route::post('email', 'sendResetCodeEmail')->name('email');
                Route::get('code-verify', 'codeVerify')->name('code.verify');
                Route::post('verify-code', 'verifyCode')->name('verify.code');
            });

        Route::controller('ResetPasswordController')->group(function () {
            Route::post('password/reset', 'reset')->name('password.update');
            Route::get('password/reset/{token}', 'showResetForm')->name('password.reset');
        });

        Route::controller('SocialiteController')->group(function () {
            Route::get('social-login/{provider}', 'socialLogin')->name('social.login');
            Route::get('social-login/callback/{provider}', 'callback')->name('social.login.callback');
        });
    });

Route::middleware('auth')
    ->name('user.')
    ->group(function () {

        //authorization
        Route::middleware('registration.complete')
            ->namespace('User')
            ->controller('AuthorizationController')
            ->group(function () {
                Route::get('authorization', 'authorizeForm')->name('authorization');
                Route::get('send-verify/{type}', 'sendVerifyCode')->name('send.verify.code');
                Route::post('verify-email', 'emailVerification')->name('verify.email');
                Route::post('verify-mobile', 'mobileVerification')->name('verify.mobile');
                Route::post('verify-g2fa', 'g2faVerification')->name('2fa.verify');
            });

        Route::namespace('User')
            ->controller('ChannelController')
            ->prefix('channel')
            ->name('channel.')
            ->group(function () {
                Route::get('create', 'create')->name('create');
                Route::post('data-submit', 'channelDataSubmit')->name('data.submit');
            });

        Route::namespace('User')
            ->controller('CommentController')
            ->prefix('comment')
            ->name('comment.')
            ->group(function () {
                Route::post('submit/{id}', 'commentSubmit')->name('submit');
                Route::post('reply', 'replySubmit')->name('reply');
                Route::post('like-dislike/{id?}', 'likeDislike')->name('like.dislike');
            });

        Route::middleware('registration.complete')->group(function () {

            Route::namespace('User')->middleware('check.status')->group(function () {
                Route::controller('UserController')->group(function () {

                    Route::get('dashboard', 'home')->name('home');
                    
                    Route::get('videos', 'videos')->name('videos');
                    Route::get('free-videos', 'freeVideos')->name('free.videos');
                    Route::get('stock-videos', 'stockVideos')->name('stock.videos');
                    Route::get('shorts', 'shorts')->name('shorts');
                    Route::post('reaction/{id}', 'reaction')->withoutMiddleware(['check.status', 'registration.complete'])->name('reaction');
                    Route::get('history', 'history')->name('history');
                    Route::post('remove-history/{id}', 'removeHistory')->name('remove.history');
                    Route::post('remove-all-history', 'removeAllHistory')->name('remove.all.history');

                    Route::get('earnings', 'earnings')->name('earnings');
                    Route::get('wallet', 'wallet')->name('wallet');
                    Route::get('earning-chart', 'earningChat')->name('chart.earnings');
                    Route::get('video-impression-chart', 'impressionChat')->name('chart.impression');

                    Route::post('watch-later/{id}', 'watchLater')->name('watch.later');
                    Route::get('watch-later', 'listWatchLater')->name('watch.later.list');
                    Route::post('remove-watch-later/{id}', 'removeWatchLater')->name('remove.watch.later');
                    Route::post('remove-all-watch-later', 'removeAllWatchLater')->name('remove.all.watch.later');

                    Route::post('subscribe-channel/{id}', 'subscribeChannel')->withoutMiddleware(['check.status', 'registration.complete'])->name('subscribe.channel');
                    Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');

                    //KYC
                    Route::get('kyc-form', 'kycForm')->name('kyc.form');
                    Route::get('kyc-data', 'kycData')->name('kyc.data');
                    Route::post('kyc-submit', 'kycSubmit')->name('kyc.submit');

                    Route::post('add-device-token', 'addDeviceToken')->name('add.device.token');
                    //notifications
                    Route::get('notification-read/{id}', 'notificationRead')->name('notification.read');
                    Route::get('notification-alls', 'notificationAll')->name('notification.all');
                    Route::post('notification/read/all', 'notificationMarkAsReadAll')->name('notification.read.all');
                    Route::post('notification/delete/{id}', 'notificationDelete')->name('notification.delete');
                    Route::post('notification/all/delete', 'notificationDeleteAll')->name('notification.delete.all');

                    Route::get('monetization', 'monetizationSetting')->name('monetization');
                    Route::get('apply-for-monetization', 'applyForMonetization')->name('monetization.apply');
                });

                //Monthly subscription plan
                Route::controller('ManagePlanController')->prefix('manage-plan')->name('manage.plan.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('details/{slug}', 'details')->name('details');
                    Route::post('save/{id?}', 'save')->name('save');
                    Route::post('add-video/{id}', 'addVideo')->name('add.video');
                    Route::post('add-playlist/{id}', 'addPlanPlaylist')->name('add.playlist');
                    Route::post('remove-video/{video_id}/{plan_id}', 'removeVideo')->name('remove.video');
                    Route::post('remove-playlist/{playlist_id}/{plan_id}', 'removePlanPlaylist')->name('remove.playlist');
                    Route::post('status/{id}', 'status')->name('status');

                    Route::get('video-fetch/{id}', 'videoFetch')->name('video.fetch');
                    Route::get('fetch-playlist/{id}', 'playlistFetch')->name('playlist.fetch');
                });

            Route::controller('PlanController')->name('plan.')->group(function () {
                Route::get('purchased-plan', 'purchasedPlanLists')->name('purchased');
                Route::get('plan/sell/history', 'planSellHistory')->name('sell.history');
            });


                //Report
                Route::controller('UserReportController')->group(function () {
                    Route::get('transactions', 'transactions')->name('transactions');
                    Route::get('video/purchased/history', 'purchasedHistory')->name('purchased.history');
                    Route::get('playlist/purchased/history', 'playlistPurchasedHistory')->name('playlist.purchased.history');
                    Route::get('playlist/sell/history', 'playlistSellHistory')->name('playlist.sell.history');
                });

                Route::controller('SettingController')
                    ->prefix('setting')
                    ->name('setting.')
                    ->group(function () {
                        Route::get('account', 'accountSetting')->name('account');
                        Route::post('account', 'updateAccount');

                        Route::get('security', 'security')->name('security');
                        //2FA
                        Route::get('twofactor', 'show2faForm')->name('twofactor');
                        Route::post('twofactor/enable', 'create2fa')->name('twofactor.enable');
                        Route::post('twofactor/disable', 'disable2fa')->name('twofactor.disable');

                        //Profile setting
                        Route::controller('ProfileController')->group(function () {
                            Route::get('profile-setting', 'profile')->name('profile');
                            Route::post('profile-setting', 'submitProfile');
                            Route::get('change-password', 'changePassword')->name('change.password');
                            Route::post('change-password', 'submitPassword');
                        });
                    });

                Route::controller('ChannelController')
                    ->prefix('channel')
                    ->name('channel.')
                    ->group(function () {
                        Route::get('/', 'channel')->name('home');
                    });

                Route::controller('PlaylistController')
                    ->prefix('playlist')
                    ->name('playlist.')
                    ->group(function () {
                        Route::get('/', 'playlist')->name('index');
                        Route::get('videos/{slug?}', 'playlistVideos')->name('videos');
                        Route::get('load', 'loadPlaylists')->name('load');
                        Route::get('load-videos/{id?}', 'loadVideos')->name('load.videos');

                        Route::post('save/{id?}', 'save')->name('save');
                        Route::post('add-video', 'addVideo')->name('add.video');
                        Route::get('video-fetch/{id}', 'videoFetch')->name('video.fetch');

                        Route::post('remove-video/{video_id}/{playlist_id}', 'removeVideo')->name('video.remove');
                        Route::get('check-playlist-slug', 'checkPlaylistSlug')->name('check.slug');
                    });

                Route::controller('VideoController')
                    ->middleware(['check.status'])
                    ->prefix('video')
                    ->name('video.')
                    ->group(function () {
                        Route::get('upload-form/{id?}', 'uploadForm')->name('upload.form');
                        Route::post('upload-file/{id?}', 'uploadFile')->name('upload');
                        Route::post('marge/upload-file/{id?}', 'mergeChunks')->name('merge');
                        Route::get('upload-server/{id?}', 'uploadLiveServer')->name('upload.server');
                        Route::get('details-form/{id?}', 'detailsForm')->name('details.form');
                        Route::post('details-submit/{id}', 'detailsSubmit')->name('details.submit');
                        Route::get('elements-form/{id?}', 'elementsForm')->name('elements.form');
                        Route::get('fatch-playlist', 'fatchPlaylist')->name('fatch.playlist');
                        Route::post('elements-form-submit/{id}', 'elementsSubmit')->name('elements.submit');
                        Route::get('visibility-form/{id?}', 'visibilityForm')->name('visibility.form');
                        Route::post('visibility-submit/{id}', 'visibilitySubmit')->name('visibility.submit');
                        Route::get('play/{id}', 'playVideo')->name('play');
                        Route::get('edit/{id?}', 'editVideo')->name('edit');
                        Route::get('check-slug', 'checkSlug')->name('check.slug');
                        Route::post('fetch-data', 'fetchData')->name('fetch.data');
                        Route::get('video-analytics/{slug?}', 'videoAnalytics')->name('analytics');
                        Route::get('video-chart/{slug?}', 'videoChart')->name('chart');

                        Route::post('add-playlist', "addPlaylist")->name('add.playlist');
                        Route::get('fetch-tags', 'fatchTags')->name('fatch.tags');
                    });

                Route::controller('ShortsController')
                    ->prefix('shorts')
                    ->name('shorts.')
                    ->group(function () {
                        Route::get('upload-form/{id?}', 'uploadForm')->name('upload.form');
                        Route::post('upload-shorts-file/{id?}', 'uploadFile')->name('upload');
                        Route::get('details-form/{id?}', 'detailsForm')->name('details.form');
                        Route::post('details-submit/{id}', 'detailsSubmit')->name('details.submit');
                        Route::get('fatch-playlist', 'fatchPlaylist')->name('fatch.playlist');
                        Route::get('visibility-form/{id?}', 'visibilityForm')->name('visibility.form');
                        Route::post('visibility-submit/{id}', 'visibilitySubmit')->name('visibility.submit');
                        Route::get('edit/{id}', 'editShorts')->name('edit');
                    });

                Route::controller('AdsController')
                    ->prefix('ad')
                    ->name('ad.')
                    ->group(function () {
                        Route::get('setting/{slug?}', 'adSetting')->name('setting');
                        Route::post('add-play-duration/{slug}', 'addPlayDuration')->name('play.duration');
                    });

                Route::controller('AdvertiserController')
                    ->name('advertiser.')
                    ->prefix('advertiser')
                    ->group(function () {
                        Route::get('dashboard', 'home')->name('home');
                        Route::get('ads-chart', 'adsChart')->name('ad.chart');
                        Route::post('data-submit', 'dataSubmit')->name('data.submit');
                    });
                    
                    Route::middleware('check.advertiser.status')->name('advertiser.')->prefix('advertiser')->group(function () {

                        Route::controller('AdvertiserController')->group(function () {
                            Route::get('create-ad', 'createAd')->name('create.ad');
                            Route::post('upload-ad-video', 'uploadAdVideo')->name('upload.ad.video');
                            Route::get('upload-ad-server/{id?}', 'uploadFtp')->name('upload.ad.ftp');
                            Route::post('processed-checkout/{id}', 'processedCheckout')->name('processed.checkout');
                            Route::get('ad-list', 'adList')->name('ad.list');
                            Route::get('advanced-ad-list', 'advanceAdList')->name('ad.list.advanced');
                            Route::post('status/{id}', 'status')->name('status');
                            Route::get('published/{id?}', 'published')->name('published');
                            Route::get('payment/history', 'paymentHistory')->name('payment.history');                        
                        });
    
    
                        Route::controller('CampaignController')->name('campaign.')->prefix('campaign')->group(function () {
                            Route::get('create/{id?}', 'create')->name('create');
                            Route::post('save/{id?}', 'save')->name('save');
                            Route::get('/', 'index')->name('index');
                            Route::get('gateways/{id}', 'gateways')->name('gateways');
                            Route::get('check-slug', 'checkSlug')->name('check.slug');
                            Route::post('status/{id?}', 'status')->name('status');
                            Route::get('detail/{slug?}', 'detail')->name('detail');
                          
                        });
    
    
                        Route::controller('AdsController')
                            ->prefix('ad')
                            ->name('ad.')
                            ->group(function () {
                                Route::get('create/{slug}', 'create')->name('create');
                                Route::get('edit/ad-set/{id}', 'editAdSet')->name('edit');
                                Route::post('store/{id?}', 'store')->name('store');
                                Route::get('get-video', 'getVideo')->name('get.video');
                                Route::get('analytics/{id?}', 'analytics')->name('analytics');
                                Route::get('analytics-chart/{id?}', 'analyticsChart')->name('analytics.chart');
                                
                            });
                    });
    
                // Withdraw
                Route::controller('WithdrawController')
                    ->prefix('withdraw')
                    ->name('withdraw')
                    ->group(function () {
                        Route::middleware('kyc')->group(function () {
                            Route::get('/', 'withdrawMethod');
                            Route::post('/withdraw/method', 'withdrawMethodSubmit')->name('.method.submit');
                            Route::get('/download/withdraw/attachments/{fileHash}', 'downloadAttachment')->name('.download.attachment');
                        });
                        Route::get('history', 'withdrawLog')->name('.history');
                    });
            });

            // Payment
            Route::prefix('deposit')
                ->name('deposit.')
                ->controller('Gateway\PaymentController')
                ->group(function () {
                    Route::post('insert', 'depositInsert')->name('insert');
                    Route::get('confirm', 'depositConfirm')->name('confirm');
                    Route::get('manual', 'manualDepositConfirm')->name('manual.confirm');
                    Route::post('manual', 'manualDepositUpdate')->name('manual.update');
                    Route::any('/{id?}/{monetization?}', 'deposit')->name('index');
                });
        });
    });
