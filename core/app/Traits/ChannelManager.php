<?php

namespace App\Traits;

use App\Constants\Status;
use App\Models\GatewayCurrency;
use App\Models\Plan;
use App\Models\Playlist;
use App\Models\User;
use App\Models\Video;

trait ChannelManager {
    protected $guest = false;

    public function channel($slug = null) {
        $user = auth()->user();
        if ($slug) {
            $user = User::where('profile_complete', Status::YES)
                ->active()->where('slug', $slug)
                ->firstOrFail();
        }
        $pageTitle = 'Channel Profile';

        $videos = Video::where('user_id', $user->id)
            ->where(function ($query) use ($user) {
                if ($user->id != auth()->id()) {
                    $query->public()->withoutOnlyPlaylist();
                }
            })
            ->with('subtitles', 'videoFiles')
            ->regular()
            ->latest()
            ->published()
            ->paginate(getPaginate());

        $subscriberCount = $user->subscribers()->count();
        $videosCount     = $user->videos()->published()->count();
        $bladeName       = 'profile';

        return view('Template::user.channel.channel_preview', compact('user', 'pageTitle', 'videos', 'bladeName', 'subscriberCount', 'videosCount'));
    }

    public function shorts($slug = null) {
        if ($slug) {

            $user = User::where('profile_complete', Status::YES)
                ->active()->where('slug', $slug)
                ->firstOrFail();
        }

        if ($user->profile_complete == Status::NO) {
            return to_route('user.channel.create');
        }

        $pageTitle = 'All Shorts';
        $videos    = Video::where('user_id', $user->id)
            ->where(function ($query) use ($user) {
                if ($user->id != auth()->id()) {
                    $query->public();
                }
            })
            ->with('subtitles')
            ->shorts()
            ->latest()
            ->published()
            ->paginate(getPaginate());

        $subscriberCount = $user->subscribers()->count();
        $videosCount     = $user->videos()->published()->count();
        $bladeName       = 'shorts';

        return view('Template::user.channel.channel_preview', compact('user', 'subscriberCount', 'videosCount', 'pageTitle', 'videos', 'bladeName'));
    }

    public function playlist($slug = null) {
        if ($slug) {
            $user = User::where('profile_complete', Status::YES)
                ->active()->where('slug', $slug)
                ->firstOrFail();
        }

        if ($user->profile_complete == Status::NO) {
            return to_route('user.channel.create');
        }

        $pageTitle = 'Playlist';
        $playlists = Playlist::where('user_id', $user->id)
            ->where(function ($query) use ($user) {
                if ($user->id != auth()->id()) {
                    $query->public();
                }
            })->orderBy('id', 'desc')
            ->paginate(getPaginate());

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();

        $subscriberCount = $user->subscribers()->count();
        $videosCount     = $user->videos()->published()->count();
        $bladeName       = 'playlist';

        return view('Template::user.channel.channel_preview', compact('pageTitle', 'videosCount', 'subscriberCount', 'gatewayCurrency', 'playlists', 'bladeName', 'user'));
    }

    public function about($slug = null) {
        if ($slug) {
            $user = User::where('profile_complete', Status::YES)->active()->where('slug', $slug)->firstOrFail();
        }
        if ($user->profile_complete == Status::NO) {
            return to_route('user.channel.create');
        }

        $subscriberCount = $user->subscribers()->count();
        $videosCount     = $user->videos()->published()->count();

        $bladeName = 'about';
        $pageTitle = 'About Channel';
        return view('Template::user.channel.channel_preview', compact('pageTitle', 'videosCount', 'subscriberCount', 'bladeName', 'user'));
    }

    public function monthlyPlan($slug) {

        abort_if(!gs('is_monthly_subscription'), 404);

        $user = User::where('profile_complete', Status::YES)->active()->where('slug', $slug)->firstOrFail();

        if ($user->profile_complete == Status::NO) {
            return to_route('user.channel.create');
        }

        $plans = Plan::active()->withVideosOrPlaylists()
            ->where('user_id', $user->id)
            ->with('videos', 'playlists')
            ->orderBy('price')
            ->get();

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();

        $subscriberCount = $user->subscribers()->count();
        $videosCount     = $user->videos()->published()->count();

        $bladeName = 'monthly_plan';
        $pageTitle = 'About Channel';
        return view('Template::user.channel.channel_preview', compact('pageTitle', 'videosCount', 'subscriberCount', 'gatewayCurrency', 'plans', 'bladeName', 'user'));
    }

    public function playlistVideos($playlistSlug, $slug = null) {
        if ($slug) {
            $user = User::where('profile_complete', Status::YES)
                ->active()->where('slug', $slug)
                ->firstOrFail();
        }

        $playlist = Playlist::where('user_id', $user->id)->where('slug', $playlistSlug)
            ->where(function ($query) use ($user) {
                if ($user->id != auth()->id()) {
                    $query->public();
                }
            })
            ->firstOrFail();

        $videos = $playlist
            ->videos()
            ->where(function ($q) use ($user) {
                if ($user->id != auth()->id()) {
                    $q->public();
                }
            })
            ->published()
            ->paginate(getPaginate());

        $videoLists = Video::published()
            ->where('user_id', $user->id)
            ->whereDoesntHave('playlists', function ($q) use ($playlist) {
                $q->where('playlist_id', $playlist->id);
            })
            ->paginate(getPaginate());

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();

        $subscriberCount = $user->subscribers()->count();
        $videosCount     = $user->videos()->published()->count();

        $bladeName = 'videos';
        $pageTitle = 'Playlists Videos';

        return view('Template::user.channel.channel_preview', compact('pageTitle', 'videosCount', 'subscriberCount', 'gatewayCurrency', 'bladeName', 'playlist', 'user', 'videos', 'videoLists'));
    }
}
