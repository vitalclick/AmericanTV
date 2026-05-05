<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Traits\VideoManager;
use Illuminate\Http\Request;

class ShortsController extends Controller
{
    use VideoManager;

    public function __construct()
    {
    
        parent::__construct();
        $this->view = 'shorts';
        $this->shorts = true;
    }

    public function editShorts($id){
        $video = Video::authUser()->findOrFail($id);
        if($video->step == 1){
            return redirect()->route('user.shorts.details.form', $video->id);
        }else if($video->step == 2){
            return redirect()->route('user.shorts.visibility.form', $video->id);
        }else{
            return redirect()->route('user.shorts.upload.form', $video->id);
        }
    }



}
