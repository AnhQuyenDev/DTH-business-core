<?php
namespace Dth\Crm\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class CustomerInteraction extends Model { use SoftDeletes; protected $table='crm_customer_interactions'; protected $guarded=[]; protected $fillable=['customer_id','staff_id','interaction_type','subject','content','outcome','interaction_at','next_follow_up_at','is_support_action','status']; protected function casts():array{return ['interaction_at'=>'datetime','next_follow_up_at'=>'datetime','is_support_action'=>'boolean'];} }
