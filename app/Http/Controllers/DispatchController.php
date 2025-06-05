<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RideRequest;
use App\Http\Requests\DispatchRequest;
use App\Models\Service;
use App\Models\Coupon;

use App\Traits\RideRequestTrait;
use App\Jobs\NotifyViaMqtt;
use App\Http\Resources\RideRequestResource;
use App\Models\Notification;
use Carbon\Carbon;
use App\Models\User;
use App\Models\RideRequestHistory;

class DispatchController extends Controller
{
    use RideRequestTrait;
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.book_now');
        $assets = ['map_place'];
        $auth_user = authSession();
        $button = $auth_user->can('riderequest list') ? '<a href="'.route('riderequest.index').'" class="float-right btn btn-sm border-radius-10 btn-primary me-2">'.__('message.list_form_title',['form' => __('message.riderequest')]).'</a>' : '';
        return view('dispatch.form', compact('pageTitle', 'assets', 'button'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DispatchRequest $request)
    {
        $data = $request->all();
        // dd($data);
        // Check if the rider has registred a riderequest already
        $rider_exists_riderequest = RideRequest::whereNotIn('status', ['canceled', 'completed'])->where('rider_id', request('rider_id'))->where('is_schedule', 0)->exists();
        
        if($rider_exists_riderequest) {
            return json_custom_response([
                'message' => __('message.rider_already_in_riderequest'),
                'status' => false,
                'event' => 'validation',
            ]);
        }

        // Check if the driver in riderequest already
        if( request('driver_id') != null ) {
            $driver_exists_riderequest = RideRequest::whereNotIn('status', ['canceled', 'completed'])->where('driver_id', request('driver_id'))->where('is_schedule', 0)->exists();
            
            if($driver_exists_riderequest) {
                return json_custom_response([
                    'message' => __('message.driver_already_in_riderequest'),
                    'status' => false,
                    'event' => 'validation',
                ]);
            }
        }
        
        $coupon_code = $request->coupon_code;

        if( $coupon_code != null ) {
            $coupon = Coupon::where('code', $coupon_code)->first();
            $status = isset($coupon_code) ? 200 : 400;
        
            if($coupon != null) {
                $status = Coupon::isValidCoupon($coupon);
            }
            if( $status != 200 ) {
                $response = couponVerifyResponse($status);
                return json_custom_response($response,$status);
            } else {
                $data['coupon_code'] = $coupon->id;
                $data['coupon_data'] = $coupon;
            }
        }

        $service = Service::with('region')->where('id',$request->service_id)->first();

        $timezone = $service->region->timezone ?? 'UTC';

        if ($request->has('schedule_datetime') && !empty($request->schedule_datetime)) {
            $data['is_schedule'] = 1;
            $data['schedule_datetime'] = Carbon::parse($request->schedule_datetime)->setTimezone($timezone)->toDateTimeString();
        } else {
            $data['is_schedule'] = 0;
        }

        $rider = User::where('id', request('rider_id'))->first();
        if( $rider != null ) {
            $rider->timezone = $timezone;
            $rider->save();
        }

        $data['datetime'] = Carbon::parse(date('Y-m-d H:i:s'))->setTimezone($timezone)->toDateTimeString();

        if( request()->has('driver_id') && request('driver_id') != null ) {
            $data['riderequest_in_driver_id'] = $data['driver_id'];
            $data['riderequest_in_datetime'] = $data['datetime'];
            $data['driver_id'] = request('driver_id'); 
            // unset($data['driver_id']);
        }

        $data['distance_unit'] = $service->region->distance_unit ?? 'km';
        $data['status'] = 'new_ride_requested';
        $data['payment_type'] = 'cash';

        $drop_locations = [];

        if (request()->has('multi_drop_location')) {
            $drop_locations = request('multi_drop_location'); // Mobile booking
        } elseif (request()->has('drop_location')) {
            $drop_location_data = request('drop_location');

            if (is_string($drop_location_data)) {
                $drop_location_data = json_decode($drop_location_data, true);
            }

            if (is_array($drop_location_data)) {
                $drop_locations = $drop_location_data;
            }
        }
        // Ensure drop locations are properly formatted
        if (!empty($drop_locations)) {
            foreach ($drop_locations as $key => $drop) {
                if (is_string($drop)) { // If any item is a JSON string, decode it
                    $decoded = json_decode($drop, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $drop_locations[$key] = $decoded;
                    }
                }
            }
        }

        // If there are multiple drop locations, use the function
        if (!empty($drop_locations)) {
            $place_details = mighty_get_distance_matrix_multiple_destination(
                request('pick_lat'),
                request('pick_lng'),
                request('drop_lat'),
                request('drop_lng'),
                $drop_locations
            );
        } else {
            // Fallback to a single destination distance calculation
            $place_details = mighty_get_distance_matrix(
                request('start_latitude'),
                request('start_longitude'),
                request('end_latitude'),
                request('end_longitude')
            );
        }

        // Extract distance and duration
        $dropoff_distance_in_meters = $place_details['distance'] ?? 0;
        $dropoff_time_in_seconds = $place_details['duration'] ?? 0;

        
        
        $distance_in_unit = 0;
        
        if ($dropoff_distance_in_meters) {
            // Region->distance_unit == km ( convert meter to km )
            $distance_in_unit = $dropoff_distance_in_meters / 1000;
        }
        $service_data = $service;
        $service_data['distance_unit'] = $distance_in_unit;
        $date_time = now()->format('Y-m-d h:i');
        $surge_price = getSurgePrice($date_time);
        // caclulate ride
        $pick_lat = request('pick_lat');
        $pick_lng = request('pick_lng');
        $drop_lat = request('drop_lat');
        $drop_lng = request('drop_lng');
        $multi_location = $drop_locations ?? [];
        $ridefee = calculateRideFares($distance_in_unit,$pick_lat, $pick_lng, $drop_lat, $drop_lng, $multi_location,$dropoff_time_in_seconds, $service_data, $coupon = null,$surge_price,$date_time);
        if (!empty($drop_locations)) {
            $data['drop_location'] = json_encode($drop_locations); // Convert array to JSON
        }        
        $data['distance'] = $distance_in_unit;
        $data['total_amount'] = $ridefee['total_amount'];
        $data['duration'] = $dropoff_time_in_seconds/60;
        $result = RideRequest::create($data);

        $message = __('message.save_form', ['form' => __('message.riderequest')]);

        if($result->is_schedule) {
            $rider_data =  [
                'rider_id'          => $result->rider_id,
                'rider_name'        => optional($result->rider)->display_name ?? '',
            ];

            $history_data = [
                'ride_request_id'   => $result->id,
                'history_type'      => $result->status,
                'history_message'   => __('message.ride.new_ride_requested'),
                'datetime'          => date('Y-m-d H:i:s'),
                'history_data'      => json_encode($rider_data),
            ];

            RideRequestHistory::create($history_data);
            // $this->acceptDeclinedRideRequest($result);
        } else {
        if( $result->status == 'new_ride_requested' ) {

            $history_data = [
                'ride_request_id'   => $result->id,
                'history_type'      => $result->status,
                'ride_request'      => $result,
            ];
            saveRideHistory($history_data);
            if( $result->driver_id != null ) {
                $this->acceptDeclinedRideRequest($result);
            }
            // $notify_data = new \stdClass();
            // $notify_data->success = true;
            // $notify_data->success_type = $result->status;
            // $notify_data->success_message = __('message.ride.new_ride_requested');
            // $notify_data->result = new RideRequestResource($result);
            // dispatch(new NotifyViaMqtt('new_ride_request_'.$result->rider_id, json_encode($notify_data), $result->rider_id));
        } else {
            $history_data = [
                'history_type'      => $result->status,
                'ride_request_id'   => $result->id,
                'ride_request'      => $result,
            ];

            saveRideHistory($history_data);
        }
        }
        // return response()->json(['status' => true, 'event' => 'reset', 'message' => $message]);
        
        return redirect()->route('riderequest.index')->withSuccess($message);
    }
}
