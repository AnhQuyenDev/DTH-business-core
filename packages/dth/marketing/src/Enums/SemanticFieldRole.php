<?php

namespace Dth\Marketing\Enums;

use Dth\Marketing\Support\UiText;

enum SemanticFieldRole: string
{
    case PersonName = 'person.name';
    case ContactEmail = 'contact.email';
    case ContactPhone = 'contact.phone';
    case PersonAddress = 'person.address';
    case PersonDateOfBirth = 'person.date_of_birth';
    case CompanyName = 'company.name';
    case CompanyTaxCode = 'company.tax_code';
    case CompanyRepresentative = 'company.representative';
    case CompanyPosition = 'company.position';
    case CompanyWebsite = 'company.website';
    case ServiceInterest = 'service.interest';
    case ContactNotes = 'contact.notes';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::PersonName->value => UiText::get('form.semantic.person_name', 'Customer / contact name'),
            self::ContactEmail->value => UiText::get('form.semantic.contact_email', 'Email address'),
            self::ContactPhone->value => UiText::get('form.semantic.contact_phone', 'Phone number'),
            self::PersonAddress->value => UiText::get('form.semantic.person_address', 'Address'),
            self::PersonDateOfBirth->value => UiText::get('form.semantic.person_date_of_birth', 'Date of birth'),
            self::CompanyName->value => UiText::get('form.semantic.company_name', 'Company name'),
            self::CompanyTaxCode->value => UiText::get('form.semantic.company_tax_code', 'Tax code'),
            self::CompanyRepresentative->value => UiText::get('form.semantic.company_representative', 'Company representative'),
            self::CompanyPosition->value => UiText::get('form.semantic.company_position', 'Contact position / title'),
            self::CompanyWebsite->value => UiText::get('form.semantic.company_website', 'Company website'),
            self::ServiceInterest->value => UiText::get('form.semantic.service_interest', 'Service / package interest'),
            self::ContactNotes->value => UiText::get('form.semantic.contact_notes', 'Notes / customer need'),
        ];
    }

    public static function labelFor(mixed $state): string
    {
        $value = $state instanceof \BackedEnum ? (string) $state->value : (string) $state;

        return self::options()[$value] ?? UiText::status($value);
    }

    public function legacyContactMapping(): ?string
    {
        return match ($this) {
            self::PersonName, self::CompanyRepresentative => 'lead.name',
            self::ContactEmail => 'lead.email',
            self::ContactPhone => 'lead.phone',
            self::CompanyName => 'lead.company_name',
            self::CompanyTaxCode => 'lead.tax_code',
            self::CompanyPosition => 'lead.position',
            self::ServiceInterest => 'lead.service_interest',
            default => null,
        };
    }

    public static function fromLegacyContactMapping(?string $mapping): ?self
    {
        return match (trim((string) $mapping)) {
            'lead.name' => self::PersonName,
            'lead.email' => self::ContactEmail,
            'lead.phone' => self::ContactPhone,
            'lead.company_name' => self::CompanyName,
            'lead.tax_code' => self::CompanyTaxCode,
            'lead.position' => self::CompanyPosition,
            'lead.service_interest' => self::ServiceInterest,
            default => null,
        };
    }
}
