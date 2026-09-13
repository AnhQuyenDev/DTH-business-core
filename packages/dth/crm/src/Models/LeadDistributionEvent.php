<?php
namespace Dth\Crm\Models;
use Illuminate\Database\Eloquent\Model; 
class LeadDistributionEvent extends Model {  protected $table='crm_lead_distribution_events'; protected $guarded=[]; protected $fillable=['lead_id','from_staff_id','to_staff_id','strategy','event','status','reason','actor_user_id','responded_at']; protected function casts():array{return ['responded_at'=>'datetime'];} }
