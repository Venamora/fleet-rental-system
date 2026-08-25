<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalHistoryEvent extends Model
{
    protected $table = 'rental_history_events';
    public $timestamps = false;
    protected $fillable = ['rental_id','event_type','occurred_at','state','reason','effective_end_date'];
    protected $casts = ['occurred_at'=>'datetime','effective_end_date'=>'date:Y-m-d'];
}
