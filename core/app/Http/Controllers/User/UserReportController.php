<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PurchasedPlaylist;
use App\Models\Transaction;

class UserReportController extends Controller
{

    public function transactions()
    {
        $pageTitle = 'Transactions';
        $remarks = Transaction::distinct('remark')->orderBy('remark')->get('remark');
        $transactions = Transaction::where('user_id', auth()->id())
            ->searchable(['trx'])
            ->filter(['trx_type', 'remark'])
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());
        return view('Template::user.transactions', compact('pageTitle', 'transactions', 'remarks'));
    }

    public function purchasedHistory(){
        $user = auth()->user();
        $pageTitle = 'Video Purchased History';
        $purchasedVideos = $user->purchasedVideos()->with('video')->searchable(['trx', 'video:title'])->paginate(getPaginate());
        return view('Template::user.video_purchased_history', compact('purchasedVideos','pageTitle'));
    }

    public function playlistPurchasedHistory(){

        abort_if(!gs('is_playlist_sell'), 404);

        $user = auth()->user();
        $pageTitle = 'Playlist Purchased History';
        $purchasedPlaylists = $user->purchasedPlaylists()->with('playlist')->searchable(['trx', 'playlist:title'])->paginate(getPaginate());
        return view('Template::user.playlists.purchased', compact('purchasedPlaylists','pageTitle'));
    }

    public function playlistSellHistory()
    {
        abort_if(!gs('is_playlist_sell'), 404);
        
        $pageTitle = 'Playlist Sell History';
        $sellPlaylists = PurchasedPlaylist::where('owner_id', auth()->id())->orderBy('id', 'desc')->with('user', 'playlist')->searchable(['user:username', 'playlist:title'])->paginate(getPaginate());
        return view('Template::user.playlists.sell', compact('pageTitle', 'sellPlaylists'));
    }


}
