<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Playlist;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManagePlanController extends Controller {

    public function index() {
        abort_if(!gs('is_monthly_subscription'), 404);

        $pageTitle = "Manage Monthly Plan";
        $plans     = Plan::where('user_id', auth()->id())->orderBy('price')->searchable(['name'])->withCount('videos')->withCount('playlists')->paginate(getPaginate());
        return view('Template::user.plans.index', compact('pageTitle', 'plans'));
    }

    public function details($slug) {
        abort_if(!gs('is_monthly_subscription'), 404);

        $pageTitle = "Monthly Plan Details";
        $plan      = Plan::where('slug', $slug)->with('videos', 'playlists')->where('user_id', auth()->id())->firstOrFail();

        $planVideos    = $plan->videos()->paginate(getPaginate());
        $planPlaylists = $plan->playlists()->paginate(getPaginate());

        
        $user = $plan->user;
        return view('Template::user.plans.details', compact('pageTitle', 'planVideos', 'planPlaylists', 'user', 'plan'));
    }

    public function save(Request $request, $id = 0) {

        abort_if(!gs('is_monthly_subscription'), 404);

        $request->validate([
            'name'  => [
                'required',
                'string',
                'max:40',
                Rule::unique('plans')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })->ignore($id),
            ],
            'price' => 'required|numeric|gte:0',
        ]);

        if ($id) {
            $plan    = Plan::where('user_id', auth()->id())->findOrFail($id);
            $message = "Plan updated successfully";
        } else {
            $plan    = new Plan();
            $message = "Plan added successfully";
        }

        $plan->user_id = auth()->id();
        $plan->name    = $request->name;
        $plan->slug    = createUniqueSlug($request->name, Plan::class, $id);
        $plan->price   = $request->price;
        $plan->save();

        $notify[] = ["success", $message];
        return to_route('user.manage.plan.index')->withNotify($notify);
    }

    public function addVideo(Request $request, $id) {
        $request->validate([
            'video_id' => 'required|array|min:1',
        ]);

        $videoLits = Video::where('user_id', auth()->id())->published()->withoutOnlyPlaylist()->regular()->whereIn('id', $request->video_id)->get();
        if (count($videoLits) != count($request->video_id)) {
            $notify[] = ['error', 'Invalid video selected.'];
            return back()->withNotify($notify);
        }

        $plan = Plan::where('user_id', auth()->id())->findOrFail($id);

        $plan->videos()->attach($request->video_id);

        $notify[] = ['success', 'Video added successfully.'];
        return back()->withNotify($notify);
    }

    public function videoFetch($id) {
        $plan       = Plan::where('user_id', auth()->id())->find($id);
        $videoLists = Video::published()->public()->withoutOnlyPlaylist()->regular()->searchable(['title'])
            ->whereDoesntHave('plans', function ($q) use ($plan) {
                $q->where('plan_id', $plan->id);
            })
            ->where('user_id', auth()->id())
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

    public function addPlanPlaylist(Request $request, $id) {
        $request->validate([
            'playlist_id' => 'required|array|min:1',
        ]);

        $playlistLits = Playlist::where('user_id', auth()->id())->whereIn('id', $request->playlist_id)
            ->whereHas('videos', function ($query) {
                $query->regular()->published();
            })->get();
        if (count($playlistLits) != count($request->playlist_id)) {
            $notify[] = ['error', 'Invalid playlist selected.'];
            return back()->withNotify($notify);
        }

        $plan = Plan::where('user_id', auth()->id())->findOrFail($id);

        $plan->playlists()->attach($request->playlist_id);

        $notify[] = ['success', 'Video added successfully.'];
        return back()->withNotify($notify);
    }

    public function playlistFetch($id) {
        $plan = Plan::where('user_id', auth()->id())->find($id);

        $playLists = Playlist::where('user_id', auth()->id())->public()->searchable(['title'])->withCount('videos')
            ->whereHas('videos', function ($query) {
                $query->regular()->published();
            })
            ->whereDoesntHave('plans', function ($q) use ($plan) {
                $q->where('plan_id', $plan->id);
            })
            ->with('videos')
            ->latest()
            ->paginate(getPaginate());

        $transformedPlaylists = $playLists->through(function ($playlist) {
            $firstVideo = $playlist->videos->first();
            $thumbImage = $firstVideo ? $firstVideo->thumb_image : 'default.png';

            $imagePath = getImage(getFilePath('thumbnail') . '/' . $thumbImage);

            $playlist->image_path = $imagePath;

            return $playlist;
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'playlistLists' => $transformedPlaylists,
                'current_page'  => $playLists->currentPage(),
                'last_page'     => $playLists->lastPage(),
                'total'         => $playLists->total(),
            ],
        ]);
    }

    public function removeVideo($videoId, $planId) {
        $plan = Plan::where('user_id', auth()->id())->findOrFail($planId);
        $plan->videos()->detach($videoId);
        $notify[] = ['success', 'Video removed successfully.'];
        return back()->withNotify($notify);
    }

    public function removePlanPlaylist($playlistId, $planId) {
        $plan = Plan::where('user_id', auth()->id())->findOrFail($planId);
        $plan->playlists()->detach($playlistId);
        $notify[] = ['success', 'Playlist removed successfully.'];
        return back()->withNotify($notify);
    }

    public function status($id) {
        $plan = plan::where('user_id', auth()->id())->findOrFail($id);
        return $plan->changeStatus($id);
    }
}
