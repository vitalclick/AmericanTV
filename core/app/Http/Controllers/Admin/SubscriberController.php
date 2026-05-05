<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\User;


class SubscriberController extends Controller
{
    public function index($id=null)
    {
        $user='' ;
        if($id){

            $user = User::findOrFail($id);
            $pageTitle = 'Subscribers of '. $user->channel_name;
        }  else {
            $pageTitle = 'All Subscribers';
        }
        
        $subscribers = Subscriber::searchable(['followUser:username','followingUser:username'])->where(function($q) use ($user){
            if($user){
                $q->where('user_id', $user->id);
            }
        })->orderBy('id','desc')->with('followUser','followingUser')->paginate(getPaginate());
        return view('admin.subscriber.index', compact('pageTitle', 'subscribers'));
    }
}
