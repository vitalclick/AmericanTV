<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Models\Subtitle;
use App\Models\Video;
use App\Rules\FileTypeValidate;
use App\Traits\VideoManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
    use VideoManager;

    public function __construct()
    {
        parent::__construct();
        $this->view = 'video';
    }

    public function elementsForm($id = 0)
    {
        abort_if(!$id, 404);

        $pageTitle = "Elements Details";
        $video     = Video::where('step', '>=', Status::SECOND_STEP)->authUser()->where('is_shorts_video', Status::NO)->findOrFail($id);
        return view('Template::user.video.elements', compact('pageTitle', 'video'));
    }

    public function elementsSubmit(Request $request, $id)
    {
        $request->validate([
            'audience'        => 'required|in:0,1',
            'price'           => 'required_with:stock_video',
            'caption.*'       => [
                'sometimes',
                function ($value, $fail) use ($request) {
                    if ($request->hasAny(['subtitle_file', 'language_code'])) {
                        if (empty($value)) {
                            $fail('Caption is required when subtitle file or language code is present.');
                        }
                    }
                },
            ],
            'language_code.*' => [
                'sometimes',
                'string',
                function ($value, $fail) use ($request) {
                    if ($request->hasAny(['subtitle_file', 'caption'])) {
                        if (empty($value)) {
                            $fail('Language code is required when caption or subtitle file is present.');
                        }
                    }
                },
            ],
            'subtitle_file.*' => [
                'sometimes',
                new FileTypeValidate(['vtt']),
                function ($value, $fail) use ($request) {
                    if ($request->hasAny(['caption', 'language_code'])) {
                        if (empty($value)) {
                            $fail('Subtitle file is required when caption or language code is present.');
                        }
                    }
                },
            ],
        ], [
            'caption.*'       => 'Caption is required when subtitle file or language code is present.',
            'language_code.*' => "Language code must be a string and is required when caption or subtitle file is present.",
            'subtitle_file.*' => "Invalid subtitle format. Subtitle file is required when caption or language code is present.",
        ]);

        $video = Video::where('step', '>=', Status::SECOND_STEP)->authUser()->where('is_shorts_video', Status::NO)->findOrFail($id);

        if ($request->old_subtitle) {
            $removeSub = array_diff($video->subtitles->pluck('id')->toArray(), $request->old_subtitle ?? []);
        } else {
            $removeSub = $video->subtitles->pluck('id')->toArray();
        }

        $video->subtitles()->whereIn('id', $removeSub)->get()->each(function ($old) {
            $filePath = getImage(getFilePath('subtitle') . '/' . $old->file);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $old->delete();
        });

        $video->audience = $request->audience;
        if ($request->stock_video) {
            $video->stock_video = Status::YES;
            $video->price       = $request->price;
        } else {
            $video->stock_video = Status::NO;
            $video->price       = 0;
        }
        $video->audience = $request->audience;

        if ($video->status == Status::NO || $video->step < Status::THIRD_STEP) {

            $video->step = Status::THIRD_STEP;
        }

        $video->save();

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

        $notify[] = ['success', 'Elements successfully save'];
        return to_route('user.video.visibility.form', $video->id)->withNotify($notify);
    }

    public function editVideo($id)
    {
        $id    = decrypt($id);
        $video = Video::authUser()->findOrFail($id);
        if ($video->step == Status::FIRST_STEP) {
            return redirect()->route('user.video.details.form', $video->id);
        } else if ($video->step == Status::SECOND_STEP) {
            return redirect()->route('user.video.elements.form', $video->id);
        } else if ($video->step == Status::THIRD_STEP) {
            return redirect()->route('user.video.visibility.form', $video->id);
        } else {
            return redirect()->route('user.video.upload.form', $video->id);
        }
    }

    public function addPlaylist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video_id'      => 'required|integer',
            'playlist_id'   => 'required|array|min:1',
            'playlist_id.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'remark'  => 'validation_error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $video = Video::published()->whereHas('user', function ($q) {
            $q->active();
        })->findOrFail($request->video_id);

        $playlists = Playlist::where('user_id', auth()->id())->whereIn('id', $request->playlist_id)->get();
        if (count($playlists) != count($request->playlist_id)) {
            return response()->json(['error' => "Something went wrong"]);
        }

        $video->playlists()->detach();
        $video->playlists()->attach($request->playlist_id);
        return response()->json(['success' => "Video successfully added to the playlist"]);
    }
}
