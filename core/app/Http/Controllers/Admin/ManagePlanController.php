<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class ManagePlanController extends Controller {
    public function index() {

        $pageTitle = "All Monthly Plan";
        $plans     = Plan::searchable(['name', 'user:username'])->with('user')->withCount('videos')->withCount('playlists')->paginate(getPaginate());
        return view('admin.plan.index', compact('pageTitle', 'plans'));
    }

    public function update(Request $request, $id = 0) {
        $request->validate([
            'name'  => 'required|string|max:40',
            'price' => 'required|numeric|gt:0',
        ]);

        $plan = Plan::findOrFail($id);

        $plan->name  = $request->name;
        $plan->slug  = createUniqueSlug($request->name, Plan::class, $id);
        $plan->price = $request->price;
        $plan->save();

        $notify[] = ["success", "Plan updated successfully"];
        return back()->withNotify($notify);
    }

    public function videosList($id) {
        $plan      = Plan::findOrFail($id);
        $pageTitle = "Videos in " . $plan->name;
        $videos    = $plan->videos()->searchable(['title'])->paginate(getPaginate());
        return view('admin.videos.index', compact('pageTitle', 'videos'));
    }

    public function playlistList($id) {
        $plan      = Plan::findOrFail($id);
        $pageTitle = "Playlists in " . $plan->name;
        $playlists = $plan->playlists()->searchable(['title'])->paginate(getPaginate());
        return view('admin.playlist.index', compact('pageTitle', 'playlists'));
    }

    public function status($id) {
        return Plan::changeStatus($id);
    }
}
