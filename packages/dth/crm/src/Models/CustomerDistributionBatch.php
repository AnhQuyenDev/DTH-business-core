<?php
namespace Dth\Crm\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class CustomerDistributionBatch extends Model {protected $table='crm_customer_distribution_batches';protected $guarded=[];protected function casts():array{return ['criteria'=>'array'];}protected static function booted():void{static::creating(function(self $m){if(blank($m->batch_code))$m->batch_code=app(\Dth\Crm\Support\CodeGenerator::class)->make('DIST');});}public function items():HasMany{return $this->hasMany(CustomerDistributionItem::class,'distribution_batch_id');}}
