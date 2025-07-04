<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Document;
use App\Models\AppSetting;
use App\Models\WithdrawRequest;
use App\Models\User;
use App\Models\Complaint;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\RideRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpMqtt\Client\Facades\MQTT;
use Illuminate\Support\Facades\App;
use App\Http\Resources\DriverResource;
use App\Http\Resources\NearByDriverResource;
use App\Models\DefaultKeyword;
use App\Models\LanguageDefaultList;
use App\Models\LanguageList;
use App\Models\Pages;
use App\Models\Screen;
use App\Models\Wallet;
use App\Models\WalletHistory;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }
    
    /*
     * Dashboard Pages Routs
     */
    public function index(Request $request)
    {
        $auth_user = auth()->user();
        
        $data['dashboard'] = [
            'pending_driver' => User::where('user_type','driver')->where('is_verified_driver',0)->count(),
            'total_driver' => User::getUser('driver')->count(),
            'total_rider' => User::getUser('rider')->count(),
            'total_ride' => RideRequest::count(),
            'today_earning' => Payment::where('payment_status','paid')->whereDate('datetime', Carbon::today())->sum('total_amount'),
            'monthly_earning' => Payment::where('payment_status','paid')->whereMonth('datetime', Carbon::now()->month)->sum('total_amount'),
            'total_earning' => Payment::where('payment_status','paid')->sum('total_amount'),
            'complaint' => Complaint::where('status','pending')->count()
        ];

        $chart_data = [];

        $cash_payment = Payment::selectRaw('sum(total_amount) as total , DATE_FORMAT(datetime , "%m") as month' )
                        ->myPayment()
                        ->where('payment_status', 'paid')
                        ->where('payment_type', 'cash')
                        ->whereYear('datetime',date('Y'))
                        ->groupBy('month')
                        ->get()->toArray();
            
        for($i = 1; $i <= 12; $i++ ) {
            $paymentData = 0;
            foreach($cash_payment as $payment){
                if((int) $payment['month'] == $i){
                    $data['cash_yearly'][] = (int) $payment['total'];
                    $paymentData++;
                }
            }
            if($paymentData == 0) {
                $data['cash_yearly'][] = 0 ;
            }
        }

        $wallet_payment = Payment::selectRaw('sum(total_amount) as total , DATE_FORMAT(datetime , "%m") as month' )
                    ->myPayment()
                    ->where('payment_status', 'paid')
                    ->where('payment_type', 'wallet')
                    ->whereYear('datetime',date('Y'))
                    ->groupBy('month')
                    ->get()->toArray();
        for($i = 1; $i <= 12; $i++ ) {
            $paymentData = 0;
            foreach($wallet_payment as $payment){
                if((int) $payment['month'] == $i){
                    $data['wallet_yearly'][] = (int) $payment['total'];
                    $paymentData++;
                }
            }
            if($paymentData == 0) {
                $data['wallet_yearly'][] = 0 ;
            }
        }

        $recent_riderequest = RideRequest::myRide()->where('created_at','<=', Carbon::now()->format('Y-m-d H:i:s'))->orderBy('id', 'desc')->take(10)->get();
        return view('dashboards.admin-dashboard',compact('data', 'auth_user','recent_riderequest'));
    }

    public function changeLanguage($locale)
    {
        App::setLocale($locale);
        session()->put('locale', $locale);
        return redirect()->back();
    }

    public function chartdata(Request $request)
    {
        $type = $request->type ?? 'daily';
        $series = [];
        if( $type == 'daily' )
        {
            $series = [
                [
                    'name' => __('message.cash') ,
                    'data' => 100,
                ],
                [
                    'name' => __('message.wallet') ,
                    'data' => 1000,
                ],
            ];
        }

        if( $type == 'yearly' ) 
        {
            $cash_payment = Payment::selectRaw('count(Date(datetime)) as total , DATE_FORMAT(datetime , "%m") as month' )
                        ->myPayment()
                        ->where('payment_status', 'paid')
                        ->where('payment_type', 'cash')
                        ->whereYear('datetime',date('Y'))
                        ->groupBy('month')
                        ->get()->toArray();
            
            for($i = 1; $i <= 12; $i++ ){
                $paymentData = 0;

                foreach($cash_payment as $payment){
                    if((int)$payment['month'] == $i){
                        $data['list'][] = (int)$payment['total'];
                        $paymentData++;
                    }
                }

                if($paymentData == 0){
                    $data['list'][] = 0 ;
                }
            }
        }

        return $series;
    }
    /*
     * Auth pages Routs
     */

     function authLogin()
    {
    return view('auth.login');
    }

    function authRegister()
    {
        $assets = ['phone'];
        return view('auth.register',compact('assets'));
    }

    function authRecoverPassword()
    {
        return view('auth.forgot-password');
    }

    function authConfirmEmail()
    {
        return view('auth.verify-email');
    }

    function authLockScreen()
    {
        return view();
    }

    public function changeStatus(Request $request)
    {
        $type = $request->type;
        $message_form = "";
        $message = __('message.update_form',['form' => __('message.status')]);
        switch ($type) {
            case 'role':
                    $role = \App\Models\Role::find($request->id);
                    $role->status = $request->status;
                    $role->save();
                    break;
            case 'service_status' :
                $service = \App\Models\Service::find($request->id);
                $service->status = $request->status;
                $service->save();
                break;
            case 'coupon_status' :
                $coupon = \App\Models\Coupon::find($request->id);
                $coupon->status = $request->status;
                $coupon->save();
                break;
            case 'document_status' :
                $document = Document::find($request->id);
                $document->status = $request->status;
                $document->save();
                break;
            case 'document_required' :
                $message_form = __('message.required');
                $document = Document::find($request->id);
                $document->is_required = $request->status;
                $document->save();
                break;
            case 'document_has_expiry_date' :
                $message_form = __('message.expire_date');
                $document = Document::find($request->id);
                $document->has_expiry_date = $request->status;
                $document->save();
                break;
            case 'driver_is_verified' :
                $message_form = __('message.driverdocument');
                $document = \App\Models\DriverDocument::find($request->id);
                $document->is_verified = $request->status;
                $document->save();
                break;
            case 'pages':
                $user = Pages::find($request->id);
                $status = $request->status == 0 ? '0' : '1';
                $user->status = $status;
                $user->save();
                break;
            default:
                    $message = 'error';
                break;
        }

        if($message_form != null){
            $message =  __('message.added_form',['form' => $message_form ]);
            if($request->status == 0){
                $message = __('message.remove_form',['form' => $message_form ]);
            }
        }
        
        return json_custom_response(['message'=> $message , 'status' => true]);
    }

    public function getAjaxList(Request $request)
    {
        $items = array();
        $value = $request->q;
        $auth_user = authSession();

        $checked_ids = $request->datatable_checked_ids;
        switch ($request->type) {
            case 'permission':
                $items = \App\Models\Permission::select('id','name as text')->whereNull('parent_id');
                if($value != ''){
                    $items->where('name', 'LIKE', $value.'%');
                }
                $items = $items->get();
                break;
            case 'fleet_driver':
                    if( $auth_user->hasRole('admin')) {
                        $user_type = ['driver','fleet'];
                    } else {
                        $user_type = $auth_user->user_type;
                    }
                    $items = User::select('id','display_name as text')
                        ->whereIn('user_type',$user_type)
                        ->where('status','active');
        
                        if($value != ''){
                            $items->where('display_name', 'LIKE', $value.'%');
                        }
        
                        $items = $items->get();
                break;
            case 'rider':
                $items = \App\Models\User::select('id','display_name as text')
                    ->where('user_type','rider')
                    ->where('status','active');
    
                    if($value != ''){
                        $items->where('display_name', 'LIKE', $value.'%');
                    }
    
                    $items = $items->get();
                    break;
            case 'fleet':
                $items = \App\Models\User::select('id','display_name as text')
                    ->where('user_type','fleet')
                    ->where('status','active');
    
                    if($value != ''){
                        $items->where('display_name', 'LIKE', $value.'%');
                    }
    
                    $items = $items->get();
                    break;
            case 'ongoing_driver':
                $items = \App\Models\User::select('id', 'display_name as text')
                    ->where('user_type', 'driver')
                    ->where('status', 'active')
                    ->whereIn('id', function ($query) {
                        $query->select('driver_id')
                            ->from('ride_requests')
                            ->whereNotIn('status', ['canceled', 'completed']);
                    });
            
                if (isset($request->fleet_id)) {
                    $items->where('fleet_id', $request->fleet_id);
                }
            
                if (isset($request->status)) {
                    $items->where('status', $request->status); // if status is passed, override the default 'active'
                }
            
                if ($value != '') {
                    $items->where('display_name', 'LIKE', $value . '%');
                }
            
                $items = $items->get();
                break;
                    
            case 'driver':
                $items = \App\Models\User::select('id','display_name as text')
                ->where('user_type','driver');
                

                if(isset($request->fleet_id)){
                    $items->where('fleet_id', $request->fleet_id);
                }

                if(isset($request->status)){
                    $items->where('status', $request->status);
                } else {
                    $items->where('status','active');
                }
                
                if($value != ''){
                    $items->where('display_name', 'LIKE', $value.'%');
                }

                $items = $items->get();
                break;


            case 'region' :
                $items = \App\Models\Region::select('id','name as text', 'distance_unit')->where('status',1);
                    if($value != ''){
                        $items->where('name', 'LIKE', '%'.$value.'%');
                    }
                            
                    $items = $items->get();

                break;
            case 'service':
                        $items = \App\Models\Service::select('id','name as text')->where('status',1);
        
                        if($value != ''){
                            $items->where('name', 'LIKE', '%'.$value.'%');
                        }
                                
                        $items = $items->get();
                        break;
            case 'coupon':
                        $items = \App\Models\Coupon::select('id','code as text')->where('status',1);
            
                        if($value != ''){
                            $items->where('code', 'LIKE', '%'.$value.'%');
                        }
                    
                        $items = $items->get();
                        break;

            case 'document':
                $items = Document::select('id','name','status' ,'is_required', 'has_expiry_date', DB::raw('(CASE WHEN is_required = 1 THEN CONCAT(name," * ") ELSE CONCAT(name,"") END) AS text'))->where('status',1);
                if($value != ''){
                    $items->where('name', 'LIKE', $value.'%');
                }
                $items = $items->get();
                break;
            case 'riderequest': 
                $id = __('message.ride_request_id');

                $items = \App\Models\RideRequest::select('id', DB::raw("CONCAT('#',id) as text"), 'rider_id','driver_id')->with(['rider','driver']);
                if($value != '') {
                    $items->where('id', 'LIKE', $value.'%');
                }
                $items = $items->get();
                break;
            case 'timezone':
                $items = timeZoneList();
                foreach ($items as $k => $v) {
                    if($value !=''){
                        if (strpos($v, $value) !== false) {

                        } else {
                            unset($items[$k]);
                        }
                    }
                }
                $data = [];
                $i = 0;
                foreach ($items as $key => $row) {
                    $data[$i] = [
                        'id'    => $key,
                        'text'  => $row,
                    ];
                    $i++;
                }
                $items=$data ;
                break;
                case 'transaction_type':
                    if( request('type_val') != null && request('type_val') == 'debit' ){

                        if( request('user_type') == 'rider' ) {
                            $items = [
                                [ 'id' => 'ride_fee', 'text' => __('message.ride_fee') ],
                            ];
                        } else {
                            $items = [
                                [ 'id' => 'correction', 'text' => __('message.correction') ],
                            ];
                        }
                    } else {
                        if( request('user_type') == 'rider' ) {
                            $items = [
                                [ 'id' => 'topup', 'text' => __('message.topup') ],
                            ];
                        } else {
                            $items = [
                                [ 'id' => 'topup', 'text' => __('message.topup') ],
                                [ 'id' => 'ride_fee', 'text' => __('message.ride_fee') ],
                            ];
                        }
                    }
                    # code...
                break;

                case 'service_for_ride':
                    $items = Service::select('id','name as text')->where('status',1);
    
                    if( $request->has('latitude') && isset($request->latitude) && $request->has('longitude') && isset($request->longitude) )
                    {
                        $point = new Point($request->latitude, $request->longitude);
                        
                        $items->whereHas('region',function ($q) use($point) {
                            $q->where('status', 1)->contains('coordinates', $point);
                        });
                    }
                            
                    $items = $items->get();
                break;
                case 'driver_for_ride':
                    $latitude = $request->latitude;
                    $longitude = $request->longitude;
    
                    $service = Service::find($request->service_id);
                    
                    $distance_unit = optional($service->region)->distance_unit ?? 'km';
                    $unit_value = convertUnitvalue($distance_unit);
    
                    $radius = SettingData('DISTANCE', 'DISTANCE_RADIUS') ?? 50;
                    $minumum_amount_get_ride = SettingData('wallet', 'min_amount_to_get_ride') ?? null;
    
                    $items = User::selectRaw("id, display_name as text, status, is_online, is_available, last_location_update_at, user_type, latitude, longitude, ( $unit_value * acos( cos( radians($latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($longitude) ) + sin( radians($latitude) ) * sin( radians( latitude ) ) ) ) AS distance")->where('user_type', 'driver')->where('status','active')
                        ->whereNotNull('latitude')->whereNotNull('longitude')
                        ->having('distance', '<=', $radius)
                        ->where('service_id', $request->service_id )
                        ->where('is_online',1)
                        ->where('is_available',1)
                        ->orderBy('distance','asc');
                        if( $minumum_amount_get_ride != null ) {
                            $items = $items->whereHas('userWallet', function($q) use($minumum_amount_get_ride) {
                                $q->where('total_amount', '>=', $minumum_amount_get_ride);
                            });
                        }
                                
                    $items = $items->get();
                break;

            case 'screen':
                $items = Screen::select('screenId','screenName as text');
                if($value != ''){
                    $items->where('screenName', 'LIKE', '%'.$value.'%');
                }
                $items = $items->get()->map(function ($screen_id) {
                    return ['id' => $screen_id->screenId, 'text' => $screen_id->text];
                });
                $items = $items;
                break;
            case 'language-list-data':
                $languageId = $request->id;
                $items = LanguageDefaultList::where('id', $languageId);
                $items = $items->first();
                break;
            case 'languagelist':
                $data = LanguageList::pluck('language_id')->toArray();
                $items = LanguageDefaultList::whereNotIn('id',$data)->select('id','languageName as text');
                    if($value != ''){
                        $items->where('languageName', 'LIKE', '%'.$value.'%');
                    }
                    $items = $items->get();
                    break;
            case 'defaultkeyword':
                $items = DefaultKeyword::select('id','keyword_name as text');
                    if($value != ''){
                        $items->where('keyword_name', 'LIKE', '%'.$value.'%');
                    }
                    $items = $items->get();
                    break;
            case 'languagetable':
                $items = LanguageList::select('id','language_name as text')->where('status', 1);
                    if($value != ''){
                        $items->where('language_name', 'LIKE', '%'.$value.'%');
                    }
                    $items = $items->get();
                    break;

            case 'language-list-checked':
                if (env('APP_DEMO')) {
                    $message = __('message.demo_permission_denied');
                    if (request()->ajax()) {
                        return response()->json(['status' => false,'message' => $message, 'event' => 'validation' ]);
                    }
                }
                $data = LanguageList::destroy($checked_ids);
                $message = __('message.delete_form', ['form' => __('message.language')]);
                updateLanguageVersion();
                break;

            default :
                break;
        }
        return response()->json(['status' => true, 'results' => $items]);
    }

    public function removeFile(Request $request)
    {
        $type = $request->type;
        $data = null;

        switch ($type) {
            case 'service_image':
                $data = Service::find($request->id);
                $message = __('message.msg_removed',[ 'name' => __('message.service')]);
                break;
            
            case 'gateway_image':
                $data = PaymentGateway::find($request->id);
                $message = __('message.msg_removed',[ 'name' => __('message.paymentgateway')]);
                break;
            
            case 'language_image':
                $data = LanguageList::find($request->id);
                $message = __('message.msg_removed',[ 'name' => __('message.slider')]);
                break;
            case 'service_marker':
                $data = Service::find($request->id);
                $message = __('message.msg_removed',[ 'name' => __('message.service')]);
                break;
            // case 'attachment':
            //     $media = Media::find($request->id);
            //     $media->delete();
            //     $message = __('message.msg_removed',[ 'name' => __('message.attachments')]);
            // break;

            default:
                $data = AppSetting::find($request->id);
                $message = __('message.msg_removed',[ 'name' => __('message.image')]);
            break;
        }

        if($data != null){
            $data->clearMediaCollection($type);
        }

        $response = [
                'status' => true,
                'id' => $request->id,
                'image' => getSingleMedia($data,$type),
                'preview' => $type."_preview",
                'message' => $message
        ];
        return json_custom_response($response);
    }

    public function destroySelected(Request $request)
    {

        $checked_ids = $request->datatable_checked_ids;
        $types = $request->datatable_button_title;
        $data = null;

        switch ($types) {            
            case 'language-list-checked':
                if (env('APP_DEMO')) {
                    $message = __('message.demo_permission_denied');
                    if (request()->ajax()) {
                        return response()->json(['status' => false,'message' => $message, 'event' => 'validation' ]);
                    }
                }
                $data = LanguageList::destroy($checked_ids);
                $message = __('message.delete_form', ['form' => __('message.language')]);
                updateLanguageVersion();
                break;
            default:
                $message  =  false;
                break;
        }
        $response = [
            'success' => true,
            'message' => $message
        ];
        return json_custom_response($response);
    }

    public function map()
    {
        $pageTitle = __('message.driver_location');
        $assets = ['map'];
        return view('map.index', compact('pageTitle','assets'));
    }

    public function driverDetail(Request $request)
    {
        $driver = User::findOrFail($request->id);
        $response = new DriverResource($driver);
        return $response;
    }
    public function search($id)
    {
        $driver = User::find($id);
    
        if ($driver) {
            return response()->json(['data' => $driver]);
        } else {
            return response()->json(['message' => 'Driver not found'], 404);
        }
    }

    public function driverListMap(Request $request)
    {
        $driver_list = User::where('user_type', 'driver')->where('status','active')->whereNotNull('latitude')->whereNotNull('longitude');

        if( $request->has('latitude') && isset($request->latitude) && $request->has('longitude') && isset($request->longitude) )
        {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            
            $unit_value = convertUnitvalue('km');

            $radius = SettingData('DISTANCE', 'DISTANCE_RADIUS') ?? 50;
            $driver_list->selectRaw("id, first_name, last_name, display_name, status, is_online, is_available, last_location_update_at, user_type, latitude, longitude, ( $unit_value * acos( cos( radians($latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($longitude) ) + sin( radians($latitude) ) * sin( radians( latitude ) ) ) ) AS distance ,service_id")
                ->having('distance', '<=', $radius)
                ->where('is_online',1)
                ->where('is_available',1)
                ->orderBy('distance','asc');
        }
        $driver_list = $driver_list->get();

        $response = [
            'data' => NearByDriverResource::collection($driver_list),
            'message' => 'driver list',
        ];
        return json_custom_response($response);
    }

    public function saveWalletHistory(Request $request,$user_id){
        $data = $request->all();

        $wallet =  Wallet::firstOrCreate(
            [ 'user_id' => $user_id ]
        );

        if( $data['type'] == 'credit' ) {
            $total_amount = $wallet->total_amount + $data['amount'];
        }

        if( $data['type'] == 'debit' ) {
            $total_amount = $wallet->total_amount - $data['amount'];
        }
        $currency_code = SettingData('CURRENCY', 'CURRENCY_CODE') ?? 'USD';
        $wallet->currency = strtolower($currency_code);

        $wallet->total_amount = $total_amount;

        try
        {
            DB::beginTransaction();
            $wallet->save();
            $data['user_id'] = $wallet->user_id;
            $data['balance'] = $total_amount;
            $data['datetime'] = date('Y-m-d H:i:s');
            $result = WalletHistory::create($data);           
            DB::commit();
        } catch(\Exception $e) {
            DB::rollBack();
            return $e;
        }
        return redirect()->back()->withSuccess(__('message.transaction_submitted'));
    }

    // publish topic
    public function SendMsgViaMqtt($topic, $message) {
        MQTT::publish($topic, $message);
    }

    // subscribe topic
    public function SubscribetoTopic($topic) {
        $mqtt = MQTT::connection();
        $mqtt->subscribe($topic, function ($topic, $message) {
            echo sprintf("Received message on topic [%s]: %s\n", $topic, $message);
        }, 0);
    }
}
