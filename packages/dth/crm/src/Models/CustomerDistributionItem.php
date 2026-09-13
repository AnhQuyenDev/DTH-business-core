<?php
namespace Dth\Crm\Models;
use Illuminate\Database\Eloquent\Model; 
class CustomerDistributionItem extends Model {  protected $table='crm_customer_distribution_items'; protected $guarded=[]; protected $fillable=['distribution_batch_id','customer_id','original_owner_staff_id','assigned_staff_id','assignment_type','result_status','reason']; protected function casts():array{return [];} }
