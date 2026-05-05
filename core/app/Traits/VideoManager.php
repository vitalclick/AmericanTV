<?php

namespace App\Traits;

use App\Constants\Status;
use App\Lib\CurlRequest;
use App\Models\AdminNotification;
use App\Models\AdvertisementAnalytics;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Impression;
use App\Models\Playlist;
use App\Models\Storage;
use App\Models\Transaction;
use App\Models\UserReaction;
use App\Models\Video;
use App\Models\VideoFile;
use App\Models\VideoResolution;
use App\Models\VideoTag;
use App\Rules\FileTypeValidate;
use Carbon\Carbon;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\X264;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

trait VideoManager
{
    use GetDateMonths, StorageDriver;

    protected $view   = null;
    protected $shorts = false;

    public function uploadForm($id = 0)
    {
        $pageTitle = 'Upload Video';
        $video     = '';

        if ($id) {
            $video = Video::authUser()->findOrFail($id);
        }
        if ($video && $this->shorts && !@$video->is_shorts_video) {
            abort(404);
        }

        $isShorts         = $this->shorts;
        $resolutions      = VideoResolution::active()->orderBy('width', 'desc')->orderBy('height', 'desc')->get();
        $availableStorage = Storage::active()->where('available_space', '>', 0)->exists();

        return view('Template::user.' . $this->view . '.file_select', compact('pageTitle', 'isShorts', 'video', 'resolutions', 'availableStorage'));
    }

    public function uploadFile(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'extension' => ['required', 'in:mp4,mov,wmv,flv,avi,mkv'],
            'fileName'  => 'required|string',
            'index'     => 'required|integer',
            'uniqueId'  => 'required|string',
            'chunk'     => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }

        try {
            $file = $request->file('chunk');
            $fileName = $request->input('fileName');
            $index = $request->input('index');
            $uniqueId = $request->input('uniqueId');

            $tempDir = storage_path("app/temp/{$uniqueId}");

            if (!is_dir($tempDir)) {
                if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
                    throw new \RuntimeException("Failed to create temporary directory: {$tempDir}");
                }
            }

            $chunkPath = "{$tempDir}/{$fileName}.part{$index}";

            if (file_exists($chunkPath)) {
                unlink($chunkPath);
            }

            if (!$file->move($tempDir, "{$fileName}.part{$index}")) {
                throw new \RuntimeException("Failed to move uploaded chunk to: {$chunkPath}");
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Chunk uploaded successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during chunk upload.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



    public function mergeChunks(Request $request, $id = 0)
    {
        $validator = Validator::make($request->all(), [
            'fileName' => 'required|string',
            'total' => 'required|integer',
            'uniqueId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }

        $fileName = $request->fileName;
        $totalChunks = $request->total;

        $tempPath = storage_path("app/temp/{$request->uniqueId}");
        $outputPath = storage_path("app/videos");

        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        $mergedFilePath = $outputPath . '/' . uniqid() . '_' . $fileName;
        $output = fopen($mergedFilePath, 'ab');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $tempPath . "/{$fileName}.part{$i}";

            if (!file_exists($chunkPath)) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => "Missing chunk {$i}"
                    ]
                );
            }

            $chunk = fopen($chunkPath, 'rb');
            stream_copy_to_stream($chunk, $output);
            fclose($chunk);
            unlink($chunkPath);
        }

        fclose($output);

        if (File::exists($tempPath)) {
            File::deleteDirectory($tempPath);
        }


