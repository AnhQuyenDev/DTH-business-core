<?php
namespace Dth\Crm\Models;
use Illuminate\Database\Eloquent\Model; 
class AuditLog extends Model {  protected $table='crm_audit_logs'; protected $guarded=[]; protected $fillable=['event','subject_type','subject_id','actor_user_id','changes','context']; protected function casts():array{return ['changes'=>'array','context'=>'array'];} }
