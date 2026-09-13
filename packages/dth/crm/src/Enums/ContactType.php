<?php
namespace Dth\Crm\Enums;
enum ContactType:string { case Personal='personal'; case Business='business';
 public static function options():array{return ['personal'=>'Cá nhân','business'=>'Doanh nghiệp'];}}
