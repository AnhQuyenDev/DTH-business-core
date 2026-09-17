<?php
namespace Dth\Commercial\Enums;
use Dth\Commercial\Support\UiText;
enum AudienceType:string {
    case Personal='personal'; case Business='business'; case Both='both';
    public static function options():array { return [self::Personal->value=>UiText::get('audience.personal','Personal'), self::Business->value=>UiText::get('audience.business','Business'), self::Both->value=>UiText::get('audience.both','Personal & business')]; }
}
