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
        return __('enum.company_contact_decision_role.'.$this->value);
    }
}
