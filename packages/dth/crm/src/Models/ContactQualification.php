<?php
namespace Dth\Crm\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class ContactQualification extends Model {protected $table='crm_contact_qualifications';protected $guarded=[];protected function casts():array{return ['estimated_value'=>'decimal:2','budget_amount'=>'decimal:2','first_contacted_at'=>'datetime','last_contacted_at'=>'datetime','next_follow_up_at'=>'datetime','qualified_at'=>'datetime','converted_at'=>'datetime'];}
 public function lead():BelongsTo{return $this->belongsTo(Lead::class);} public function contact():BelongsTo{return $this->belongsTo(Contact::class);} public function assignedStaff():BelongsTo{return $this->belongsTo(Staff::class,'assigned_staff_id');} public function notes():HasMany{return $this->hasMany(QualificationNote::class,'qualification_id');} }
