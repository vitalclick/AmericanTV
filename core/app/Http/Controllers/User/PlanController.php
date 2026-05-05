<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Playlist;
use App\Models\PurchasedPlan;

class PlanController extends Controller {
    public function viewPlanVideos($id) {
        $plan   = Plan::find($id);
        $query  = $plan->videos()->where('status', Status::PUBLISHED);
        $videos = $query->paginate(getPaginate());

        return response()->json([
            'status' => 'success',
            'data'   => [
                'videos'    => $videos,
                'last_page' => $videos->lastPage(),
            ],
        ]);
    }

    public function viewPlaylistVideos($id) {
        $playlist = Playlist::find($id);
        $query    = $playlist->videos()->where('status', Status::PUBLISHED);
        $videos   = $query->paginate(getPaginate());
        return response()->json([
            'status' => 'success',
            'data'   => [
                'videos'    => $videos,
                'last_page' => $videos->lastPage(),
            ],
        ]);
    }

    public function viewPlanPlaylists($id) {
        $plan = Plan::findOrFail($id);

        $query = $plan->playlists();

        $playlists = $query->withCount('videos')->with('user')->paginate(getPaginate());

        foreach ($playlists as $playlist) {
            $firstVideo           = $playlist->videos()->where('status', Status::PUBLISHED)->first();
            $playlist->image_path = $firstVideo ?
            getImage(getFilePath('thumbnail') . '/' . $firstVideo->thumb_image) :
            getImage(getFilePath('default'));
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'playlists' => $playlists,
                'last_page' => $playlists->lastPage(),
            ],
        ]);
    }

    public function purchasedPlanLists() {
        abort_if(!gs('is_monthly_subscription'), 404);

        $pageTitle = "My Purchased Plans";

        $plans = PurchasedPlan::where('user_id', auth()->id())
            ->where('expired_date', '>', now())
            ->with(['plan' => function ($q) {
                $q->withCount(['videos', 'playlists']);
            }])
            ->searchable(['plan:name'])
            ->paginate(getPaginate());

        return view('Template::user.plans.purchased', compact('pageTitle', 'plans'));
    }

    public function planSellHistory() {
        abort_if(!gs('is_monthly_subscription'), 404);
        $pageTitle = 'Plan Sell History';
        $sellPlans = PurchasedPlan::where('owner_id', auth()->id())->orderBy('id', 'desc')->with('user', 'plan')->searchable(['user:username', 'plan:name'])->paginate(getPaginate());
        return view('Template::user.plans.sell', compact('pageTitle', 'sellPlans'));
    }

}
