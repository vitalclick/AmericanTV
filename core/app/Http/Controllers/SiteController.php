<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Models\Advertisement;
use App\Models\AdvertisementAnalytics;
use App\Models\Category;
use App\Models\Frontend;
use App\Models\GatewayCurrency;
use App\Models\Impression;
use App\Models\Language;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Playlist;
use App\Models\Transaction;
use App\Models\UserNotification;
use App\Models\Video;
use App\Models\VideoFile;
use App\Models\WatchHistory;
use App\Traits\AdsManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class SiteController extends Controller
{
    use AdsManager;
    public function index()
    {

        $pageTitle   = 'Home';
        $sections    = Page::where('tempname', activeTemplate())->where('slug', '/')->first();
        $seoContents = $sections->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;

        $baseVideos = Video::published()->public()->withoutOnlyPlaylist()->withWhereHas('user', function ($query) {
            $query->active();
        })->orderBy('id', 'desc');


        $shortVideos = (clone $baseVideos)->shorts()->take(15)->get();
        $videos      = (clone $baseVideos)->where('is_shorts_video', Status::NO)->with('videoFiles')->paginate(getPaginate());

        $trendingVideosQuery = (clone $baseVideos)->where('is_shorts_video', Status::NO)->where(function ($query) {
            $query->whereDate('created_at', '>=', now()->subDays(7))->orWhere('is_trending', Status::YES);
        })->orderByDesc('views')->limit(10);

        $trendingVideos     = $trendingVideosQuery->get();
        $trendingVideoCount = $trendingVideos->count();

        if ($trendingVideoCount < 10) {
            $remainingCount   = 10 - $trendingVideoCount;
            $additionalVideos = (clone $baseVideos)->where('is_shorts_video', Status::NO)->whereNotIn('id', @$trendingVideos->pluck('id') ?? [])->whereDate('created_at', '<', now()->subDays(7))->limit($remainingCount)->orderByDesc('views')->get();
            $trendingVideos   = $trendingVideos->merge($additionalVideos);
        }

        $today = Carbon::now();
        $inFeedAd = [];
        if (gs('ads_module')) {

            $ad = Advertisement::with('countries', 'schedules', 'video.videoFiles')->where(function($query){
                $query->whereIn('ad_type', [Status::IN_FEED, Status::ALL_VIEWS]);
            })->where('status', Status::RUNNING)->whereHas('campaign', function ($query) {
                $query->where('status', Status::RUNNING)->where('payment_status', Status::PAYMENT_SUCCESS);
            })->inRandomOrder()->first();

            if ($ad) {
                $eligible = $ad->eligible();

                if ($eligible) {
                    $this->perUserCostsCalculate($ad);
                    $this->adsReached($ad);
                    $inFeedAd = $ad;
                }
            }
        }

        return view('Template::home', compact('pageTitle', 'sections', 'seoContents', 'seoImage', 'videos', 'trendingVideos', 'shortVideos', 'inFeedAd'));
    }

    public function search(Request $request)
    {
        $pageTitle   = 'Search';
        $sections    = Page::where('tempname', activeTemplate())->where('slug', '/')->first();
        $seoContents = $sections->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        $videos      = Video::searchable(['title', 'category:name', 'description', 'tags:tag'])
            ->published()
            ->public()
            ->latest()
            ->regular()
            ->with('videoFiles')
            ->paginate(getPaginate());

        return view('Template::search', compact('pageTitle', 'sections', 'seoContents', 'seoImage', 'videos'));
    }



    public function shortsList()
    {
        $pageTitle   = 'Shorts';
        $shortVideos = Video::published()->public()->whereHas('user', function ($query) {
            $query->active();
        })->shorts()->latest()->paginate(getPaginate());
        return view('Template::shorts', compact('pageTitle', 'shortVideos'));
    }

    public function pages($slug)
    {
        $page        = Page::where('tempname', activeTemplate())->where('slug', $slug)->firstOrFail();
        $pageTitle   = $page->name;
        $sections    = $page->secs;
        $seoContents = $page->seo_content;
        $seoImage    = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        return view('Template::pages', compact('pageTitle', 'sections', 'seoContents', 'seoImage'));
    }

    public function policyPages($slug)
    {
        $policy      = Frontend::where('slug', $slug)->where('data_keys', 'policy_pages.element')->firstOrFail();
        $pageTitle   = $policy->data_values->title;
        $seoContents = $policy->seo_content;
        $seoImage    = @$seoContents->image ? frontendImage('policy_pages', $seoContents->image, getFileSize('seo'), true) : null;
        return view('Template::policy', compact('policy', 'pageTitle', 'seoContents', 'seoImage'));
    }

    public function accountDeletion()
    {
        $pageTitle = 'Delete Your Account';
        return view('Template::account_deletion', compact('pageTitle'));
    }

    public function accountDeletionRequest(Request $request)
    {
        $request->validate([
            'email'  => 'required|email|max:255',
            'reason' => 'nullable|string|max:1000',
        ]);

        $email = strtolower(trim($request->email));
        $user  = \App\Models\User::whereRaw('LOWER(email) = ?', [$email])->first();

        try {
            \App\Models\AccountDeletionRequest::create([
                'user_id' => $user?->id,
                'email'   => $email,
                'source'  => 'web',
                'reason'  => $request->reason,
                'status'  => 'pending',
            ]);
        } catch (\Throwable $e) {
            // If the table is not yet migrated, still acknowledge the request
            // so the user is never blocked; the failure is logged for admins.
            logger()->error('Account deletion request could not be stored: ' . $e->getMessage(), [
                'email' => $email,
            ]);
        }

        $notify = ['success', 'Your account deletion request has been received. We will process it within 30 days and email you once it is complete.'];
        return back()->withNotify([$notify]);
    }

    public function changeLanguage($lang = null)
    {
        $language = Language::where('code', $lang)->first();
        if (!$language) {
            $lang = 'en';
        }
        session()->put('lang', $lang);
        return back();
    }

    public function playVideo($id = 0, $slug = null)
    {

        $videos = Video::published()->public()->where('is_shorts_video', Status::NO)->with('videoFiles', 'user');

        $video  = (clone $videos)->where('id', $id)->whereHas('user', function ($query) {
            $query->active();
        })->with(['userReactions', 'subtitles', 'adPlayDurations', 'comments' => function ($query) {
            $query->with('user', 'replies.user')->latest()->take(20);
        }])->firstOrFail();

        $seoContents = $video->description;

        $seoImage = $video->thumb_image ? getImage(getFilePath('thumbnail') . '/' . $video->thumb_image) : null;

        $pageTitle    = $video->title;
        $adsDurations = $video->adsDurations();

        $categories = Category::active()->withCount('videos')->orderBy('videos_count', 'desc')->get();
        $comments   = $video->comments;

        $videoTags = $video->tags->pluck('tag')->toArray();

        $relatedPlaylistVideos = [];

        $palyPlaylist = [];

        $relatedVideos = (clone $videos)->where('id', '!=', $video->id)
            ->where(function ($query) use ($video, $videoTags) {
                $query->where('category_id', $video->category_id)->orWhere(function ($query) use ($videoTags) {
                    $query->whereHas('tags', function ($query) use ($videoTags) {
                        $query->whereIn('tag', $videoTags);
                    });
                });
            })
            ->inRandomOrder()
            ->take(20)
            ->get();

        if ($relatedVideos->isEmpty()) {
            $relatedVideos = (clone $videos)->where('id', '!=', $video->id)->latest()->take(10)->get();
        }

        $this->viewsHistory($video);

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();

        $purchasedTrue = true;
        $watchLater    = false;
        $playlists     = [];
        $plan          = [];
        $planPlaylists = [];
        if ($video->stock_video) {
            $purchasedTrue = false;
        }
        if (auth()->check()) {
            $user = auth()->user();
            if ($video->stock_video) {

                if ($video->user_id == $user->id || in_array($video->id, $user->purchasedVideoId)) {

                    $purchasedTrue = true;
                }
            }
            $watchLater    = in_array($video->id, $user->watchLatterVideoId);
            $existsHistory = collect($user->watchHistories)->where('video_id', $video->id)->first();
            if ($existsHistory) {
                $existsHistory->last_view = Carbon::now();
                $existsHistory->save();
            } else {
                $history            = new WatchHistory();
                $history->user_id   = auth()->id();
                $history->video_id  = $video->id;
                $history->last_view = Carbon::now();
                $history->save();
            }
            $playlists = Playlist::where('user_id', auth()->id())->get();
        }
        $isPurchased = false;
        if (request()->list) {
            $palyPlaylist = Playlist::public()->with('videos')->where('slug', request()->list)->first();

            $isPurchased = true;
            if (@$palyPlaylist->playlist_subscription) {
                $isPurchased = false;
            }

            if (auth()->check()) {
                if (@$palyPlaylist->playlist_subscription && in_array($palyPlaylist->id, $user->purchasedPlaylistId)) {
                    $isPurchased = true;

                    $videoInRequestedPlaylist = $palyPlaylist->videos()
                        ->where('videos.id', $video->id)
                        ->exists();

                    if ($videoInRequestedPlaylist) {
                        $purchasedTrue = true;
                    }
                }
            }

            if ($palyPlaylist) {
                $relatedPlaylistVideos = @$palyPlaylist->videos()->public()->regular()->get();
            }

            if (request()->plan) {
                $plan          = Plan::where('slug', request()->plan)->firstOrFail();
                $purchasedTrue = false;

                if (auth()->check()) {
                    $purchasedTrue = $user->hasValidPlan($plan->id);
                } else {
                    return to_route('user.login');
                }

                $planPlaylists = $plan->playlists()->with('videos')->get();
            }
        } else if (request()->plan) {
            $plan          = Plan::where('slug', request()->plan)->firstOrFail();
            $palyPlaylist  = $plan;
            $purchasedTrue = false;

            if (auth()->check()) {
                $purchasedTrue = $user->hasValidPlan($plan->id);
            } else {
                return to_route('user.login');
            }

            $relatedPlaylistVideos = @$plan->videos()->get();
            $planPlaylists         = $plan->playlists()->with('videos')->get();
        }

        return view('Template::play_video', compact('pageTitle', 'seoContents', 'seoImage', 'plan', 'planPlaylists', 'isPurchased', 'playlists', 'video', 'gatewayCurrency', 'relatedVideos', 'categories', 'purchasedTrue', 'watchLater', 'comments', 'adsDurations', 'relatedPlaylistVideos', 'palyPlaylist'));
    }

    public function shortPlayVideo($id = 0, $slug = null)
    {
        $pageTitle = 'Short Video';
        $short     = Video::published()
            ->public()
            ->with('comments.replies', 'comments.user', 'comments.userReactions')
            ->where('is_shorts_video', Status::YES)
            ->where('id', $id)
            ->firstOrFail();

        $this->viewsHistory($short);

        $relatedVideosQuery = Video::published()
            ->public()
            ->with('comments.replies', 'comments.user', 'comments.userReactions')
            ->where('id', '!=', $short->id)
            ->where('is_shorts_video', Status::YES)
            ->latest();

        $relatedVideos = $relatedVideosQuery->paginate(getPaginate());

        return view('Template::shorts_play', compact('pageTitle', 'short', 'relatedVideos'));
    }

    private function viewsHistory($video)
    {
        $playedVideosJson = session()->get('played_videos', '[]');
        $playedVideos     = json_decode($playedVideosJson, true);
        $playVideo        = (object) (@$playedVideos[$video->id] ?? []);

        if (@$playVideo->exp <= now()) {
            $expiration               = Carbon::now()->addMinutes(20);
            $playedVideos[$video->id] = [
                'id'  => $video->id,
                'exp' => $expiration->toDateTimeString(),
            ];

            session()->put('played_videos', json_encode($playedVideos));
            $video->views += 1;
            $video->save();

            $impression           = new Impression();
            $impression->user_id  = $video->user_id;
            $impression->video_id = $video->id;
            $impression->save();
        }
    }

    public function cookieAccept()
    {
        Cookie::queue('gdpr_cookie', gs('site_name'), 43200);
    }

    public function cookiePolicy()
    {
        $cookieContent = Frontend::where('data_keys', 'cookie.data')->first();
        abort_if($cookieContent->data_values->status != Status::ENABLE, 404);
        $pageTitle = 'Cookie Policy';
        $cookie    = Frontend::where('data_keys', 'cookie.data')->first();
        return view('Template::cookie', compact('pageTitle', 'cookie'));
    }

    public function placeholderImage($size = null)
    {
        $imgWidth  = explode('x', $size)[0];
        $imgHeight = explode('x', $size)[1];
        $text      = $imgWidth . '×' . $imgHeight;
        $fontFile  = realpath('assets/font/solaimanLipi_bold.ttf');
        $fontSize  = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if ($imgHeight < 100 && $fontSize > 30) {
            $fontSize = 30;
        }

        $image     = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill    = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgFill);
        $textBox    = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth  = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX      = ($imgWidth - $textWidth) / 2;
        $textY      = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    public function maintenance()
    {
        $pageTitle = 'Maintenance Mode';
        if (gs('maintenance_mode') == Status::DISABLE) {
            return to_route('home');
        }
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->first();
        return view('Template::maintenance', compact('pageTitle', 'maintenance'));
    }

    public function getVideos($isStockVideo = false, $id = null)
    {
        $query = Video::published()->public()->withoutOnlyPlaylist()->latest()->with('user', 'videoFiles')->whereHas('user', function ($q) {
            $q->active();
        })->regular();

        if ($isStockVideo) {
            $query->stock();
        }

        if (request()->trending) {
            $query->whereDate('created_at', '>=', now()->subDays(7))->orWhere('is_trending', Status::YES);
        }

        if (request()->category_id) {
            $query->where('category_id', request()->category_id);
        }

        if ($id) {
            $query->where('user_id', $id);
        }

        $videos = $query->orderBy('id', 'desc')->paginate(getPaginate());

        $videos->getCollection()->transform(function ($video) {
            if (auth()->check()) {
                $user                  = auth()->user();
                $video->purchased_true = $user
                    ->purchasedVideos()
                    ->where('video_id', $video->id)
                    ->exists();
            } else {
                $video->purchased_true = false;
            }
            return $video;
        });

        $html = view('Template::partials.video.video_list', compact('videos'))->render();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'videos'       => $html,
                'current_page' => $videos->currentPage(),
                'last_page'    => $videos->lastPage(),
                'total'        => $videos->total(),
            ],
        ]);
    }

    public function loadShorts($id = null)
    {
        $query = Video::published()->public()->whereHas('user', function ($query) {
            $query->active();
        })->latest()->with('user');

        if ($id) {
            $query->where('user_id', $id);
        }
        $shortVideos = $query->shorts()->paginate(getPaginate());

        if (request()->play_short) {

            $html = view('Template::partials.video.load_shorts', compact('shortVideos'))->render();
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'html'         => $html,
                    'current_page' => $shortVideos->currentPage(),
                    'last_page'    => $shortVideos->lastPage(),
                ],
            ]);
        } else {

            $html = view('Template::partials.video.shorts_list', compact('shortVideos'))->render();

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'videos'       => $html,
                    'current_page' => $shortVideos->currentPage(),
                    'last_page'    => $shortVideos->lastPage(),
                    'total'        => $shortVideos->total(),
                ],
            ]);
        }
    }

    public function getAllVideos($id = null)
    {
        return $this->getVideos(false, $id);
    }

    public function getStockVideos()
    {
        return $this->getVideos(true);
    }

    public function stockVideos()
    {
        $pageTitle = 'Stock Videos';
        $videos    = Video::published()->withoutOnlyPlaylist()->public()->where('is_shorts_video', Status::NO)->stock()->whereHas('user', function ($query) {
            $query->active();
        })->with('videoFiles')->latest()->paginate(getPaginate());
        return view('Template::stock_videos', compact('videos', 'pageTitle'));
    }

    public function categoryVideo($slug)
    {
        if ($slug == 'all') {
            $pageTitle = 'All Videos';
            return to_route('home');
        } else {
            $category  = Category::where('slug', $slug)->firstOrFail();
            $pageTitle = $category->name;
            $videos    = Video::published()->withoutOnlyPlaylist()
                ->public()
                ->withWhereHas('user', function ($query) {
                    $query->active();
                })
                ->where('is_shorts_video', Status::NO)
                ->where('category_id', $category->id)
                ->with('videoFiles')
                ->orderBy('id', 'desc')
                ->paginate(getPaginate());

            return view('Template::category_videos', compact('videos', 'pageTitle', 'category'));
        }
    }


    public function fetchAd()
    {
        $id = decrypt(request()->video_id);

        $video = Video::published()
            ->whereHas('user', fn($q) => $q->active())
            ->regular()
            ->find($id);

        if (!$video) {
            return response()->json(['error' => 'Video not found']);
        }

        if ($video->user->monetization_status != Status::MONETIZATION_APPROVED) {
            return response()->json(['error' => 'The video is not available for ads showing']);
        }

        $ad = $this->getAd($video);
      

        if (!$ad) {
            return response()->json(['error' => 'No available ad']);
        }

        $adsModuleEnabled = gs('ads_module');
        $actionUrl = null;
        $videoSrc = null;

        if ($adsModuleEnabled) {
            if ($ad->video) {
                $adVideo = $ad->video;
                $file = $adVideo->videoFiles()->first();
                $videoSrc = getVideo(optional($file)->file_name, $adVideo);
            } else {
                $videoSrc = getAd($ad->ad_file, $ad);
            }

            $actionUrl = route('redirect.ad', [
                'id' => encrypt($ad->id),
                'video_id' => encrypt(optional($video)->id),
            ]);
        } else {
            $videoSrc = getAd($ad->ad_file, $ad);

            if ($ad->ad_type == Status::CLICK) {
                $actionUrl = route('redirect.ad', [
                    'id' => encrypt($ad->id),
                    'video_id' => encrypt($video->id),
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'ad_id'        => $ad->id,
                'ad_title'     => $ad->title,
                'ad_type'      => $ad->ad_type,
                'ad_url'       => $ad->url,
                'button_label' => $ad->button_label,
                'ad_logo'      => getImage(getFilePath('adLogo') . '/' . $ad->logo),
                'ad_video_src' => $videoSrc,
                'action_url'   => $actionUrl,
            ],
        ]);
    }



    public function redirectAd($id, $video_id = 0)
    {


        if ($video_id) {
            $videoId = decrypt($video_id);
            $video   = Video::where('id', $videoId)->published()->public()->whereHas('user', function ($query) {
                $query->active();
            })->regular()->firstOrFail();

            if (!$video) {
                return response()->json(['error' => 'Video not found']);
            }

            if ($video->user->monetization_status != Status::MONETIZATION_APPROVED) {
                return response()->json(['error' => 'The video not available for ads showing']);
            }
        }

        if (gs('ads_module')) {
            $today = Carbon::now();
            $ad = Advertisement::with('countries', 'schedules')->where('start_date', '<=', $today)->where('end_date', '>=', $today)->where('status', Status::RUNNING)->where('payment_status', Status::PAYMENT_SUCCESS)->whereHas('campaign', function ($query) {
                $query->where('status', Status::RUNNING);
            })->findOrFail($id);

            $eligible = $ad->eligible();

            if (!$eligible) {
                $notify[] = ['error' => 'Ad not available'];
                return back()->withNotify($notify);
            }

            $this->perUserEngagementCostsCalculate($ad);

        } else {



            $ad = Advertisement::where('available_click', '>', 0)
                ->where('status', Status::RUNNING)
                ->where('user_id', '!=', $video->user_id)
                ->whereHas('categories', function ($query) use ($video) {
                    $query->where('category_id', $video->category_id)->where('status', Status::ENABLE);
                })
                ->where(function ($query) {
                    $query->where('ad_type', Status::CLICK);
                    $query->orWhere('ad_type', Status::BOTH);
                })
                ->findOrFail($id);


            $ad->available_click -= 1;
            $ad->save();
        }


        if ($ad->ad_type != Status::IN_FEED) {

            $videoOwner = $video->user;

            $videoOwner->balance += gs('per_click_earn');
            $videoOwner->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $videoOwner->id ?? 0;
            $transaction->video_id     = $video->id;
            $transaction->amount       = gs('per_click_earn');
            $transaction->post_balance = $videoOwner->balance;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = 'Earn form ads';
            $transaction->trx          = getTrx();
            $transaction->remark       = 'ads_revenue';
            $transaction->save();

            $userNotification            = new UserNotification();
            $userNotification->user_id   = $videoOwner->id ?? 0;
            $userNotification->title     = 'Ads revenue add to your balance';
            $userNotification->click_url = urlPath('user.transactions');
            $userNotification->save();
        }

        $adAnalysis                   = new AdvertisementAnalytics();
        $adAnalysis->video_id         = @$video->id ?? 0;
        $adAnalysis->advertisement_id = $ad->id;
        $adAnalysis->click            = Status::YES;
        $adAnalysis->save();

        return redirect($ad->url);
    }

    public function embedVideo($id = 0, $slug = null)
    {
        $video = Video::where('id', $id)->with('videoFiles', 'subTitles')->published()->free()->public()->whereHas('user', function ($query) {
            $query->active();
        })->regular()->firstOrFail();
        return view('Template::embed_video', compact('video'));
    }

    public function trendingList()
    {
        $pageTitle = 'Trending Videos';

        $trendingVideos = Video::published()
            ->public()
            ->regular()
            ->withoutOnlyPlaylist()
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->orWhere('is_trending', Status::YES)
            ->orderBy('views', 'desc')
            ->whereHas('user', function ($query) {
                $query->active();
            })
            ->paginate(getPaginate());
        return view('Template::trending_list', compact('trendingVideos', 'pageTitle'));
    }

    public function shortView($id)
    {

        $short = Video::published()
            ->public()
            ->whereHas('user', function ($query) {
                $query->active();
            })
            ->with('comments.replies', 'comments.user', 'comments.userReactions')
            ->where('is_shorts_video', Status::YES)
            ->find($id);

        if (!$short) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Video not found',
            ]);
        }

        $this->viewsHistory($short);

        return response()->json([
            'status'  => 'success',
            'message' => 'Views save successfully',
        ]);
    }

    public function getVideoSource($id)
    {
        // Build the query with necessary conditions
        $query = Video::with('videoFiles', 'storage')
            ->where('id', $id)
            ->public()
            ->whereHas('user', function ($query) {
                $query->active();
            })
            ->regular()
            ->published();

        if (auth()->check() && $query->first()->user_id != auth()->id()) {
            $query->free();
        }

        $video = $query->first();

        if (!$video) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Video not found',
            ]);
        }

        $file    = $video->videoFiles()->first();
        $path    = getVideo($file->file_name, $video);
        $quality = $file->quality;

        return response()->json([
            'status'  => 'success',
            'path'    => $path,
            'quality' => $quality,
        ]);
    }


    public function videoPath($id = 0)
    {
        $id = decrypt($id);

        $videoFile = VideoFile::with('video')->findOrFail($id);
        $video = $videoFile->video;

        if (!$this->canAccessVideo($video)) {
            return null;
        }

        return $this->streamVideo($videoFile->file_name, $video->storage_id, $video);
    }

    public function shortsPath($id)
    {
        $id = decrypt($id);

        $short = Video::where('id', $id)
            ->where('is_shorts_video', Status::YES)
            ->firstOrFail();

        return $this->streamVideo($short->video, $short->storage_id, $short);
    }

    private function canAccessVideo($video): bool
    {
        return $video->showEligible();
    }


    private function streamVideo($fileName, $storageId, $video)
    {
        if ($storageId == 0) {
            $filePath = 'assets/videos/' . $fileName;

            if (!file_exists($filePath)) {
                abort(404);
            }

            $size = filesize($filePath);
            $start = 0;
            $end = $size - 1;
            $length = $size;

            $headers = [
                'Content-Type' => 'application/octet-stream',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ];

            if (request()->headers->has('Range')) {
                preg_match('/bytes=(\d+)-(\d*)/', request()->header('Range'), $matches);
                $start = intval($matches[1]);
                if (isset($matches[2]) && $matches[2] !== '') {
                    $end = intval($matches[2]);
                }
                $length = $end - $start + 1;

                $headers['Content-Range'] = "bytes $start-$end/$size";
                $headers['Content-Length'] = $length;
                $status = 206;
            } else {
                $headers['Content-Length'] = $size;
                $status = 200;
            }

            $stream = function () use ($filePath, $start, $length) {
                $handle = fopen($filePath, 'rb');
                fseek($handle, $start);
                $bufferSize = 8192;

                while (!feof($handle) && $length > 0) {
                    $readLength = min($bufferSize, $length);
                    echo fread($handle, $readLength);
                    flush();
                    $length -= $readLength;
                }

                fclose($handle);
            };

            return response()->stream($stream, $status, $headers);
        }

        return redirect()->away(getVideo($fileName, $video));
    }
}
