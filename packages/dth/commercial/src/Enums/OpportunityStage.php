<?php
namespace Dth\Commercial\Enums;
use Dth\Commercial\Support\UiText;
enum OpportunityStage:string {
    case Discovery='discovery'; case Qualified='qualified'; case Proposal='proposal'; case Negotiation='negotiation'; case Won='won'; case Lost='lost'; case Cancelled='cancelled';
    public static function options():array { $out=[]; foreach(self::cases() as $case){$out[$case->value]=$case->label();} return $out; }
    public function label():string { return UiText::get('opportunity_stage.'.$this->value, match($this){self::Discovery=>'Discovery',self::Qualified=>'Qualified',self::Proposal=>'Proposal',self::Negotiation=>'Negotiation',self::Won=>'Won',self::Lost=>'Lost',self::Cancelled=>'Cancelled'}); }
    public function probability():int { return match($this){self::Discovery=>20,self::Qualified=>50,self::Proposal=>70,self::Negotiation=>85,self::Won=>100,self::Lost,self::Cancelled=>0}; }
    public function isTerminal():bool { return in_array($this,[self::Won,self::Lost,self::Cancelled],true); }
}
