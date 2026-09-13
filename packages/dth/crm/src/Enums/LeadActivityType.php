<?php
namespace Dth\Crm\Enums;
enum LeadActivityType:string { case Call='call'; case Email='email'; case Meeting='meeting'; case Message='message'; case Note='note'; case Other='other';
 public static function options():array{return ['call'=>'Cuộc gọi','email'=>'Email','meeting'=>'Cuộc họp','message'=>'Tin nhắn','note'=>'Ghi chú','other'=>'Khác'];}}
