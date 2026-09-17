<?php
namespace Dth\Commercial\Enums;
use Dth\Commercial\Support\UiText;
enum ServiceStatus:string {
    case Active='active'; case Inactive='inactive'; case Archived='archived';
    public static function options():array { return [self::Active->value=>UiText::get('status.active','Active'), self::Inactive->value=>UiText::get('status.inactive','Inactive'), self::Archived->value=>UiText::get('status.archived','Archived')]; }
}
