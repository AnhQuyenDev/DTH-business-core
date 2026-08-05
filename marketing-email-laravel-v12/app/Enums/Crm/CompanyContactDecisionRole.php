<?php

namespace App\Enums\Crm;

enum CompanyContactDecisionRole: string
{
    case DecisionMaker = 'decision_maker';
    case Influencer = 'influencer';
    case TechnicalContact = 'technical_contact';
    case BillingContact = 'billing_contact';
    case EndUser = 'end_user';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DecisionMaker => 'Người quyết định',
            self::Influencer => 'Người ảnh hưởng',
            self::TechnicalContact => 'Liên hệ kỹ thuật',
            self::BillingContact => 'Liên hệ thanh toán',
            self::EndUser => 'Người sử dụng',
            self::Other => 'Khác',
        };
    }
}
