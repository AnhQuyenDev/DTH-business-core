<?php
namespace Dth\Crm\Models; use Illuminate\Database\Eloquent\Model;
class PersonalContactProfile extends Model {protected $table='crm_personal_contact_profiles';protected $guarded=[];protected function casts():array{return ['date_of_birth'=>'date','expected_budget'=>'decimal:2','metadata'=>'array'];}}
