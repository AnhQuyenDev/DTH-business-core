<?php
namespace Dth\Crm\Enums;
enum CustomerStatus:string { case Potential='potential'; case Active='active'; case Inactive='inactive'; case Churned='churned';
 public static function options():array{return ['potential'=>'Tiềm năng','active'=>'Đang hoạt động','inactive'=>'Không hoạt động','churned'=>'Rời bỏ'];}}
