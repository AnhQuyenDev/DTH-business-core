<?php
namespace Dth\Crm\Models; use Illuminate\Database\Eloquent\Model;
class BusinessContactProfile extends Model {protected $table='crm_business_contact_profiles';protected $guarded=[];protected function casts():array{return ['tax_verification_data'=>'array'];}}
