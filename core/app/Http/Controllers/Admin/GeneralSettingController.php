<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\RequiredConfig;
use App\Models\Frontend;
use App\Models\Holiday;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;

class GeneralSettingController extends Controller
{
    public function systemSetting()
    {
        $pageTitle = 'System Settings';
        $settings  = json_decode(file_get_contents(resource_path('views/admin/setting/settings.json')));
        return view('admin.setting.system', compact('pageTitle', 'settings'));
    }
    public function general()
    {
        $pageTitle       = 'General Setting';
        $timezones       = timezone_identifiers_list();
        $currentTimezone = array_search(config('app.timezone'), $timezones);
        return view('admin.setting.general', compact('pageTitle', 'timezones', 'currentTimezone'));
    }

    public function generalUpdate(Request $request)
    {
        $request->validate([
            'site_name'           => 'required|string|max:40',
            'google_api_key'      => 'required|string',
            'cur_text'            => 'required|string|max:40',
            'cur_sym'             => 'required|string|max:40',
            'base_color'          => 'nullable|regex:/^[a-f0-9]{6}$/i',
            'secondary_color'     => 'nullable|regex:/^[a-f0-9]{6}$/i',
            'timezone'            => 'required|integer',
            'currency_format'     => 'required|in:1,2,3',
            'paginate_number'     => 'required|integer',
            'minimum_subscribe'   => 'required|integer',
            'minimum_views'       => 'required|integer',
            'title'               => 'required|string',
            'description'         => 'required|string',
            'monetization_amount' => 'required|numeric|gte:0',


        ]);

        $timezones = timezone_identifiers_list();
        $timezone  = @$timezones[$request->timezone] ?? 'UTC';

        $general                    = gs();
        $general->site_name         = $request->site_name;
        $general->google_api_key    = $request->google_api_key;
        $general->cur_text          = $request->cur_text;
        $general->cur_sym           = $request->cur_sym;
        $general->paginate_number   = $request->paginate_number;
        $general->base_color        = str_replace('#', '', $request->base_color);
        $general->secondary_color   = str_replace('#', '', $request->secondary_color);
        $general->currency_format   = $request->currency_format;
        $general->minimum_subscribe = $request->minimum_subscribe;
        $general->minimum_views     = $request->minimum_views;
        $general->vc_warning        = [
            'title'       => $request->title,
            'description' => $request->description,
        ];

        $general->monetization_status = $request->monetization_status ? Status::ENABLE : Status::DISABLE;
        $general->monetization_amount = $request->monetization_amount;


        $general->save();

        $timezoneFile = config_path('timezone.php');
        $content      = '<?php $timezone = "' . $timezone . '" ?>';
        file_put_contents($timezoneFile, $content);
        RequiredConfig::configured('general_setting');
        $notify[] = ['success', 'General setting updated successfully'];
        return back()->withNotify($notify);
    }

    public function systemConfiguration()
    {
        $pageTitle = 'System Configuration';
        return view('admin.setting.configuration', compact('pageTitle'));
    }

    public function systemConfigurationSubmit(Request $request)
    {
        $general                  = gs();
        $general->kv              = $request->kv ? Status::ENABLE : Status::DISABLE;
        $general->ev              = $request->ev ? Status::ENABLE : Status::DISABLE;
        $general->en              = $request->en ? Status::ENABLE : Status::DISABLE;
        $general->sv              = $request->sv ? Status::ENABLE : Status::DISABLE;
        $general->sn              = $request->sn ? Status::ENABLE : Status::DISABLE;
        $general->pn              = $request->pn ? Status::ENABLE : Status::DISABLE;
        $general->ffmpeg_status   = $request->ffmpeg_status ? Status::ENABLE : Status::DISABLE;
        $general->force_ssl       = $request->force_ssl ? Status::ENABLE : Status::DISABLE;
        $general->secure_password = $request->secure_password ? Status::ENABLE : Status::DISABLE;
        $general->registration    = $request->registration ? Status::ENABLE : Status::DISABLE;
        $general->agree           = $request->agree ? Status::ENABLE : Status::DISABLE;
        $general->multi_language  = $request->multi_language ? Status::ENABLE : Status::DISABLE;
        $general->is_storage      = $request->is_storage ? Status::ENABLE : Status::DISABLE;
        $general->is_playlist_sell          = $request->is_playlist_sell ? Status::ENABLE : Status::DISABLE;
        $general->is_monthly_subscription   = $request->is_monthly_subscription ? Status::ENABLE : Status::DISABLE;
        $general->save();
        $notify[] = ['success', 'System configuration updated successfully'];
        return back()->withNotify($notify);
    }

    public function logoIcon()
    {
        $pageTitle = 'Logo & Favicon';
        return view('admin.setting.logo_icon', compact('pageTitle'));
    }

