<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Sos;

class RiderDashboardResource extends JsonResource
{
    public function toArray($request)
    {
        $ride_request = $this->riderRideRequestDetail()->where('is_schedule', 0)->where('driver_id', null)->whereNotIn('status', ['canceled','completed'])->where('is_rider_rated', false)->latest()->first();
        $schedule_ride_request = $this->riderRideRequestDetail()->where('is_schedule', 1)->whereNotIn('status', ['canceled'])->where('is_rider_rated', false)->orderBy('schedule_datetime','asc')->get();
        $on_ride_request = $this->riderRideRequestDetail()->where('driver_id', '!=', null)->where('is_schedule', 0)->whereNotIn('status', ['canceled'])->where('is_rider_rated',false)
                        // ->whereHas('payment',function ($q) {
                        //     $q->where('payment_status', 'pending');
                        // })
                        ->latest()
                        ->first();
        $on_ride_request_data = $this->riderRideRequestDetail()->where('driver_id', '!=', null)->where('is_schedule', 1)->whereNotIn('status', ['canceled'])->where('is_rider_rated',false)
                        // ->whereHas('payment',function ($q) {
                        //     $q->where('payment_status', 'pending');
                        // })
                        ->latest()
                        ->first();

        $pending_payment_ride_request = $this->riderRideRequestDetail()->where('status', 'completed')->where('is_rider_rated',true)
                        ->whereHas('payment',function ($q) {
                            $q->where('payment_status', 'pending');
                        })
                        ->latest()
                        ->first();
         
        // $driver = isset($on_ride_request) && optional($on_ride_request->driver) ? $on_ride_request->driver : null;
        $driver = null;

        if ($on_ride_request && $on_ride_request->driver) {
            $driver = $on_ride_request->driver;
        } elseif ($on_ride_request_data && $on_ride_request_data->driver) {
            $driver = $on_ride_request_data->driver;
        }

        $payment = isset($pending_payment_ride_request) && optional($pending_payment_ride_request->payment) ? $pending_payment_ride_request->payment : null;
        
        $is_rider_rated = isset($on_ride_request) ? $on_ride_request->rideRequestRating()->where('driver_id', $on_ride_request->driver_id)->first() : null;

        $multiDropLocation = !empty($this->multi_drop_location) ? json_decode($this->multi_drop_location) : json_decode($this->drop_location);
        return [
            'id'                => $this->id,
            'display_name'      => $this->display_name,
            'email'             => $this->email,
            'username'          => $this->username,
            'user_type'         => $this->user_type,
            'profile_image'     => getSingleMedia($this, 'profile_image',null),
            'status'            => $this->status,
            'multi_drop_location' => $multiDropLocation,
            'ride_has_bids' => ($ride_request && $ride_request->ride_has_bid == 1) ? 1 : 0,
            // 'sos'               => Sos::mySOs()->get(),
            'ride_request'      => isset($ride_request) ? new RideRequestResource($ride_request) : null,
            'schedule_ride_request' => RideRequestResource::collection($schedule_ride_request),
            'on_ride_request'   => isset($on_ride_request) && $is_rider_rated == null  ? new RideRequestResource($on_ride_request) : null,
            'driver'            => isset($driver) ? new DriverResource($driver) : null,
            'payment'           => isset($payment) ? new PaymentResource($payment) : null,
            'service_marker'     => $on_ride_request ? getServiceSingleMedia($on_ride_request->service , 'service_marker',null) : null,
        ];
    }
}