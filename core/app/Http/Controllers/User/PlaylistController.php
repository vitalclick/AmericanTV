<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlaylistController extends Controller {

    public function playlist() {
        $pageTitle = 'Playlists';
        $playlists = Playlist::where('user_id', auth()->id())
            ->whereHas('videos', function ($query) {
                $query->regular()->published();
            })
            ->with([
                'user',
                'videos' => function ($q) {
                    $q->public()->published()->regular();
                },
            ])->whereHas('user', function ($query) {
            $query->active();
        })
            ->latest()
            ->paginate(getPaginate());

        return view('Template::playlists', compact('playlists', 'pageTitle'));
    }

    public function playlistVideos($slug) {

        $playlist = Playlist::public()->where('slug', $slug)
            ->whereHas('videos', function ($query) {
                $query->public()->published()->regular();
            })
            ->with([
                'user',
                'videos' => function ($q) {
                    $q->public()->published()->regular();
                },
            ])->whereHas('user', function ($query) {
            $query->active();
        })->firstOrFail();

        $pageTitle = $playlist->title . ' Videos';
        $videos    = $playlist->videos()->paginate(getPaginate());
        return view('Template::playlist_videos', compact('playlist', 'pageTitle', 'videos'));
    }

    public function loadVideos($id) {

        $playlist = Playlist::public()
            ->whereHas('videos', function ($query) {
                $query->public()->published()->regular();
            })
            ->with([
                'user',
                'videos' => function ($q) {
                    $q->public()->published()->with('videoFiles', 'subtitles', 'user')->regular();
                },
            ])
            ->find($id);

        if (!$playlist) {
            return response()->json([
                'status' => 'error',
                'data'   => [
                    'message' => 'Playlist not found',
                ],
            ]);
        }

        $videos = $playlist->videos()->with('videoFiles', 'subtitles', 'user')->paginate(getPaginate());

        $html = view('Template::partials.video.video_list', compact('videos'))->render();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'videos'       => $html,
                'current_page' => $videos->currentPage(),
                'last_page'    => $videos->lastPage(),
            ],
        ]);

    }

    public function loadPlaylists() {

        $playlists = Playlist::public()
            ->whereHas('videos', function ($query) {
                $query->regular()->published();
            })
            ->with([
                'user',
                'videos' => function ($q) {
                    $q->public()->published()->regular();
                },
            ])->whereHas('user', function ($query) {
            $query->active();
        })
            ->latest()
            ->paginate(getPaginate());

        $html = view('Template::partials.playlist_list', compact('playlists'))->render();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'playlists'    => $html,
                'current_page' => $playlists->currentPage(),
                'last_page'    => $playlists->lastPage(),
            ],
        ]);
    }

    public function save(Request $request, $id = 0) {
        $title     = $request->title;
        $validator = Validator::make($request->all(), [
            'title'                 => [
                'required',
                'string',
                'unique' => function ($attribute, $value, $fail) use ($title, $id) {
                    $query = Playlist::where('title', $title)->where('user_id', auth()->id());
                    if (!$id) {

                        if ($query->exists()) {
                            $fail('The ' . $attribute . ' must be unique.');
                        }
                    }
                },
            ],
            'description'           => 'nullable|string',
            'visibility'            => 'required|in:0,1',
            'slug'                  => "required|string|unique:playlists,slug," . $id,
            'playlist_subscription' => 'nullable',
            'price'                 => [
                'nullable',
                'numeric',
                Rule::requiredIf(function () use ($request) {
                    return $request->input('playlist_subscription') == 1;
                }),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('playlist_subscription') == 1 && $value <= 0) {
                        $fail('The ' . $attribute . ' must be greater than 0 when subscription is enabled.');
                    }
                },
            ],
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            if ($request->ajax()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => ['error' => $errors],
                ]);
            } else {
                $notify = array_map(fn($error) => ['error', $error], $errors);
                return back()->withNotify($notify);
            }
        }

        if ($id) {
            $playlist = Playlist::where('user_id', auth()->id())->findOrFail($id);
            $notify[] = ['success', 'Playlist updated successfully.'];
        } else {
            $playlist = new Playlist();
            $notify[] = ['success', 'Playlist created successfully.'];
        }

        $playlist->title                 = $request->title;
        $playlist->slug                  = $request->slug;
        $playlist->user_id               = auth()->id();
        $playlist->description           = $request->description;
        $playlist->visibility            = $request->visibility;
        $playlist->price                 = $request->price ?? 0;
        $playlist->playlist_subscription = $request->playlist_subscription ? Status::ENABLE : Status::DISABLE;
        $playlist->save();

        if ($request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => ['success' => 'Playlist create successfully'],
            ]);
        }
        return back()->withNotify($notify);
    }

    public function addVideo(Request $request) {
        $validator = Validator::make($request->all(), [
            'video_id' => 'required|array|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $videoLits = Video::published()->whereIn('id', $request->video_id)->get();
        if (count($videoLits) != count($request->video_id)) {
            $notify[] = ['error', 'Invalid video selected.'];
            return back()->withNotify($notify);
        }

        $playlist = Playlist::where('user_id', auth()->id())->findOrFail($request->playlist_id);

        $playlist->videos()->attach($request->video_id);

        $notify[] = ['success', 'Video added successfully.'];
        return back()->withNotify($notify);

    }

// for modal
    public function videoFetch($id) {
        $playlist = Playlist::where('user_id', auth()->id())->find($id);

        if (!$playlist) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Playlist not found.',
            ]);
        }

        $videoLists = Video::published()->regular()
            ->where('user_id', auth()->id())->searchable(['title'])
            ->whereDoesntHave('playlists', function ($q) use ($playlist) {
                $q->where('playlist_id', $playlist->id);
            })
            ->paginate(getPaginate());

        return response()->json([
            'status' => 'success',
            'data'   => [
                'videoLists'   => $videoLists,
                'current_page' => $videoLists->currentPage(),
                'last_page'    => $videoLists->lastPage(),
                'total'        => $videoLists->total(),
            ],
        ]);
    }

    public function videoGet($id) {

        $playlist = Playlist::find($id);

        if ($playlist) {
            $videos = $playlist->videos()->where(function ($q) use ($playlist) {
                if ($playlist->user_id != auth()->id()) {
                    $q->public();
                }
            })->published()->paginate(getPaginate());

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
    }

    public function removeVideo($videoId, $playlistId) {
        $playlist = Playlist::where('user_id', auth()->id())->findOrFail($playlistId);
        $playlist->videos()->detach($videoId);
        $notify[] = ['success', 'Video removed successfully.'];
        return back()->withNotify($notify);
    }

    public function checkPlaylistSlug() {
        $video = Playlist::where('slug', request()->slug)->exists();
        return response()->json([
            'exists' => $video,
        ]);
    }

}
