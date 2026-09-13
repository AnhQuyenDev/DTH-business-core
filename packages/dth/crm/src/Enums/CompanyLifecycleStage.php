<?php
namespace Dth\Crm\Enums;
enum CompanyLifecycleStage:string { case Prospect='prospect'; case Qualified='qualified'; case Customer='customer'; case Inactive='inactive';
 public static function options():array{return ['prospect'=>'Tiềm năng','qualified'=>'Đã xác nhận','customer'=>'Khách hàng','inactive'=>'Ngừng hoạt động'];}}
