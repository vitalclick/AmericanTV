<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Category;
use App\Rules\FileTypeValidate;
use App\Traits\AdminAdsManage;
use App\Traits\StorageDriver;
use Illuminate\Http\Request;

class ManageAdvertisementController extends Controller
{
     public function __construct()
    {
      if(gs('ads_module')){
          abort(404);
      }
        parent::__construct();
        $this->view = 'advertisements';

    }

    use StorageDriver , AdminAdsManage;
   
    public function impression($id = null)
    {
        $pageTitle      = "Ad Type Impression";
        $advertisements = $this->advertisementData('impression', $id);
        return view('admin.advertisements.index', compact('advertisements', 'pageTitle'));
    }

    public function click($id = null)
    {
        $pageTitle      = "Ad Type Click";
        $advertisements = $this->advertisementData('click', $id);
        return view('admin.advertisements.index', compact('advertisements', 'pageTitle'));
    }

    public function both($id = null)
    {
        $pageTitle      = "Ad Type Both";
        $advertisements = $this->advertisementData('both', $id);
        return view('admin.advertisements.index', compact('advertisements', 'pageTitle'));
    }

    

 

    public function edit($id)
    {
        $advertisement = Advertisement::with('user')->findOrFail($id);
        $pageTitle     = "Edit " . $advertisement->title . " Advertisement";

        $categories = Category::active()->get();
        return view('admin.advertisements.edit', compact('advertisement', 'pageTitle', 'categories'));
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'title'         => 'required|string',
            'category_id'   => 'required|array|min:1',
            'category_id.*' => 'integer',
            'ad_video'      => ['nullable', new FileTypeValidate(['mp4', 'mov', 'wmv', 'flv', 'avi', 'mkv'])],
            'logo'          => ['nullable', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'url'           => 'nullable|url|required_if:ad_type,2,3',
            'ad_type'       => 'required|numeric',
            'impression'    => 'nullable|numeric|required_if:ad_type,1,3',
            'click'         => 'nullable|numeric|required_if:ad_type,2,3',
            'button_label'  => 'nullable|string|required_if:ad_type,2,3',
        ], [
            'title.required'         => 'Title is required',
            'category_id.required'   => 'Please select at least one category',
            'category_id.*.integer'  => 'Each category must be a valid integer',
            'ad_video.required'      => 'Please upload an ad video',
            'url.url'                => 'Invalid URL',
            'ad_type.required'       => 'Please select Ad type',
            'impression.required_if' => 'Please enter an impression value',
            'click.required_if'      => 'Please enter a click value',
        ]);

        $categories = Category::whereIn('id', $request->category_id)->get();

        if (count($categories) != count($request->category_id)) {
            $notify[] = ['error', 'Please select valid categories'];
            return back()->withNotify($notify);
        }

        $advertisement = Advertisement::findOrFail($id);

        $advertisement->title = $request->title;
        if ($request->hasFile('ad_video')) {
            try {
                $fileName = now()->format('Y/F') . '/' . uniqid() . time() . '.' . $request->ad_video->getClientOriginalExtension();
                $advertisement->ad_file = fileUploader($request->ad_video, getFilePath('adVideo'). '/' . now()->format('Y/F'),filename:$fileName);
            } catch (\Exception $exp) {
                $notify[] = ['error' => 'Couldn\'t upload your video'];
                return back()->withNotify($notify);
            }
        }

        if ($request->hasFile('logo')) {
            try {
                $advertisement->logo = fileUploader($request->logo, getFilePath('adLogo'));
            } catch (\Exception $exp) {
                $notify[] = ['error' => 'Couldn\'t upload your video'];
                return back()->withNotify($notify);
            }
        }
        $advertisement->url          = $request->url;
        $advertisement->button_label = $request->button_label;
        $advertisement->ad_type      = $request->ad_type;
        $advertisement->impression   = $request->impression ?? 0;
        $advertisement->click        = $request->click ?? 0;
        $advertisement->total_amount = $request->total_amount;
        $advertisement->status       = $request->status ? Status::RUNNING : Status::PAUSE;

        $advertisement->save();

        $advertisement->categories()->sync($request->category_id);

        if (@$advertisement->storage && $request->hasFile('ad_video')) {
            $path = getFilePath('adVideo') . '/' . $advertisement->ad_file;
            $this->uploadServer( $advertisement->ad_file,$path,$advertisement, 'ads');
     
        }

        $notify[] = ['success', 'Advertisement updated successfully'];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        $advertisement         = Advertisement::findOrFail($id);
        $advertisement->status = $advertisement->status == Status::RUNNING ? Status::PAUSE : Status::RUNNING;
        $advertisement->save();
        $notify[] = ['success', 'Status has been changed'];
        return back()->withNotify($notify);
    }

   
}
