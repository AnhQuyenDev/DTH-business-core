<?php
namespace Dth\Crm\Services; use Dth\Crm\Models\AuditLog;
final class AuditService {public function log(string $event,mixed $subject=null,array $changes=[],array $context=[]):void{AuditLog::create(['event'=>$event,'subject_type'=>is_object($subject)?$subject::class:null,'subject_id'=>is_object($subject)?(string)($subject->getKey()??''):null,'actor_user_id'=>auth()->id(),'changes'=>$changes,'context'=>$context]);}}
