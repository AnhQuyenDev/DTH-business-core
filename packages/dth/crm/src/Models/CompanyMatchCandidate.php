<?php
namespace Dth\Crm\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CompanyMatchCandidate extends Model {protected $table='crm_company_match_candidates';protected $guarded=[];protected function casts():array{return ['reviewed_at'=>'datetime'];} public function contact():BelongsTo{return $this->belongsTo(Contact::class);} public function suggestedCompany():BelongsTo{return $this->belongsTo(Company::class,'suggested_company_id');}}
