<?php
namespace Dth\Commercial\Enums;
use Dth\Commercial\Support\UiText;
enum BillingPeriodUnit:string {
    case Day='day'; case Month='month'; case Year='year'; case OneTime='one_time';
    public static function options():array { return [self::Day->value=>UiText::get('billing.day','Day'), self::Month->value=>UiText::get('billing.month','Month'), self::Year->value=>UiText::get('billing.year','Year'), self::OneTime->value=>UiText::get('billing.one_time','One time')]; }
}
