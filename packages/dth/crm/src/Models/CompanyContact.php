<?php
namespace Dth\Crm\Models; use Illuminate\Database\Eloquent\Model;
class CompanyContact extends Model {protected $table='crm_company_contacts';protected $guarded=[];protected function casts():array{return ['is_primary'=>'boolean','is_active'=>'boolean'];}}
