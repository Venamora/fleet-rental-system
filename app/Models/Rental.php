<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    protected $fillable = ['vehicle_id','customer_id','start_date','end_date','effective_end_date','status','daily_rate_snapshot_cents','subtotal_cents','discount_cents','total_cents','cancellation_reason','cancelled_at'];
    protected $casts = ['start_date'=>'date:Y-m-d','end_date'=>'date:Y-m-d','effective_end_date'=>'date:Y-m-d','cancelled_at'=>'datetime','daily_rate_snapshot_cents'=>'integer','subtotal_cents'=>'integer','discount_cents'=>'integer','total_cents'=>'integer'];
    public function vehicle() { return $this->belongsTo(Vehicle::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function history() { return $this->hasMany(RentalHistoryEvent::class); }
}
