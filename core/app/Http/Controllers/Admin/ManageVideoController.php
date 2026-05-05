<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdvertisementAnalytics;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Impression;
use App\Models\Subtitle;
use App\Models\Transaction;
use App\Models\UserReaction;
use App\Models\Video;
use App\Models\VideoTag;
use App\Rules\FileTypeValidate;
use App\Traits\GetDateMonths;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ManageVideoController extends Controller {

    use GetDateMonths;
    public function index($userId = null) {
        $pageTitle = 'All Videos';
        $videos    = $this->videoData(userId: $userId);
        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    public function regular($userId = null) {
        $pageTitle = 'Regular Videos';
        $videos    = $this->videoData('regular', $userId);

        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    public function shorts($userId = null) {
        $pageTitle = 'Shorts Videos';
        $videos    = $this->videoData('shorts', $userId);
        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    public function stock($userId = null) {
        $pageTitle = 'Stock Videos';
        $videos    = $this->videoData('stock', $userId);
        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    public function free($userId = null) {
        $pageTitle = 'Stock Videos';
        $videos    = $this->videoData('free', $userId);
        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    public function draft($userId = null) {
        $pageTitle = 'Draft Videos';
        $videos    = $this->videoData('draft');
        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    public function published($userId = null) {
        $pageTitle = 'Published Videos';
        $videos    = $this->videoData('published');
        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    public function rejected($userId = null) {
        $pageTitle = 'Rejected Videos';
        $videos    = $this->videoData('rejected');
        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    public function public($userId = null) {
        $pageTitle = 'Public Videos';
        $videos    = $this->videoData('public', $userId);
        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    public function private($userId = null) {
        $pageTitle = 'Private Videos';
        $videos    = $this->videoData('private', $userId);
        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    protected function videoData($scope = null, $userId = null) {
        if ($scope) {
            $videos = Video::$scope();
        } else {
            $videos = Video::query();
        }

        if ($userId) {
            $videos = $videos->where('user_id', $userId);
        }

        return $videos
            ->searchable(['title', 'user:username', 'user:channel_name'])
            ->with('user', 'tags')

            ->paginate(getPaginate());
    }

    public function edit($id) {
        $video      = Video::with('subtitles', 'user', 'category', 'videoFiles')->with('tags')->findOrFail($id);
        $categories = Category::active()->get();
        $pageTitle  = 'Edit ' . @$video->title;
        return view('admin.videos.edit', compact('pageTitle', 'video', 'categories'));
    }

    public function update(Request $request, $id) {
        $this->validation($request, $id);

        $video    = Video::findOrFail($id);
        $category = Category::active()->findOrFail($request->category);

        $video->title       = $request->title;
        $video->slug        = $request->slug;
        $video->description = $request->description;
        if ($request->hasFile('thumb_image')) {
            try {
                $old                = $video->thumb_image;
                $video->thumb_image = fileUploader($request->thumb_image, getFilePath('thumbnail'), getFileSize('thumbnail'), $old, getFileThumb('thumbnail'));
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your video'];
                return back()->withNotify($notify);
            }
        }

        $video->category_id = $category->id;
        $video->visibility  = $request->visibility;

        if ($request->old_subtitle) {
            $removeSub = array_diff($video->subtitles->pluck('id')->toArray(), $request->old_subtitle ?? []);
        } else {
            $removeSub = $video->subtitles->pluck('id')->toArray();

        }
        $video->subtitles()->whereIn('id', $removeSub)->get()->each(function ($old) {
            $filePath = getFilePath('subtitle') . '/' . $old->file;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $old->delete();
        });

        $video->audience    = $request->audience;
        $video->stock_video = $request->stock_video;
        $video->is_trending = $request->is_trending ? Status::YES : Status::NO;
        $video->audience    = $request->audience;
        $video->step        = $video->is_shorts_video ? Status::THIRD_STEP : Status::FOURTH_STEP;
        $video->status      = $request->status;

        $video->save();

        if ($request->tags) {
            $oldTags = $video->tags;
            if ($oldTags) {
                $video->tags()->delete();
            }
            foreach ($request->tags as $tag) {
                $videoTag           = new VideoTag();
                $videoTag->video_id = $video->id;
                $videoTag->tag      = $tag;
                $videoTag->save();
            }
        }

        if ($request->subtitle_file) {
            foreach ($request->subtitle_file as $key => $file) {
                $subtitle = new Subtitle();
                try {
                    $subtitle->file = fileUploader($file, getFilePath('subtitle'));
                } catch (\Exception $exp) {
                    $notify[] = ['error', 'Couldn\'t upload your subtitle'];
                    return back()->withNotify($notify);
                }
                $subtitle->video_id      = $video->id;
                $subtitle->caption       = $request->caption[$key];
                $subtitle->language_code = $request->language_code[$key];
                $subtitle->save();
            }
        }

        $notify[] = ['success', 'Video updated successfully'];
        return back()->withNotify($notify);
    }

    public function validation($request, $id) {

        $request->validate(
            [
                'title'           => 'required|string',
                'description'     => 'required|string',
                'status'          => 'required|integer|in:1,2',
                'thumb_image'     => ['nullable', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
                'slug'            => 'required|string|unique:videos,slug,' . $id,
                'category'        => 'required|integer',
                'tags'            => 'required|array|min:1',
                'tags.*'          => 'string',
                'visibility'      => 'required|in:0,1',
                'audience'        => 'required|in:0,1',
                'stock_video'     => 'nullable|integer|in:0,1',
                'price'           => 'required_if:stock_video,1|nullable|numeric',
                'caption.*'       => [
                    'sometimes',
                    function ($value, $fail) use ($request) {
                        if ($request->hasAny(['subtitle_file.*', 'language_code.*']) && empty($value)) {
                            $fail('Caption is required when subtitle file or language code is present.');
                        }
                    },
                ],
                'language_code.*' => [
                    'sometimes',
                    'string',
                    function ($value, $fail) use ($request) {
                        if ($request->hasAny(['subtitle_file.*', 'caption.*']) && empty($value)) {
                            $fail('Language code is required when caption or subtitle file is present.');
                        }
                    },
                ],
                'subtitle_file.*' => [
                    'sometimes',
                    new FileTypeValidate(['vtt']),
                    function ($value, $fail) use ($request) {
                        if ($request->hasAny(['caption.*', 'language_code.*']) && empty($value)) {
                            $fail('Subtitle file is required when caption or language code is present.');
                        }
                    },
                ],
            ],
            [
                'caption.*'         => 'Caption is required when subtitle file or language code is present.',
                'language_code.*'   => 'Language code must be a string and is required when caption or subtitle file is present.',
                'subtitle_file.*'   => 'Invalid subtitle format. Subtitle file is required when caption or language code is present.',
                'price.required_if' => 'Price is required when stock video is enabled.',
            ],
        );
    }

    public function analytics($id) {
        $video     = Video::where('id', $id)->firstOrFail();
        $pageTitle = 'Analytics - ' . @$video->title;
        return view('admin.videos.analytics', compact('pageTitle', 'video'));
    }

    public function filterData(Request $request, $id) {
        $video      = Video::where('id', $id)->first();
        $diffInDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));

        $groupBy = $diffInDays > 30 ? 'months' : 'days';
        $format  = $diffInDays > 30 ? '%M-%Y' : '%d-%M-%Y';

        if ($groupBy == 'days') {
            $dates = $this->getAllDates($request->start_date, $request->end_date);
        } else {
            $dates = $this->getAllMonths($request->start_date, $request->end_date);
        }

        $totalViews = Impression::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->selectRaw('SUM(views) AS views')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $totalLike = UserReaction::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)->like()
            ->selectRaw('SUM(is_like) AS is_like')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $totalDislike = UserReaction::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)->dislike()
            ->selectRaw('SUM(is_like) AS is_like')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $totalComment = Comment::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->selectRaw('Count(comment) AS comment')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $clicks = AdvertisementAnalytics::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->selectRaw('SUM(click) AS click')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $impressions = AdvertisementAnalytics::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->selectRaw('SUM(impression) AS impression')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $earningFromAds = Transaction::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->where('remark', 'ads_revenue')
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $earningFromVideos = Transaction::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->where('remark', 'earn_from_video')
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $totalEarning = Transaction::
            whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->whereIn('remark', ['earn_from_video', 'ads_revenue'])
            ->where('video_id', $video->id)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $data = [];

        foreach ($dates as $date) {
            $data[] = [
                'created_on'              => showDateTime($date, 'd-M-y'),
                'total_views'             => $totalViews->where('created_on', $date)->first()?->views ?? 0,
                'total_like'              => $totalLike->where('created_on', $date)->first()?->is_like ?? 0,
                'total_dislike'           => $totalDislike->where('created_on', $date)->first()?->is_like ?? 0,
                'total_comment'           => $totalComment->where('created_on', $date)->first()?->comment ?? 0,
                'total_clicks'            => $clicks->where('created_on', $date)->first()?->click ?? 0,
                'total_impressions'       => $impressions->where('created_on', $date)->first()?->impression ?? 0,
                'total_ads_earning'       => getAmount($earningFromAds->where('created_on', $date)->first()?->amount ?? 0),
                'total_purchased_earning' => getAmount($earningFromVideos->where('created_on', $date)->first()?->amount ?? 0),
                'totalEarning'            => getAmount($totalEarning->where('created_on', $date)->first()?->amount ?? 0),
            ];

        }

        $data = collect($data);

        $like     = $data->pluck('total_like')->sum();
        $dislike  = $data->pluck('total_dislike')->sum();
        $comments = $data->pluck('total_comment')->sum();
        $views    = $data->pluck('total_views')->sum();
        $pieData  = [$like, $dislike, $comments, $views];

        $report['created_on'] = $data->pluck('created_on');
        $report['data']       = [
            [
                'name' => 'Clicks',
                'data' => $data->pluck('total_clicks'),
            ],
            [
                'name' => 'Impressions',
                'data' => $data->pluck('total_impressions'),
            ],
            [
                'name' => 'Ads',
                'data' => $data->pluck('total_ads_earning'),
            ],
            [
                'name' => 'Sales',
                'data' => $data->pluck('total_purchased_earning'),
            ],
            [
                'name' => 'Total',
                'data' => $data->pluck('totalEarning'),
            ],

        ];

        return response()->json([
            $report,
            $pieData,
        ]);
    }

    public function checkSlug() {
        $video = Video::where('slug', request()->slug)->exists();
        return response()->json([
            'exists' => $video,
        ]);
    }
}
