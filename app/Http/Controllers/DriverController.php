<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\DataTables\DriverDataTable;
use App\Models\Role;
use App\Http\Requests\DriverRequest;
use App\Models\DriverDocument;
use App\Models\Payment;
use App\DataTables\PaymentDataTable;
use App\Models\WalletHistory;
use App\DataTables\WalletHistoryDataTable;
use App\DataTables\DriverEarningDataTable;
use App\DataTables\RideRequestDataTable;
use Illuminate\Support\Facades\DB;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(DriverDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.driver')] );
        $auth_user = authSession();
        if(!empty(request('status'))) {
            $pageTitle = __('message.pending_list_form_title',['form' => __('message.driver')] );
        }
        $last_actived_at = request('last_actived_at') ?? null;
        $assets = ['datatable'];
        $button = $auth_user->can('driver add') ? '<a href="'.route('driver.create').'" class="float-right btn btn-sm border-radius-10 btn-primary me-2"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.driver')]).'</a>' : '';
        return $dataTable->with('status', request('status'))->render('global.driver-datatable', compact('assets','pageTitle','button','auth_user','last_actived_at'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.driver')]);
        $assets = ['phone'];
        // $selected_service = [];
        return view('driver.form', compact('pageTitle','assets'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DriverRequest $request)
    {
        $request['password'] = bcrypt($request->password);

        $request['username'] = $request->username ?? stristr($request->email, "@", true) . rand(100,1000);
        $request['display_name'] = $request->first_name.' '. $request->last_name;
        $request['user_type'] = 'driver';

        if(auth()->user()->hasRole('fleet')) {
            $request['fleet_id'] = auth()->user()->id;
        }
        $user = User::create($request->all());

        uploadMediaFile($user,$request->profile_image, 'profile_image');
        $user->assignRole('driver');
        // Save Driver detail...
        $user->userDetail()->create($request->userDetail);
        $user->userBankAccount()->create($request->userBankAccount);
        
        $user->userWallet()->create(['total_amount' => 0 ]);
/*
        if($user->driverService()->count() > 0)
        {
            $user->driverService()->delete();
        }

        if($request->service_id != null) {
            foreach($request->service_id as $service) {
                $driver_services = [
                    'service_id'    => $service->id,
                    'driver_id'     => $user->id,
                ];
                $user->driverService()->insert($driver_services);
            }
        }
*/
        return redirect()->route('driver.index')->withSuccess(__('message.save_form', ['form' => __('driver')]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(WalletHistoryDataTable $dataTable,RideRequestDataTable $rideRequestDataTable, $id)
    {
        $pageTitle = __('message.view_form_title',[ 'form' => __('message.driver')]);
        $data = User::where('user_type', 'driver')->with('roles','userDetail', 'userBankAccount')->findOrFail($id);
        $data->rating = count($data->driverRating) > 0 ? (float) number_format(max($data->driverRating->avg('rating'),0), 2) : 0;

        $data->cash_earning = Payment::whereHas('riderequest',function ($q) use($id) {
                $q->where('driver_id',$id);
            })->where('payment_status', 'paid')->where('payment_type', 'cash')->value(DB::raw('SUM(admin_commission + driver_commission)')) ?? 0;
        
        $data->admin_commission = Payment::whereHas('riderequest',function ($q) use($id) {
            $q->where('driver_id', $id);
        })->where('payment_status', 'paid')->sum('admin_commission') ?? 0;

        $data->wallet_earning = Payment::whereHas('riderequest',function ($q) use($id) {
                $q->where('driver_id',$id);
            })->where('payment_status', 'paid')->where('payment_type', 'wallet')->sum('driver_commission') ?? 0;
        
        $data->total_earning = $data->cash_earning + $data->wallet_earning;

        $data->driver_earning = Payment::whereHas('riderequest',function ($q) use($id) {
            $q->where('driver_id', $id);
        })->where('payment_status', 'paid')->sum('driver_commission') ?? 0;

        $profileImage = getSingleMedia($data, 'profile_image');
        $type = request('type') ?? 'detail';

        // $validStatuses = [
        //     'new_ride_requested',
        //     'accepted',
        //     'arriving',
        //     'arrived',
        //     'in_progress',
        //     'completed'
        // ];
        // foreach ($data->driverRideRequestDetail as $key => $value) {
        //     if (in_array($value->status, $validStatuses)) {
        //         $data['in_service'] = 1;
        //     }
        // }
        
        switch ($type) {
                case 'detail':
                    return view('driver.show', compact('pageTitle', 'data', 'profileImage','type'));
                break;

                case 'bank_detail':
                    return view('driver.show', compact('pageTitle', 'data', 'profileImage','type'));
                break;

                case 'wallet_history':
                    return $dataTable->with('user_id',$id)->render('driver.show', compact('pageTitle', 'data', 'profileImage', 'type' ));
                break;

                case 'ride_request':
                    return $rideRequestDataTable->with('driver_id',$id)->render('driver.show', compact('pageTitle', 'data', 'profileImage', 'type' ));
                break;

            default:
                # code...
                $type = 'detail';
                return view('driver.show', compact('pageTitle', 'data', 'profileImage','type'));
                break;
        }
            
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.driver')]);
        $data = User::where('user_type', 'driver')->with('userDetail','userBankAccount')->findOrFail($id);

        $profileImage = getSingleMedia($data, 'profile_image');
        $assets = ['phone'];
/* 
        $selected_service = $data->driverService->mapWithKeys(function ($item) {
            return [ $item->service_id => optional($item->service)->name ];
        });
*/
        return view('driver.form', compact('data', 'pageTitle', 'id', 'profileImage', 'assets'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(DriverRequest $request, $id)
    {
        $user = User::with('userDetail')->findOrFail($id);
        
        $request['password'] = $request->password != '' ? bcrypt($request->password) : $user->password;

        $request['display_name'] = $request->first_name.' '. $request->last_name;

        if(auth()->user()->hasRole('fleet')) {
            $request['fleet_id'] = auth()->user()->id;
        }

        if ($request->status !== 'active') {
            $request['is_online'] = 0;
            $request['is_available'] = 0;
        }

        // User user data...
        $user->fill($request->all())->update();

        // Save user image...
        if (isset($request->profile_image) && $request->profile_image != null) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        if($user->userDetail != null) {
            $user->userDetail->fill($request->userDetail)->update();
        } else {
            $user->userDetail()->create($request->userDetail);
        }

        if($user->userBankAccount != null) {
            $user->userBankAccount->fill($request->userBankAccount)->update();
        } else {
            $user->userBankAccount()->create($request->userBankAccount);
        }

        /*
        if($user->driverService()->count() > 0)
        {
            $user->driverService()->delete();
        }

        if($request->service_id != null) {
            foreach($request->service_id as $service) {
                $driver_services = [
                    'service_id'    => $service,
                    'driver_id'     => $user->id,
                ];
                $user->driverService()->insert($driver_services);
            }
        }
        */

        if(auth()->check()){
            return redirect()->route('driver.index')->withSuccess(__('message.update_form',['form' => __('message.driver')]));
        }
        return redirect()->back()->withSuccess(__('message.update_form',['form' => __('message.driver') ] ));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.driver')]);

        if($user!='') {
            $user->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.driver')]);
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }
}
