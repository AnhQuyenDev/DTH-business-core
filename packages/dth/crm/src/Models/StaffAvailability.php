<?php
namespace Dth\Crm\Models; use Illuminate\Database\Eloquent\Model;
class StaffAvailability extends Model {protected $table='crm_staff_availabilities';protected $guarded=[];protected function casts():array{return ['date'=>'date'];}}