    public function logoIconUpdate(Request $request)
    {
        $request->validate([
            'logo'    => ['image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'favicon' => ['image', new FileTypeValidate(['png'])],
        ]);
        $path = getFilePath('logoIcon');

        if ($request->hasFile('logo')) {
            try {
                fileUploader($request->logo, $path, filename: 'logo.png');
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload the logo'];
                return back()->withNotify($notify);
            }
        }
        if ($request->hasFile('logo_dark')) {
            try {
                fileUploader($request->logo_dark, $path, filename: 'logo_dark.png');
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload the logo'];
                return back()->withNotify($notify);
            }
        }

        if ($request->hasFile('favicon')) {
            try {
                fileUploader($request->favicon, $path, filename: 'favicon.png');
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload the favicon'];
                return back()->withNotify($notify);
            }
        }
        RequiredConfig::configured('logo_favicon');
        $notify[] = ['success', 'Logo & favicon updated successfully'];
        return back()->withNotify($notify);
    }

    public function customCss()
    {
        $pageTitle   = 'Custom CSS';
        $file        = activeTemplate(true) . 'css/custom.css';
        if (file_exists($file)) {
            $fileContent = file_get_contents($file);
        } else {
            $fileContent = null;
        }
        return view('admin.setting.custom_css', compact('pageTitle', 'fileContent'));
    }

    public function sitemap()
    {
        $pageTitle   = 'Sitemap XML';
        $file        = 'sitemap.xml';
        if (file_exists($file)) {
            $fileContent = file_get_contents($file);
        } else {
            $fileContent = null;
        }
        return view('admin.setting.sitemap', compact('pageTitle', 'fileContent'));
    }

    public function sitemapSubmit(Request $request)
    {
        $file = 'sitemap.xml';
        if (!file_exists($file)) {
            fopen($file, "w");
        }
        file_put_contents($file, $request->sitemap);
        $notify[] = ['success', 'Sitemap updated successfully'];
        return back()->withNotify($notify);
    }

    public function robot()
    {
        $pageTitle   = 'Robots TXT';
        $file        = 'robots.xml';
        if (file_exists($file)) {
            $fileContent = file_get_contents($file);
        } else {
            $fileContent = null;
        }
        return view('admin.setting.robots', compact('pageTitle', 'fileContent'));
    }

    public function robotSubmit(Request $request)
    {
        $file = 'robots.xml';
        if (!file_exists($file)) {
            fopen($file, "w");
        }
        file_put_contents($file, $request->robots);
        $notify[] = ['success', 'Robots txt updated successfully'];
        return back()->withNotify($notify);
    }

    public function customCssSubmit(Request $request)
    {
        $file = activeTemplate(true) . 'css/custom.css';
        if (!file_exists($file)) {
            fopen($file, "w");
        }
        file_put_contents($file, $request->css);
        $notify[] = ['success', 'CSS updated successfully'];
        return back()->withNotify($notify);
    }

    public function maintenanceMode()
    {
        $pageTitle   = 'Maintenance Mode';
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->firstOrFail();
        return view('admin.setting.maintenance', compact('pageTitle', 'maintenance'));
    }

    public function maintenanceModeSubmit(Request $request)
    {
        $request->validate([
            'description' => 'required',
            'image'       => ['nullable', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);
        $general                   = gs();
        $general->maintenance_mode = $request->status ? Status::ENABLE : Status::DISABLE;
        $general->save();

        $maintenance = Frontend::where('data_keys', 'maintenance.data')->firstOrFail();
        $image       = @$maintenance->data_values->image;
        if ($request->hasFile('image')) {
            try {
                $old   = $image;
                $image = fileUploader($request->image, getFilePath('maintenance'), getFileSize('maintenance'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $maintenance->data_values = [
            'description' => $request->description,
            'image'       => $image,
        ];
        $maintenance->save();

        $notify[] = ['success', 'Maintenance mode updated successfully'];
        return back()->withNotify($notify);
    }

    public function cookie()
    {
        $pageTitle = 'GDPR Cookie';
        $cookie    = Frontend::where('data_keys', 'cookie.data')->firstOrFail();
        return view('admin.setting.cookie', compact('pageTitle', 'cookie'));
    }

    public function cookieSubmit(Request $request)
    {
        $request->validate([
            'short_desc'  => 'required|string|max:255',
            'description' => 'required',
        ]);
        $cookie              = Frontend::where('data_keys', 'cookie.data')->firstOrFail();
        $cookie->data_values = [
            'short_desc'  => $request->short_desc,
            'description' => $request->description,
            'status'      => $request->status ? Status::ENABLE : Status::DISABLE,
        ];
        $cookie->save();
        $notify[] = ['success', 'Cookie policy updated successfully'];
        return back()->withNotify($notify);
    }

    public function socialiteCredentials()
    {
        $pageTitle = 'Social Login Credentials';
        return view('admin.setting.social_credential', compact('pageTitle'));
    }

    public function updateSocialiteCredentialStatus($key)
    {
        $general     = gs();
        $credentials = $general->socialite_credentials;
        try {
            $credentials->$key->status = $credentials->$key->status == Status::ENABLE ? Status::DISABLE : Status::ENABLE;
        } catch (\Throwable $th) {
            abort(404);
        }

        $general->socialite_credentials = $credentials;
        $general->save();

        $notify[] = ['success', 'Status changed successfully'];
        return back()->withNotify($notify);
    }

    public function updateSocialiteCredential(Request $request, $key)
    {
        $general     = gs();
        $credentials = $general->socialite_credentials;
        try {
            @$credentials->$key->client_id     = $request->client_id;
            @$credentials->$key->client_secret = $request->client_secret;
        } catch (\Throwable $th) {
            abort(404);
        }
        $general->socialite_credentials = $credentials;
        $general->save();

        $notify[] = ['success', ucfirst($key) . ' credential updated successfully'];
        return back()->withNotify($notify);
    }

    public function adSetting()
    {
        $pageTitle = 'Ads Settings';
        return view('admin.setting.ad', compact('pageTitle'));
    }

    public function adSettingUpdate(Request $request)
    {



        $request->validate([
            'per_minute'           => 'required|numeric|gte:0',
            'ad_views'             => 'required|numeric|gte:0',

            'per_impression_spent' => 'nullable|numeric|gte:0',
            'per_click_spent'      => 'nullable|numeric|gte:0',

            'per_click_earn'       => 'required|numeric|gte:0',
            'per_impression_earn'  => 'required|numeric|gte:0',
            'ads_module' => 'required|in:0,1',
      
            'ad_reach' => 'nullable|numeric|gte:0',
            'ad_engagement' => 'nullable|numeric|gte:0',
  
        ]);

        $general            = gs();
        $general->ad_config = [
            'per_minute' => $request->per_minute,
            'ad_views'   => $request->ad_views,
        ];

        $general->per_impression_spent = $request->per_impression_spent;
        $general->per_click_spent      = $request->per_click_spent;
        $general->per_click_earn       = $request->per_click_earn;
        $general->per_impression_earn  = $request->per_impression_earn;
        $general->ads_module = $request->ads_module;
        $general->ads_auto_approve = $request->ads_auto_approve;
        $general->ad_reach = $request->ad_reach;
        $general->ad_engagement = $request->ad_engagement;



        $general->save();
        $notify[] = ['success', 'General setting updated successfully'];
        return back()->withNotify($notify);
    }

    public function holiday()
    {
        $holidays  = Holiday::paginate(getPaginate());
        $pageTitle = 'Holidays';
        return view('admin.setting.holiday', compact('holidays', 'pageTitle'));
    }

    public function offDaySubmit(Request $request)
    {

        $totalOffDay = count($request->off_days ?? []);

        if ($totalOffDay == 7) {
            $notify[] = ['error', 'You couldn\'t set all days as holiday'];
            return back()->withNotify($notify);
        }

        $general           = gs();
        $general->off_days = $request->off_days;
        $general->save();

        $notify[] = ['success', 'Weekly Holiday Setting Updated'];
        return back()->withNotify($notify);
    }

    public function holidaySubmit(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'date'  => 'required|date',
        ]);

        $holiday          = new Holiday();
        $holiday->day_off = $request->date;
        $holiday->title   = $request->title;
        $holiday->save();

        $notify[] = ['success', 'Holiday added successfully'];
        return back()->withNotify($notify);
    }

    public function charge()
    {
        $pageTitle = 'Charge Setting';
        return view('admin.setting.charge', compact('pageTitle'));
    }

    public function chargeSetting(Request $request)
    {
        $request->validate([
            'video_sell_charge'    => 'required|numeric|gte:0|lt:100',
            'playlist_sell_charge' => 'required|numeric|gte:0|lt:100',
            'plan_sell_charge'     => 'required|numeric|gte:0|lt:100',
        ]);

        $general            = gs();
        $general->video_sell_charge = $request->video_sell_charge;
        $general->playlist_sell_charge      = $request->playlist_sell_charge;
        $general->plan_sell_charge       = $request->plan_sell_charge;
        $general->save();

        RequiredConfig::configured('charge_setting');

        $notify[] = ['success', 'Charge setting updated successfully'];
        return back()->withNotify($notify);
    }

    public function remove($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        $notify[] = ['success', 'Holiday deleted successfully'];
        return back()->withNotify($notify);
    }

    public function checkFFmpegInstallation()
    {
        if (!gs('ffmpeg_status')) {
            return response()->json([
                'status' => 'ffmpeg_disable',
            ]);
        }

        $ffmpegVersion = shell_exec('ffmpeg -version 2>&1');
        if (strpos($ffmpegVersion, 'ffmpeg version') !== false) {
            return response()->json([
                'status' => 'success',
            ]);
        }
        return response()->json([
            'status' => 'error',
        ]);
    }
}
