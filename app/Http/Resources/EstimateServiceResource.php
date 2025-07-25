<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Coupon;
use App\Models\User;

class EstimateServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $distance_unit = optional($this->region)->distance_unit ?? 'km';
        $distance_in_unit = request('distance_in_unit');
        $dropoff_distance_in_meters = request('dropoff_distance_in_meters');
        $dropoff_time_in_seconds = request('dropoff_time_in_seconds');
        $coupon = request('coupon');
        $pick_lat = $this['start_latitude'] ?? request('pick_lat');
        $pick_lng = $this['start_longitude'] ?? request('pick_lng');
        $drop_lat = $this['end_latitude'] ?? request('drop_lat');
        $drop_lng = $this['end_longitude'] ?? request('drop_lng');
        $multiLocation = $this['multi_drop_location'] ?? request('multi_location', []);
        if (is_string($multiLocation)) {
            $multiLocation = json_decode($multiLocation, true);
        }
        
        // Fallback to an empty array if decoding fails
        $multi_location = is_array($multiLocation) ? $multiLocation : [];
        $date_time = now()->format('Y-m-d h:i');
        $surge_price = getSurgePrice($date_time);
        
        $service_data = [
            'id'                => $this->id,
            'service_id'        => $this->id,
            'name'              => $this->name,
            'region_id'         => $this->region_id,
            'distance_unit'     => $distance_unit,
            'dropoff_distance_in_km' => $dropoff_distance_in_meters/1000,
            'duration'          => $dropoff_time_in_seconds/60,
            'capacity'          => $this->capacity,
            'base_fare'         => $this->base_fare,
            'minimum_fare'      => $this->minimum_fare,
            'minimum_distance'  => $this->minimum_distance,
            'per_distance'      => $this->per_distance,
            'per_minute_drive'  => $this->per_minute_drive,
            'per_minute_wait'   => $this->per_minute_wait,
            'waiting_time_limit'=> $this->waiting_time_limit,
            'cancellation_fee'  => $this->cancellation_fee,
            // 'place_details'     => $place_details,
            'payment_method'    => $this->payment_method,            
            'service_image'     => getSingleMedia($this, 'service_image',null),
            'service_marker'     => getServiceSingleMedia($this, 'service_marker',null),
            'status'            => $this->status,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'description'       => $this->description,
            'commission_type'   => $this->commission_type,
            'admin_commission'  => $this->admin_commission,
            'fleet_commission'  => $this->fleet_commission,
            'coupon_discount'  => optional($this->driverRideRequestDetail),
        ];

        // caclulate ride
        $ridefee = calculateRideFares($distance_in_unit,$pick_lat, $pick_lng, $drop_lat, $drop_lng, is_array($multi_location) ? $multi_location : [],$dropoff_time_in_seconds, $service_data, $coupon,$surge_price,$date_time);

        return array_merge($service_data, $ridefee);
    }
}