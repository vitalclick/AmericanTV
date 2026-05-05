<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoResolution;
use Illuminate\Http\Request;

class VideoResolutionController extends Controller
{
    public function index(){
        $pageTitle = 'Video Resolution';
        $resolutions = VideoResolution::searchable(['resolution_label'])->paginate(getPaginate());
        return view('admin.video_resolution.index', compact('pageTitle','resolutions'));

    }

    public function save(Request $request, $id=0){
        $request->validate([
            'resolution_label' => 'required|string|unique:video_resolutions,resolution_label,'.$id,
            'width' => 'required|numeric|unique:video_resolutions,width,' . $id,
            'height' => 'required|numeric|unique:video_resolutions,height,' . $id,
        ]);

            if($id){
                $resolution = VideoResolution::findOrFail($id);
                $notify[] =['success', 'Video resolution update successfully'];
            }else{
               $resolution = new VideoResolution();
               $notify[] =['success', 'Video resolution added successfully'];
            }
            
            $resolution->resolution_label = $request->resolution_label;
            $resolution->width = $request->width;
            $resolution->height = $request->height;
            $resolution->save();
            
            return back()->withNotify($notify);
    }

    public function status($id){

        return VideoResolution::changeStatus($id);
    }

}