        try {
            $mimeType = mime_content_type($mergedFilePath);
            if (!str_starts_with($mimeType, 'video/')) {
                File::delete($mergedFilePath);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Uploaded file is not a valid video.'
                ]);
            }


            $uploadedFile = new UploadedFile(
                $mergedFilePath,
                basename($mergedFilePath),
                mime_content_type($mergedFilePath),
                null,
                true
            );

            if ($id) {
                $uploadVideo = Video::authUser()->findOrFail($id);
            } else {
                $uploadVideo          = new Video();
                $uploadVideo->user_id = auth()->id();
                $uploadVideo->step    = Status::FIRST_STEP;

                if ($request->shorts) {
                    $uploadVideo->is_shorts_video = Status::YES;
                }

                $uploadVideo->save();
            }

            $fileName = now()->format('Y/F') . '/' . uniqid() . time() . '.' . $uploadedFile->getClientOriginalExtension();

            if ($uploadedFile && !$uploadVideo->is_shorts_video) {
                if (gs('ffmpeg_status')) {
                    $result = $this->processVideo($uploadedFile, $uploadVideo, $id);
                    if (!$result->success && !$id) {
                        $uploadVideo->delete();
                        DB::rollBack();
                        return response()->json(['error' => $result->message]);
                    }
                } else {

                    if ($id) {
                        $videoFile = $uploadVideo->videoFiles()->first();
                        if (!$videoFile) {
                            DB::rollBack();
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Something went wrong'
                            ]);
                        }

                        if (@$uploadVideo->storage) {
                            $this->removeOldFile($uploadVideo, @$uploadVideo->storage, $videoFile->file_name, 'videos');
                        }
                    } else {
                        $videoFile = new VideoFile();
                    }

                    $videoFile->video_id  = $uploadVideo->id;
                    $videoFile->file_name = fileUploader($uploadedFile, getFilePath('video') . '/' . now()->format('Y/F'), old: $videoFile->file_name, filename: $fileName);
                    $videoFile->save();
                }
            } else {

                try {
                    if (@$uploadVideo->storage) {
                        $this->removeOldFile($uploadVideo, @$uploadVideo->storage, $uploadVideo->video, 'videos');
                    }

                    $uploadVideo->video = fileUploader($uploadedFile, getFilePath('video') . '/' . now()->format('Y/F'), old: $uploadVideo->video, filename: $fileName);
                    $uploadVideo->save();
                } catch (\Exception $exp) {

                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Couldn\'t upload your video'
                    ]);
                }
            }
        } catch (\Exception $e) {
            File::delete($mergedFilePath);
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'FFProbe error: ' . $e->getMessage()
                ]
            );
        }

        File::delete($mergedFilePath);


        return response()->json([
            'status' => 'success',
            'message' => 'Video uploaded successfully.',
            'data' => [
                'video'     => $uploadVideo,
            ]
        ]);
    }


    public function detailsForm($id = 0)
    {
        $pageTitle = 'Details';
        $video     = Video::where('step', '>=', Status::FIRST_STEP)->authUser()->with('playlist', 'videoFiles', 'storage')->findOrFail($id);

        if ($this->shorts && !$video->is_shorts_video) {
            abort(404);
        }

        $playlists = Playlist::authUser()->get();
        $action    = $this->view;

        return view('Template::user.' . $this->view . '.details', compact('pageTitle', 'video', 'playlists', 'action'));
    }

    public function fatchPlaylist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $playlists = Playlist::searchable(['title'])->select('id', 'title')->authUser()->paginate(getPaginate($request->rows ?? 5));
        $response  = [];

        foreach ($playlists as $playlist) {
            $response[] = [
                'id'   => $playlist->id,
                'text' => $playlist->title,
            ];
        }
        return response()->json($response);
    }

    public function detailsSubmit(Request $request, $id)
    {
        $video = Video::where('step', '>=', Status::FIRST_STEP)
            ->authUser()
            ->findOrFail($id);


        if ($this->shorts && !$video->is_shorts_video) {
            abort(404);
        }

        $isRequired = 'nullable';

        if (!$this->shorts) {
            $isRequired = $video->thumb_image ? 'nullable' : 'required';
        }

        $slug    = $request->slug;
        $isShort = $video->is_shorts_video;
        $request->validate([
            'title'       => 'required|string',
            'description' => 'required|string',
            'playlist'    => 'nullable|integer',
            'is_only_playlist' => 'required|integer|in:0,1',
            'thumb_image' => [$isRequired, new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'slug'        => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($slug, $isShort, $id) {
                    $query = Video::where('slug', $slug)->where('id', '!=', $id);
                    if ($isShort) {
                        $query->where('is_shorts_video', Status::YES);
                    }

                    if ($query->exists()) {
                        $fail('The ' . $attribute . ' must be unique.');
                    }
                },
            ],
        ]);

        $playlist = [];
        if ($request->playlist) {
            $playlist = Playlist::authUser()->find($request->playlist);
            if (!$playlist) {
                $notify[] = ['error', 'Playlist not found'];
                return back()->withNotify($notify);
            }
        }

        $video->title                       = $request->title;
        $video->slug                        = $request->slug;
        $video->description                 = $request->description;
        $video->is_only_playlist            = $request->is_only_playlist;

        if ($request->hasFile('thumb_image')) {
            try {
                $old                = $video->thumb_image;
                $video->thumb_image = fileUploader($request->thumb_image, getFilePath('thumbnail'), getFileSize('thumbnail'), $old, getFileThumb('thumbnail'));
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your video'];
                return back()->withNotify($notify);
            }
        }

        if ($video->status == Status::NO || $video->step < Status::SECOND_STEP) {
            $video->step = Status::SECOND_STEP;
        }

        $video->save();

        if ($playlist) {
            $playlist->videos()->syncWithoutDetaching([$video->id]);
        }

        $notify[] = ['success', 'Details successfully save'];
        if ($video->is_shorts_video) {
            return to_route('user.shorts.visibility.form', $video->id)->withNotify($notify);
        } else {
            return to_route('user.video.elements.form', $video->id)->withNotify($notify);
        }
    }

    public function visibilityForm($id = 0)
    {
        $pageTitle = 'Visibility';

        $video = Video::where('step', '>=', $this->shorts ? Status::SECOND_STEP : Status::THIRD_STEP)->with('tags')
            ->authUser()
            ->findOrFail($id);

        if ($this->shorts && !$video->is_shorts_video) {
            abort(404);
        }

        $categories = Category::active()->get();
        $action     = $this->view;

        return view('Template::user.' . $this->view . '.visibility_form', compact('pageTitle', 'video', 'categories', 'action'));
    }

    public function visibilitySubmit(Request $request, $id)
    {
        $request->validate([
            'category'   => 'required|integer',
            'tags'       => 'required|array|min:1',
            'tags.*'     => 'required|string',
            'visibility' => 'required|in:0,1',
        ]);

        $video = Video::where('step', '>=', $this->shorts ? Status::SECOND_STEP : Status::THIRD_STEP)
            ->authUser()
            ->findOrFail($id);

        if ($this->shorts && !$video->is_shorts_video) {
            abort(404);
        }

        $category           = Category::active()->findOrFail($request->category);
        $video->category_id = $category->id;

        $video->visibility = $request->visibility;

        if ($video->status == Status::NO || $video->step <= ($this->shorts ? Status::SECOND_STEP : Status::THIRD_STEP)) {
            $video->step   = $this->shorts ? Status::THIRD_STEP : Status::FOURTH_STEP;
            $video->status = Status::PUBLISHED;
        }

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

        $notify[] = ['success', 'Visibility successfully save'];

        if ($this->shorts && $video->is_shorts_video) {
            return to_route('user.shorts')->withNotify($notify);
        } else {
            return to_route('user.videos')->withNotify($notify);
        }
    }

    public function checkSlug()
    {
        $video = Video::where('step', '>=', Status::FIRST_STEP)->where('slug', request()->slug);
        if (request()->is_short) {
            $video->where('is_shorts_video', Status::YES);
        }
        $video = $video->exists();
        return response()->json([
            'exists' => $video,
        ]);
    }

    public function fetchData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails,statistics&id=' . $request->video_id . '&key=' . gs('google_api_key');

        $response = CurlRequest::curlContent($url);

        $jsonData = json_decode($response);

        if (@$jsonData->error) {
            $adminNotification            = new AdminNotification();
            $adminNotification->user_id   = auth()->id();
            $adminNotification->title     = $jsonData->error->message;
            $adminNotification->click_url = urlPath('admin.setting.general');
            $adminNotification->save();

            return response()->json([
                'remark'  => 'api_error',
                'status'  => 'error',
                'message' => ['error' => 'Something went wrong'],
            ]);
        }

        if ($jsonData->items) {
            $youtubeData  = $jsonData->items[0]->snippet;
            $thumbnailUrl = $youtubeData->thumbnails->maxres->url;
            $imageData    = file_get_contents($thumbnailUrl);

            if ($imageData === false) {
                throw new \Exception('Unable to fetch image from the URL.');
            }

            $finfo    = finfo_open();
            $mimeType = finfo_buffer($finfo, $imageData, FILEINFO_MIME_TYPE);
            finfo_close($finfo);

            $base64 = base64_encode($imageData);

            $base64Path = 'data:' . $mimeType . ';base64,' . $base64;

            return response()->json([
                'remark'  => 'fetch_success',
                'status'  => 'success',
                'message' => ['success' => 'Data fetched successfully'],
                'data'    => [
                    'title'         => $youtubeData->title,
                    'description'   => $youtubeData->description,
                    'thumbnail_url' => $thumbnailUrl,
                    'thumb_base64'  => $base64Path,
                ],
            ]);
        } else {
            return response()->json([
                'remark'  => 'fetch_error',
                'status'  => 'error',
                'message' => ['error' => 'Your video id is not valid'],
            ]);
        }
    }

    private function processVideo(UploadedFile $file, $uploadVideo, $id = null)
    {


        // $file = $request->file('video');

        try {
            $ffmpeg  = FFMpeg::create();
            $ffprobe = FFProbe::create();
        } catch (\Exception $e) {
            return (object) ['success' => false, 'message' => $e->getMessage()];
        }

        try {

            $fullPath = $file->getPathname();


            $videoStream = $ffprobe->streams($fullPath)->videos()->first();

            $width      = $videoStream->get('width');
            $height     = $videoStream->get('height');
            $resolution = $width . 'x' . $height;

            $resolutions = $this->getResolutions($resolution);

            if (!$resolutions) {
                return (object) ['success' => false, 'message' => 'Unsupported resolution'];
            }

            $outputDir = storage_path('app/encode');

            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            if ($id) {
                $oldFiles = $uploadVideo->videoFiles;
                foreach ($oldFiles as $file) {
                    if ($uploadVideo->storage_id == 0) {
                        File::delete(getFilePath('video') . '/' . $file->file_name);
                    } else {
                        $this->removeOldFile($uploadVideo, $uploadVideo->storage, $file->file_name, 'videos');
                    }
                }

                $uploadVideo->videoFiles()->delete();
            }

            foreach ($resolutions as $key => $res) {
                [$newWidth, $newHeight] = explode('x', $res);

                $uuid   = uniqid();
                $rdName = "{$uuid}_{$key}p.mp4";

                $outputFilePath = $outputDir . '/' . $rdName;

                $video = $ffmpeg->open($fullPath);
                $video->filters()->resize(new Dimension($newWidth, $newHeight));
                $format = new X264();
                $format->setAudioCodec('aac');
                $video->save($format, $outputFilePath);

                $path = getFilePath('video');

                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $destination = getFilePath('video') . '/' . now()->format('Y/F') . '/' . $rdName;

                File::move($outputFilePath, $destination);

                $videoFile            = new VideoFile();
                $videoFile->video_id  = $uploadVideo->id;
                $videoFile->file_name = now()->format('Y/F') . '/' . $rdName;
                $videoFile->quality   = $key;
                $videoFile->save();

                File::delete($outputFilePath);
            }

            File::delete($fullPath);

            return (object) ['success' => true, 'message' => 'Video saved successfully'];
        } catch (\Exception $e) {
            return (object) ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function getResolutions($resolution)
    {
        [$inputWidth, $inputHeight] = explode('x', $resolution);

        $resolutions = VideoResolution::active()->where('width', '<=', $inputWidth)->where('height', '<=', $inputHeight)->orderBy('width', 'desc')->orderBy('height', 'desc')->get();

        $availableResolutions = [];
        foreach ($resolutions as $res) {
            $key                        = $res->height;
            $availableResolutions[$key] = "{$res->width}x{$res->height}";
        }
        return $availableResolutions;
    }

    public function videoAnalytics($slug)
    {
        $video     = Video::where('slug', $slug)->authUser()->first();
        $pageTitle = $video->title . ' Analytics ';
        return view('Template::user.video.analytics', compact('pageTitle', 'video'));
    }

    public function videoChart(Request $request, $slug)
    {
        $video = Video::where('slug', $slug)->authUser()->first();

        $diffInDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));

        $groupBy = $diffInDays > 30 ? 'months' : 'days';
        $format  = $diffInDays > 30 ? '%M-%Y' : '%d-%M-%Y';

        if ($groupBy == 'days') {
            $dates = $this->getAllDates($request->start_date, $request->end_date);
        } else {
            $dates = $this->getAllMonths($request->start_date, $request->end_date);
        }

        $totalViews = Impression::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('user_id', auth()->id())
            ->where('video_id', $video->id)
            ->selectRaw('SUM(views) AS views')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $totalLike = UserReaction::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('user_id', auth()->id())
            ->where('video_id', $video->id)
            ->like()
            ->selectRaw('SUM(is_like) AS is_like')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $totalDislike = UserReaction::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('user_id', auth()->id())
            ->where('video_id', $video->id)
            ->dislike()
            ->selectRaw('SUM(is_like) AS is_like')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $totalComment = Comment::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->selectRaw('Count(comment) AS comment')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $clicks = AdvertisementAnalytics::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->selectRaw('SUM(click) AS click')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $impressions = AdvertisementAnalytics::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->selectRaw('SUM(impression) AS impression')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $earningFromAds = Transaction::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->where('user_id', auth()->id())
            ->where('remark', 'ads_revenue')
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $earningFromVideos = Transaction::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->where('user_id', auth()->id())
            ->where('remark', 'earn_from_video')
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $totalEarning = Transaction::whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->where('video_id', $video->id)
            ->where('user_id', auth()->id())
            ->whereIn('remark', ['earn_from_video', 'ads_revenue'])
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

        $report['created_on'] = $data->pluck('created_on');
        $report['data']       = [
            [
                'name' => 'Views',
                'data' => $data->pluck('total_views'),
            ],

            [
                'name' => 'Likes',
                'data' => $data->pluck('total_like'),
            ],
            [
                'name' => 'Dislikes',
                'data' => $data->pluck('total_dislike'),
            ],
            [
                'name' => 'Comments',
                'data' => $data->pluck('total_comment'),
            ],
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

        return response()->json($report);
    }

    public function fatchTags(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors());
        }

        $tags = VideoTag::searchable(['tag'])->groupBy('tag')
            ->select('id', 'tag')->groupBy('tag')
            ->paginate(getPaginate($request->rows ?? 10));

        $response = [];

        foreach ($tags as $tag) {
            $response[] = [
                'id'   => $tag->tag,
                'text' => $tag->tag,
            ];
        }
        return response()->json($response);
    }

    public function uploadLiveServer($id)
    {

        if (!gs('is_storage')) {
            return response()->json([
                'error' => 'Storage service not found. Please enable storage service.',
            ]);
        }

        try {
            $video = Video::authUser()->find($id);
            if (!$video) {
                return response()->json(['error' => 'Video not found.']);
            }

            if (!$video->is_shorts_video) {
                $videoFiles = $video->videoFiles;
                foreach ($videoFiles as $file) {
                    $path = getFilePath('video') . '/' . $file->file_name;


                    $response = $this->uploadServer($file->file_name, $path, $video, 'videos');

                    if ($response == false) {
                        return response()->json(['error' => 'Video upload failed on server']);
                    }
                }
            } else {
                $path = getFilePath('video') . '/' . $video->video;

                $this->uploadServer($video->video, $path, $video, 'videos');
            }

            $videoType = $video->is_shorts_video ? 'Short' : 'Video';
            return response()->json([
                'success' => 'Video uploaded successfully',
                'data'    => [
                    'video'      => $video,
                    'video_type' => $videoType,
                ],
            ]);
        } catch (\Throwable $e) {
            return (object) ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
