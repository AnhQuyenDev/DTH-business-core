<?php
namespace Dth\Crm\Models;
use Illuminate\Database\Eloquent\Model; 
class LeadActivity extends Model {  protected $table='crm_lead_activities'; protected $guarded=[]; protected $fillable=['lead_id','staff_id','type','status','subject','content','outcome','activity_at','next_follow_up_at','metadata']; protected function casts():array{return ['activity_at'=>'datetime','next_follow_up_at'=>'datetime','metadata'=>'array'];} }
