<?php
namespace Dth\Crm\Enums;
enum LeadIntakeStatus:string { case New='new'; case Active='active'; case Duplicate='duplicate'; case Spam='spam'; case Closed='closed'; case ConvertedToOpportunity='converted_to_opportunity';
 public static function options():array{return ['new'=>'Mới','active'=>'Đang xử lý','duplicate'=>'Trùng','spam'=>'Spam','closed'=>'Đã đóng','converted_to_opportunity'=>'Đã chuyển Cơ hội'];}}
