<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Vehicle extends Model
{
    protected $fillable = ['brand_id','type_id','plate','daily_rate_cents','year','color','archived_at'];
    protected $casts = ['daily_rate_cents'=>'integer','year'=>'integer','archived_at'=>'datetime'];
    public function brand() { return $this->belongsTo(Brand::class); }
    public function type() { return $this->belongsTo(Type::class); }
    public function getDerivedStatusAttribute(): string { return $this->archived_at ? 'archived' : 'tersedia'; }
}
