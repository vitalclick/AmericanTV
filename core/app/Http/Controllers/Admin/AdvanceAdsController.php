<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Traits\AdminAdsManage;
use Illuminate\Http\Request;

class AdvanceAdsController extends Controller
{
    public function __construct()
    {
        if (!gs('ads_module')) {
            abort(404);
        }
        parent::__construct();
        $this->view = 'advance_ads';
    }

    use AdminAdsManage;


    public function detail($id)
    {
        $advertisement = Advertisement::with('campaign', 'countries', 'schedules')->findOrFail($id);
        $pageTitle = 'Detail of ' . $advertisement->title;
        $campaign = $advertisement->campaign;
        $countries  = json_decode(file_get_contents(resource_path('views/partials/country.json')));
        return view('admin.' . $this->view . '.detail', compact('advertisement','pageTitle','campaign','countries'));
    }

    public function status($id){
        $advertisement = Advertisement::findOrFail($id);
        $advertisement->status = $advertisement->status == Status::RUNNING ? Status::PAUSE : Status::RUNNING;
        $advertisement->save();
        $notify[] = ['success', 'Status changed successfully'];
        return back()->withNotify($notify);
    }

    public function approved($id){
        $advertisement = Advertisement::where('status', Status::ADVERTISEMENT_PENDING)->findOrFail($id);
        $advertisement->status = Status::RUNNING;
        $advertisement->save();
        $notify[] = ['success', 'Advertisement Approved successfully'];
        return back()->withNotify($notify);
    }

  


    public function reject(Request $request, $id) {
        $request->validate([
        
            'message' => 'required|string|max:255',
        ]);
        $advertisement = Advertisement::where('status', Status::ADVERTISEMENT_PENDING)->findOrFail($id);

        $advertisement->reject_reason = $request->message;
        $advertisement->status         = Status::ADVERTISEMENT_REJECTED;
        $advertisement->save();

  
        notify($advertisement->user, 'ADVERTISEMENT_REJECT', [
            'title' => $advertisement->title,
            'rejection_message' => $request->message,
        ]);

        $notify[] = ['success', 'Advertisement rejected successfully'];
        return back()->withNotify($notify);

    }


}
