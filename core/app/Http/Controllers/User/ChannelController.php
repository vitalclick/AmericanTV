<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\GatewayCurrency;
use App\Models\Playlist;
use App\Traits\ChannelManager;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChannelController extends Controller {
    use ChannelManager;

    public function create() {

        $user = auth()->user();

        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }
        $pageTitle  = "Create Channel";
        $info       = json_decode(json_encode(getIpInfo()), true);
        $mobileCode = @implode(',', $info['code']);
        $countries  = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        return view('Template::user.channel.form', compact('pageTitle', 'info', 'mobileCode', 'countries'));
    }

    public function channelDataSubmit(Request $request) {

        $user = auth()->user();

        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }

        $countryData  = (array) json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes  = implode(',', array_column($countryData, 'dial_code'));
        $countries    = implode(',', array_column($countryData, 'country'));

        $request->validate([
            'country_code' => 'required|in:' . $countryCodes,
            'country'      => 'required|in:' . $countries,
            'mobile_code'  => 'required|in:' . $mobileCodes,
            'username'     => 'required|unique:users|min:6',
            'mobile'       => ['required', 'regex:/^([0-9]*)$/', Rule::unique('users')->where('dial_code', $request->mobile_code)],
            'channel_name' => 'required|string|max:255',

        ]);

        if (preg_match("/[^a-z0-9_]/", trim($request->username))) {
            $notify[] = ['info', 'Username can contain only small letters, numbers and underscore.'];
            $notify[] = ['error', 'No special character, space or capital letters in username.'];
            return back()->withNotify($notify)->withInput($request->all());
        }

        $user->country_code = $request->country_code;
        $user->channel_name = $request->channel_name;
        $user->slug         = slug($request->channel_name) . "-" . $user->id;
        $user->mobile       = $request->mobile;
        $user->username     = $request->username;

        $user->address      = $request->address;
        $user->city         = $request->city;
        $user->state        = $request->state;
        $user->zip          = $request->zip;
        $user->country_name = @$request->country;
        $user->dial_code    = $request->mobile_code;

        $user->profile_complete = Status::YES;
        $user->save();

        return to_route('user.home');
    }

    public function playlistFetch($id) {

        $playlists = Playlist::where('user_id', $id)
            ->with([
                'user',
                'videos' => function ($q) {
                    $q->public()->published()->regular();
                },
            ])->whereHas('user', function ($query) {
            $query->active();
        })
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('name')->get();

        $html = view('Template::partials.playlist_list', compact('playlists', 'gatewayCurrency', ))->render();

        return response()->json([
            'remark' => 'playlists',
            'status' => 'success',
            'data'   => [
                'playlists'    => $html,
                'current_page' => $playlists->currentPage(),
                'last_page'    => $playlists->lastPage(),
            ],
        ]);

    }

}
