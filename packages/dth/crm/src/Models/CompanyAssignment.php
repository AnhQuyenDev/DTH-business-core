<?php
namespace Dth\Crm\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CompanyAssignment extends Model {protected $table='crm_company_assignments';protected $guarded=[];protected function casts():array{return ['starts_at'=>'datetime','ends_at'=>'datetime'];}public function staff():BelongsTo{return $this->belongsTo(Staff::class);}public function company():BelongsTo{return $this->belongsTo(Company::class);}}
