<?php
namespace Dth\Crm\Support;
final class CrmAuthorization {public function allows(string $ability, mixed $user=null):bool{$mode=config('dth-crm.authorization_mode','auto');if($mode==='off')return true;$user??=auth()->user();if(!$user)return false;$gate=app(\Illuminate\Contracts\Auth\Access\Gate::class);if($mode==='auto'&&!$gate->has($ability))return true;return $gate->forUser($user)->allows($ability);} }
