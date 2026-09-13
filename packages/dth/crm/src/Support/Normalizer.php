<?php
namespace Dth\Crm\Support;
use Illuminate\Support\Str;
final class Normalizer {
 public function email(?string $v):?string{$v=strtolower(trim((string)$v));return $v!==''?$v:null;}
 public function phone(?string $v):?string{$digits=preg_replace('/\D+/','',(string)$v)??'';if(str_starts_with($digits,'84'))$digits='0'.substr($digits,2);return $digits!==''?$digits:null;}
 public function taxCode(?string $v):?string{$v=preg_replace('/\s+/','',strtoupper(trim((string)$v)))??'';return $v!==''?$v:null;}
 public function companyName(?string $v):?string{$v=Str::of((string)$v)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/',' ')->squish()->value();return $v!==''?$v:null;}
 public function domain(?string $email):?string{$email=$this->email($email);if(!$email||!str_contains($email,'@'))return null;$d=explode('@',$email,2)[1]?:null;return $d&&!$this->isFreeEmailDomain($d)?$d:null;} public function isFreeEmailDomain(?string $d):bool{return in_array(strtolower((string)$d),['gmail.com','yahoo.com','outlook.com','hotmail.com','icloud.com','proton.me','protonmail.com'],true);}
 }
