<?php
namespace Dth\Crm\Enums;
enum QualificationStatus:string { case New='new'; case Assigned='assigned'; case Contacting='contacting'; case FollowUp='follow_up'; case Qualified='qualified'; case Unqualified='unqualified'; case Converted='converted'; case Duplicate='duplicate'; case Spam='spam'; case Archived='archived';
 public static function options():array{return ['new'=>'Mới','assigned'=>'Đã phân phối','contacting'=>'Đang liên hệ','follow_up'=>'Theo dõi','qualified'=>'Đủ điều kiện','unqualified'=>'Không đủ điều kiện','converted'=>'Đã chuyển đổi','duplicate'=>'Trùng','spam'=>'Spam','archived'=>'Lưu trữ'];}}
