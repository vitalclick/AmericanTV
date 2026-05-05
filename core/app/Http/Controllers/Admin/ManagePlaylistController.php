<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Playlist;
use Illuminate\Http\Request;

class ManagePlaylistController extends Controller {
    public function index() {

        $pageTitle = "All Playlists";
        $playlists = Playlist::searchable(['title', 'user:username'])->with('user')->paginate(getPaginate());
        return view('admin.playlist.index', compact('pageTitle', 'playlists'));

    }

    public function update(Request $request, $id = 0) {

        $request->validate([
            'title'       => 'required|string|unique:playlists,title,' . $id,
            'description' => 'nullable|string',
            'visibility'  => 'required|in:0,1',
            'price'       => 'nullable|numeric',
            'playlist_subscription'  => 'nullable',
        ]);

        $playlist              = Playlist::findOrFail($id);
        $playlist->title       = $request->title;
        $playlist->description = $request->description;
        $playlist->visibility  = $request->visibility;
        $playlist->price       = $request->price;
        $playlist->playlist_subscription  = $request->playlist_subscription ? Status::ENABLE : Status::DISABLE;;
        $playlist->save();

        $notify[] = ['success', 'Playlist updated successfully.'];

        return back()->withNotify($notify);
    }

    public function videosList($id) {
        $playlist  = Playlist::findOrFail($id);
        $pageTitle = "Videos in " . $playlist->title;
        $videos    = $playlist->videos()->paginate(getPaginate());
        return view('admin.videos.index', compact('pageTitle', 'videos'));

    }

}
