<?php
namespace Dth\Crm\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes; use Illuminate\Database\Eloquent\Relations\HasMany;
class Staff extends Model {use SoftDeletes;protected $table='crm_staff';protected $guarded=[];protected static function booted():void{static::creating(function(self $m){if(blank($m->staff_code))$m->staff_code=app(\Dth\Crm\Support\CodeGenerator::class)->make('STF');});}public function availabilities():HasMany{return $this->hasMany(StaffAvailability::class);} public function leads():HasMany{return $this->hasMany(Lead::class,'assigned_staff_id');}}
