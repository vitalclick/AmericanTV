<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\HolidayCalculator;
use App\Models\Withdrawal;
use App\Models\WithdrawMethod;
use App\Models\WithdrawSetting;
use Illuminate\Http\Request;

class WithdrawController extends Controller {

    public function withdrawMethod() {
        $user           = auth()->user();
        $withdrawMethod = WithdrawMethod::active()->get();
        $pageTitle      = 'Withdraw Method';
        return view('Template::user.withdraw.methods', compact('pageTitle', 'withdrawMethod', 'user'));
    }

    public function withdrawMethodSubmit(Request $request) {
        $validation = [
            'method_code' => 'required',
            'amount'      => 'required|numeric|gt:0',
        ];

        $method   = WithdrawMethod::where('id', $request->method_code)->where('status', Status::ENABLE)->firstOrFail();
        $formData = $method->form->form_data;

        $user            = auth()->user();
        $withdrawSetting = $user->withdrawSetting;

        $formProcessor  = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $validation     = array_merge($validation, $validationRule);

        if (!$withdrawSetting) {
            $withdrawSetting = new WithdrawSetting();
        } else {
            foreach (@$withdrawSetting->user_data ?? [] as $data) {
                foreach ($formData as $getData) {
                    if ($getData->name == $data->name && $data->value && $data->type == 'file' && $getData->type == 'file') {
                        @$validation[$getData->label][0] = 'nullable';
                    }
                }
            }
        }

        $request->validate($validation);

        if ($request->amount < $method->min_limit) {
            $notify[] = ['error', 'Your requested amount is smaller than minimum amount.'];
            return back()->withNotify($notify)->withInput();
        }
        if ($request->amount > $method->max_limit) {
            $notify[] = ['error', 'Your requested amount is larger than maximum amount.'];
            return back()->withNotify($notify)->withInput();
        }

        $userData = $formProcessor->processFormData($request, $formData);

        foreach (@$withdrawSetting->user_data ?? [] as $index => $data) {
            foreach ($formData as $getData) {
                if ($getData->name == $data->name && $data->value && $data->type == 'file' && $getData->type == 'file') {
                    if (!$userData[$index]['value']) {
                        $userData[$index]['value'] = $data->value;
                    }
                }
            }
        }

        $withdrawSetting->user_id            = $user->id;
        $withdrawSetting->withdraw_method_id = $method->id;
        $withdrawSetting->amount             = $request->amount;
        $withdrawSetting->user_data          = $userData;
        $withdrawSetting->next_withdraw_date = HolidayCalculator::nextWorkingDay($withdrawSetting);
        $withdrawSetting->save();

        $notify[] = ['success', 'Withdraw setting updated successfully'];
        return back()->withNotify($notify);
    }

    public function withdrawLog(Request $request) {
        $pageTitle = "Withdrawal Log";
        $withdraws = Withdrawal::where('user_id', auth()->id())->where('status', '!=', Status::PAYMENT_INITIATE);
        if ($request->search) {
            $withdraws = $withdraws->where('trx', $request->search);
        }
        $withdraws = $withdraws->with('method')->orderBy('id', 'desc')->paginate(getPaginate());
        return view('Template::user.withdraw.log', compact('pageTitle', 'withdraws'));
    }

    public function downloadAttachment($fileHash) {
        try {
            $attachment = decrypt($fileHash);
            $file       = $attachment;
            $path       = getFilePath('verify');
            $full_path  = $path . '/' . $file;
            $title      = 'Attachment';
            $ext        = pathinfo($file, PATHINFO_EXTENSION);
            $mimetype   = mime_content_type($full_path);
            header('Content-Disposition: attachment; filename="' . $title . '.' . $ext . '";');
            header("Content-Type: " . $mimetype);
            return readfile($full_path);
        } catch (\Exception $error) {
            $notify[] = ['error', $error->getMessage()];
            return back()->withNotify($notify);
        }
    }

}
