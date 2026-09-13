<?php
namespace Dth\Crm\Services; use Dth\Crm\Models\Customer;
final class CustomerCareService {public function stats(Customer $c):array{return ['interactions'=>$c->interactions()->count(),'last_interaction_at'=>$c->interactions()->max('interaction_at'),'next_follow_up_at'=>$c->next_follow_up_at,'active_assignments'=>$c->assignments()->where('status','active')->count()];} public function timeline(Customer $c){return $c->interactions()->withTrashed()->orderByDesc('interaction_at')->get();}}
