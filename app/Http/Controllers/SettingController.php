<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppSetting;
use App\Models\Setting;
use App\Models\User;
use App\Models\Service;
use App\Models\PaymentGateway;
use App\Http\Requests\UserRequest;
use App\Notifications\CommonNotification;
use Session;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

    }

    public function settings(Request $request)
    {
        $auth_user = auth()->user();
        $assets = ['phone'];
        $pageTitle = __('message.setting');
        $page = $request->page;

        if($page == ''){
            if($auth_user->hasAnyRole(['admin', 'demo_admin'])){
                $page = 'general-setting';
            }else{
                $page = 'profile_form';
            }
        }

        return view('setting.index',compact('page', 'pageTitle' ,'auth_user', 'assets'));
    }

    public function layoutPage(Request $request)
    {
        $page = $request->page;
        if( $page == 'payment-setting' ) {
            $type = isset($request->type) ? $request->type : 'stripe';
        }
        $auth_user = auth()->user();
        $user_id = $auth_user->id;
        $settings = AppSetting::first();
        $user_data = User::find($user_id);
        $envSettting = $envSettting_value = [];

        if(count($envSettting) > 0 ){
            $envSettting_value = Setting::whereIn('key',array_keys($envSettting))->get();
        }
        if($settings == null){
            $settings = new AppSetting;
        }elseif($user_data == null){
            $user_data = new User;
        }
        switch ($page) {
            case 'password_form':
                $data  = view('setting.'.$page, compact('settings','user_data','page'))->render();
                break;
            case 'profile_form':
                $assets = ['phone'];
                $data  = view('setting.'.$page, compact('settings','user_data','page', 'assets'))->render();
                break;
            case 'mail-setting':
                $data  = view('setting.'.$page, compact('settings','page'))->render();
                break;
            case 'mobile-config':
                $setting = Config::get('mobile-config');
                $getSetting = [];
                foreach($setting as $k=>$s){
                    foreach ($s as $sk => $ss){
                        $getSetting[]=$k.'_'.$sk;
                    }
                }

                $setting_value = Setting::whereIn('key',$getSetting)->get();

                $data  = view('setting.'.$page, compact('setting', 'setting_value', 'page'))->render();
                break;
            case 'wallet-setting':
                $page = 'wallet-setting';
                $wallet_setting = config('constant.wallet');

                foreach ($wallet_setting as $key => $val) {
                    $wallet_setting[$key] = Setting::where('key',$key)->pluck('value')->first();
                }

                $data  = view('setting.'.$page, compact('wallet_setting', 'page'))->render();
                break;
            case 'ride-setting':
                $ride_setting = config('constant.ride');
                $page = 'ride-setting';
                foreach ($ride_setting as $key => $val) {
                    $ride_setting[$key] = Setting::where('key',$key)->pluck('value')->first();
                }

                $data  = view('setting.'.$page, compact('ride_setting', 'page'))->render();
                break;
            case 'notification-setting':
                $notification_setting = config('constant.notification');
                $page = 'notification-setting';
                $notification_setting_data = AppSetting::first();

                $data  = view('setting.'.$page, compact('notification_setting', 'page', 'notification_setting_data'))->render();
                break;
            case 'payment-setting':
                $payment_setting_data = PaymentGateway::where('type',$type)->first();
                // dd($payment_setting_data);
                $data  = view('setting.'.$page, compact('settings', 'page', 'type', 'payment_setting_data'))->render();
                break;
            case 'databasebackup-setting':
                $databasebackup_setting = AppSetting::first();
                $data  = view('setting.'.$page, compact('databasebackup_setting','page'))->render();
                break;
            case 'mail-template-setting':
                $mail_template_setting = config('constant.mail_template_setting');
                $page = 'mail-template-setting';

                foreach ($mail_template_setting as $key => $val) {
                    $mail_template_setting[$key] = Setting::where('key',$key)->pluck('value')->first();
                }
                $data  = view('setting.'.$page, compact('mail_template_setting', 'page'))->render();
                break;
            default:
                $data  = view('setting.'.$page, compact('settings','page','envSettting'))->render();
                break;
        }
        return response()->json($data);
    }

    public function settingUpdate(Request $request)
    {
        $data = $request->all();

        if($request->is('api/*')) {
            foreach($data as $req) {
                $input = [
                    'type'  => $req['type'],
                    'key'   => $req['key'],
                    'value' => $req['value'],
                ];
                Setting::updateOrCreate(['key' => $req['key'], 'type' => $req['type'] ],$input);
            }
        }else{
            if (isset($data['key']) && is_array($data['key'])) {
                foreach($data['key'] as $key => $val){
                    $value = ( $data['value'][$key] != null) ? $data['value'][$key] : null;
                    $input = [
                        'type' => $data['type'][$key],
                        'key' => $data['key'][$key],
                        'value' => ( $data['value'][$key] != null) ? $data['value'][$key] : null,
                    ];
                    Setting::updateOrCreate(['key'=>$input['key']],$input);
                    envChanges($data['key'][$key],$value);
                }         
            }
        }
        $message = __('message.save_form', ['form' => __('message.setting')]);
        if (request()->is('api/*')) {
            return json_message_response($message);
        }
        if (isset($data['mobile-config'])) {
            return redirect()->route('setting.index', ['page' => 'mobile-config'])->withSuccess( __('message.updated'));
        } else {

            return redirect()->back()->withSuccess($message);
        }

    }
    public function settingsUpdates(Request $request)
    {
        $page = $request->page;
        $language_option= $request->language_option;

        if(!is_array($language_option)){
            $language_option=(array)$language_option;
        }

        array_push($language_option, $request->env['DEFAULT_LANGUAGE']);

        $request->merge(['language_option' => $language_option]);

        $request->merge(['site_name' => str_replace("'", "", str_replace('"', '', $request->site_name))]);

        $res = AppSetting::updateOrCreate([ 'id' => $request->id ], $request->all());

        $type = 'APP_NAME';
        $env = $request->env;

        $env['APP_NAME'] = $res->site_name;
        foreach ($env as $key => $value){
            envChanges($key, $value);
        }

        $message = '';

        App::setLocale($env['DEFAULT_LANGUAGE']);
        session()->put('locale', $env['DEFAULT_LANGUAGE']);

        if($request->timezone != '') {
            $user = auth()->user();
            $user->timezone = $request->timezone;
            $user->save();
        }
        uploadMediaFile($res,$request->site_logo, 'site_logo');
        uploadMediaFile($res,$request->site_dark_logo, 'site_dark_logo');
        uploadMediaFile($res,$request->site_favicon, 'site_favicon');

        appSettingData('set');

        createLangFile($env['DEFAULT_LANGUAGE']);

        return redirect()->route('setting.index', ['page' => $page])->withSuccess( __('message.updated'));
    }
    public function AppSetting(Request $request)
    {
        $data = $request->all();

        $appSetting = AppSetting::updateOrCreate(['id' => $request->id], $data);
        $message = __('message.save_form', ['form' => __('message.setting')]);
        if (isset($data['notification_settings'])) {
            return redirect()->route('setting.index', ['page' => 'notification-setting'])->withSuccess(__('message.updated'));
        } else {
            return redirect()->route('setting.index', ['page' => 'order-setting'])->withSuccess(__('message.updated'));
        }
    }
    // public function settingUploadImage(Request $request)
    // {
    //     $data = $request->all();

    //     $result = Setting::updateOrCreate(['key' => request('key'), 'type' => request('type')],$data);
    //     $collection_name = request('key');

    //     if(isset($request->$collection_name) && $request->$collection_name != null ) {
    //         $result->clearMediaCollection($collection_name);
    //         $result->addMediaFromRequest($collection_name)->toMediaCollection($collection_name);
    //     }

    //     $result->update([
    //         'value' => getSingleMedia($result, $collection_name ,null)
    //     ]);

    //     if (request()->is('api/*')) {
    //         return json_message_response(__('message.save_form', ['form' => __('message.setting')]));
    //     } else {
    //         return redirect()->back();
    //     }
    // }

    public function envChanges(Request $request)
    {
        $page = $request->page;
        if(!auth()->user()->hasRole('admin')) {
            abort(403, __('message.action_is_unauthorized'));
        }
        $env = $request->ENV;
        $envtype = $request->type;

        foreach ($env as $key => $value){
            envChanges($key, str_replace('#','',$value));
        }
        Artisan::call('cache:clear');
        return redirect()->route('setting.index', ['page' => $page])->withSuccess(ucfirst($envtype).' '.__('message.updated'));
    }

    public function updateProfile(UserRequest $request)
    {
        $user = Auth::user();
        $page = $request->page;

        $user->fill($request->all())->update();
        uploadMediaFile($user,$request->profile_image, 'profile_image');

        return redirect()->route('setting.index', ['page' => 'profile_form'])->withSuccess( __('message.profile').' '.__('message.updated'));
    }

    public function changePassword(Request $request)
    {
        $user = User::where('id',Auth::user()->id)->first();

        if($user == "") {
            $message = __('message.not_found_entry',[ 'name' => __('message.user') ]);
            return json_message_response($message,400);
        }

        $validator= Validator::make($request->all(), [
            'old' => 'required|min:8|max:255',
            'password' => 'required|min:8|confirmed|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('setting.index', ['page' => 'password_form'])->with('errors',$validator->errors());
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old, $hashedPassword);

        $same_exits = Hash::check($request->password, $hashedPassword);
        if ($match)
        {
            if($same_exits){
                $message = __('message.old_new_pass_same');
                return redirect()->route('setting.index', ['page' => 'password_form'])->with('error',$message);
            }

			$user->fill([
                'password' => Hash::make($request->password)
            ])->save();
            Auth::logout();
            $message = __('message.password_change');
            return redirect()->route('setting.index', ['page' => 'password_form'])->withSuccess($message);
        }
        else
        {
            $message = __('message.valid_password');
            return redirect()->route('setting.index', ['page' => 'password_form'])->with('error',$message);
        }
    }

    public function termAndCondition(Request $request)
    {
        $setting_data = Setting::where('type','terms_condition')->where('key','terms_condition')->first();
        $pageTitle = __('message.terms_condition');
        $assets = ['textarea'];
        return view('setting.term_condition_form',compact('setting_data', 'pageTitle', 'assets'));
    }

    public function saveTermAndCondition(Request $request)
    {
        if(!auth()->user()->hasRole('admin')) {
            abort(403, __('message.action_is_unauthorized'));
        }
        $setting_data = [
                        'type'  => 'terms_condition',
                        'key'   =>  'terms_condition',
                        'value' =>  $request->value
                    ];
        $result = Setting::updateOrCreate(['id' => $request->id],$setting_data);
        if($result->wasRecentlyCreated)
        {
            $message = __('message.save_form',['form' => __('message.terms_condition')]);
        }else{
            $message = __('message.update_form',['form' => __('message.terms_condition')]);
        }

        return redirect()->route('term-condition')->withsuccess($message);
    }

    public function privacyPolicy(Request $request)
    {
        $setting_data = Setting::where('type','privacy_policy')->where('key','privacy_policy')->first();
        $pageTitle = __('message.privacy_policy');
        $assets = ['textarea'];

        return view('setting.privacy_policy_form',compact('setting_data', 'pageTitle', 'assets'));
    }

    public function savePrivacyPolicy(Request $request)
    {
        if(!auth()->user()->hasRole('admin')) {
            abort(403, __('message.action_is_unauthorized'));
        }
        $setting_data = [
                        'type'   => 'privacy_policy',
                        'key'   =>  'privacy_policy',
                        'value' =>  $request->value
                    ];
        $result = Setting::updateOrCreate(['id' => $request->id],$setting_data);
        if($result->wasRecentlyCreated)
        {
            $message = __('message.save_form',['form' => __('message.privacy_policy')]);
        }else{
            $message = __('message.update_form',['form' => __('message.privacy_policy')]);
        }

        return redirect()->route('privacy-policy')->withsuccess($message);
    }

    public function paymentSettingsUpdate(Request $request)
    {
        if(!auth()->user()->hasRole('admin')) {
            abort(403, __('message.action_is_unauthorized'));
        }
        $data = $request->all();
        $result = PaymentGateway::updateOrCreate([ 'type' => request('type') ],$data);
        uploadMediaFile($result,$request->gateway_image, 'gateway_image');
        return redirect()->route('setting.index', ['page' => 'payment-setting'])->withSuccess( __('message.updated'));
    }

    public function walletSettingsUpdate(Request $request)
    {
        $data = $request->all();

        foreach(config('constant.wallet') as $key => $val){
            $input = [
                'type'  => 'wallet',
                'key'   => $key,
                'value' => $data[$key] ?? null,
            ];
            Setting::updateOrCreate(['key' => $key],$input);
        }

        return redirect()->route('setting.index', ['page' => 'wallet-setting'])->withSuccess( __('message.updated'));
    }

    public function rideSettingsUpdate(Request $request)
    {
        $data = $request->all();

        foreach(config('constant.ride') as $key => $val){
            $input = [
                'type'  => 'ride',
                'key'   => $key,
                'value' => $data[$key] ?? null,
            ];
            Setting::updateOrCreate(['key' => $key],$input);
        }

        return redirect()->route('setting.index', ['page' => 'ride-setting'])->withSuccess( __('message.updated'));
    }

    public function notificationSettingsUpdate(Request $request)
    {
        $app_setting = AppSetting::getData();

        AppSetting::updateOrCreate(['id' => $app_setting->id ], ['notification_settings' => $request->notification_settings]);

        return redirect()->route('setting.index', ['page' => 'notification-setting'])->withSuccess( __('message.updated'));
    }
    
    public function updateAppSetting(Request $request)
    {
        $data = $request->all();
        $page = request('page') ?? null;

        AppSetting::updateOrCreate(['id' => $request->id], $data);
        $message = __('message.updated');

        if (isset($page) && in_array($page,['databasebackup-setting'])) {
            return redirect()->route('setting.index', ['page' => $page])->withSuccess( $message );
        }

        return redirect()->back()->withsuccess( $message );
    }

    public function mailTemplateSettingsUpdate(Request $request)
    {
        $data = $request->all();

        foreach(config('constant.mail_template_setting') as $key => $val){
            $input = [
                'type'  => 'mail_template',
                'key'   => $key,
                'value' => $data[$key] ?? 0,
            ];
            Setting::updateOrCreate(['key' => $key],$input);
        }

        return redirect()->route('setting.index', ['page' => 'mail-template-setting'])->withSuccess( __('message.updated'));
    }
}
