<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideRequest extends Model
{
    use HasFactory;

    protected $fillable = [ 'rider_id', 'service_id', 'datetime', 'is_schedule','schedule_datetime', 'ride_attempt', 'distance_unit', 'total_amount','surge_amount', 'subtotal', 'extra_charges_amount', 'driver_id', 'start_latitude', 'start_longitude', 'end_latitude', 'start_address', 'end_longitude', 'end_address', 'distance', 'duration', 'seat_count', 'reason', 'status','ride_has_bid', 'base_fare', 'minimum_fare', 'base_distance', 'per_distance', 'per_distance_charge', 'per_minute_drive', 'per_minute_drive_charge', 'payment_type', 'extra_charges', 'tips', 'cancel_by', 'cancelation_charges', 'coupon_discount','coupon_code', 'coupon_data', 'otp', 'waiting_time_limit', 'waiting_time', 'per_minute_waiting', 'per_minute_waiting_charge', 'cancelled_driver_ids','nearby_driver_ids','rejected_bid_driver_ids', 'service_data', 'max_time_for_find_driver_for_ride_request', 'is_rider_rated', 'is_driver_rated', 'riderequest_in_driver_id', 'riderequest_in_datetime', 'is_ride_for_other','other_rider_data', 'drop_location', 'datetime_utc','multi_drop_location' ];

    protected $casts = [
        'rider_id'      => 'integer',
        'service_id'    => 'integer',
        'driver_id'     => 'integer',
        'ride_attempt'  => 'integer',
        'total_amount'  => 'double',
        'surge_amount'  => 'double',
        'subtotal'      => 'double',
        'distance'      => 'double',
        'base_distance' => 'double',
        'duration'      => 'double',
        'seat_count'    => 'double',
        'base_fare'     => 'double',
        'minimum_fare'  => 'double',
        'per_distance'  => 'double',
        'waiting_time'  => 'double',
        'tips'          => 'double',
        'coupon_code'   => 'integer',
        'is_schedule'       => 'integer',
        'riderequest_in_driver_id'  => 'integer',
        'is_driver_rated'   => 'integer',
        'is_rider_rated'    => 'integer',
        'coupon_discount'   => 'double',
        'per_minute_drive'  => 'double',
        'per_distance_charge'   => 'double',
        'cancelation_charges'   => 'double',
        'waiting_time_limit'    => 'double',
        'per_minute_waiting'    => 'double',
        'extra_charges_amount'  => 'double',
        'per_minute_drive_charge'   => 'double',
        'per_minute_waiting_charge' => 'double',
        'max_time_for_find_driver_for_ride_request' => 'double',
        'is_ride_for_other' => 'integer',
    ];
    
    public function rider() {
        return $this->belongsTo( User::class, 'rider_id', 'id');
    }

    public function driver() {
        return $this->belongsTo( User::class, 'driver_id', 'id');
    }

    public function riderequest_in_driver() {
        return $this->belongsTo( User::class, 'riderequest_in_driver_id', 'id');
    }

    // public function nearby_drivers()
    // {
    //     return User::whereIn('id', json_decode($this->nearby_driver_ids, true))->get();
    // }

    public function nearby_drivers() {
        return $this->belongsTo( User::class, 'nearby_driver_ids', 'id');
    }

    public function service() {
        return $this->belongsTo( Service::class, 'service_id', 'id');
    }

    public function payment() {
        return $this->hasOne( Payment::class, 'ride_request_id', 'id');
    }

    public function rideRequestHistory(){
        return $this->hasMany(RideRequestHistory::class, 'ride_request_id', 'id');
    }

    public function rideRequestStartTime() {
        return $this->rideRequestHistory()->where('history_type', 'in_progress')->pluck('created_at')->first();
    }

    public function rideRequestCompletedTime() {
        return $this->rideRequestHistory()->where('history_type', 'completed')->pluck('created_at')->first();
    }

    public function rideRequestRating()
    {
        return $this->hasMany(RideRequestRating::class, 'ride_request_id', 'id');
    }

    public function rideRequestRiderRating()
    {
        return $this->rideRequestRating()->where('rating_by', 'driver')->first();
    }

    public function rideRequestDriverRating()
    {
        return $this->rideRequestRating()->where('rating_by', 'rider')->first();
    }

    public function complaint()
    {
        return $this->hasMany(Complaint::class, 'ride_request_id', 'id');
    }

    public function bids()
    {
        return $this->hasMany(RideRequestBid::class, 'ride_request_id');
    }

    public function approvedBids()
    {
        return $this->hasOne(RideRequestBid::class, 'ride_request_id')->where('is_bid_accept',1);
    }

    public function drivers()
    {
        return $this->hasManyThrough(User::class, RideRequestBid::class, 'ride_request_id', 'id', 'id', 'driver_id');
    }

    public function rideRequestRiderComplaint()
    {
        return $this->complaint()->where('complaint_by', 'rider')->first();
    }

    public function rideRequestDriverComplaint()
    {
        return $this->complaint()->where('complaint_by', 'driver')->first();
    }

    public function getExtraChargesAttribute($value)
    {
        $val = isset($value) ? json_decode($value, true) : [];
        return $val;
    }

    public function setExtraChargesAttribute($value)
    {
        $this->attributes['extra_charges'] = isset($value) ? json_encode($value) : null;
    }

    public function getCouponDataAttribute($value)
    {
        return isset($value) ? json_decode($value, true) : null;
    }

    public function setCouponDataAttribute($value)
    {
        $this->attributes['coupon_data'] = isset($value) ? json_encode($value) : null;
    }

    public function getServiceDataAttribute($value)
    {
        return isset($value) ? json_decode($value, true) : null;
    }

    public function setServiceDataAttribute($value)
    {
        $this->attributes['service_data'] = isset($value) ? json_encode($value) : null;
    }

    protected static function boot(){
        parent::boot();
        static::deleted(function ($row) {
            $row->rideRequestHistory()->delete();
            $row->rideRequestRating()->delete();
        });
    }

    public function getCancelledDriverIdsAttribute($value)
    {
        $val = isset($value) ? json_decode($value, true) : [];
        return $val;
    }

    public function setCancelledDriverIdsAttribute($value)
    {
        $this->attributes['cancelled_driver_ids'] = isset($value) ? json_encode($value) : [];
    }

    public function riderequest_history_data($type)
    {
        return $this->rideRequestHistory()->where('history_type',$type)->pluck('datetime')->first();
    }
    public function scopeMyRide($query)
    {
        $user = auth()->user();

        if($user->hasAnyRole(['admin','demo_admin']) ) {
            return $query;
        }

        if($user->hasRole('fleet')) {
            return $query->whereHas('driver',function ($q) use($user) {
                $q->where('fleet_id',$user->id);
            });
        }

        if($user->user_type == 'rider') {
            return $query->where('rider_id', $user->id);
        }

        if($user->user_type = 'driver') {
            return $query->where('driver_id', $user->id);
        }

        return $query;
    }

    public function getOtherRiderDataAttribute($value)
    {
        return isset($value) ? json_decode($value, true) : null;
    }

    public function setOtherRiderDataAttribute($value)
    {
        $this->attributes['other_rider_data'] = isset($value) ? json_encode($value) : null;
    }

    public function getDropLocationAttribute($value)
    {
        return isset($value) ? json_decode($value, true) : null;
    }

    // public function setDropLocationAttribute($value)
    // {
    //     $this->attributes['drop_location'] = isset($value) ? json_encode($value) : null;
    // }
}
