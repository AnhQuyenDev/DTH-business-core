<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\SemanticFieldRole;
use Illuminate\Support\Str;

final class SemanticFieldResolver
{
    public const AUTO_THRESHOLD = 85;
    public const SUGGEST_THRESHOLD = 60;

    /**
     * @param array<string, mixed> $htmlAttributes
     * @return array{role:?SemanticFieldRole,confidence:int,source:?string,reason:?string}
     */
    public function resolve(
        string $fieldKey,
        string $label = '',
        ?string $placeholder = null,
        FormFieldType|string|null $fieldType = null,
        FormAudienceType|string|null $audienceType = null,
        array $htmlAttributes = [],
    ): array {
        $type = $fieldType instanceof FormFieldType
            ? $fieldType
            : FormFieldType::tryFrom((string) $fieldType);
        $audience = $audienceType instanceof FormAudienceType
            ? $audienceType
            : FormAudienceType::tryFrom((string) $audienceType);

        $signals = [
            'key' => $this->normalize($fieldKey),
            'label' => $this->normalize($label),
            'placeholder' => $this->normalize((string) $placeholder),
            'id' => $this->normalize((string) ($htmlAttributes['id'] ?? '')),
            'name' => $this->normalize((string) ($htmlAttributes['name'] ?? '')),
            'autocomplete' => $this->normalize((string) ($htmlAttributes['autocomplete'] ?? '')),
            'aria' => $this->normalize((string) ($htmlAttributes['aria-label'] ?? '')),
            'input_type' => $this->normalize((string) ($htmlAttributes['type'] ?? '')),
        ];

        /** @var array<string, array{score:int,reason:string}> $scores */
        $scores = [];
        $add = static function (SemanticFieldRole $role, int $score, string $reason) use (&$scores): void {
            $key = $role->value;
            if (! isset($scores[$key]) || $score > $scores[$key]['score']) {
                $scores[$key] = ['score' => min(100, $score), 'reason' => $reason];
            }
        };

        // Structural HTML signals are strongest and do not depend on the author's key naming.
        if ($type === FormFieldType::Email || $signals['input_type'] === 'email') {
            $add(SemanticFieldRole::ContactEmail, 100, 'email input type');
        }
        if ($type === FormFieldType::Phone || in_array($signals['input_type'], ['tel', 'phone'], true)) {
            $add(SemanticFieldRole::ContactPhone, 100, 'telephone input type');
        }

        $autocomplete = $signals['autocomplete'];
        if ($autocomplete !== '') {
            if ($this->containsAny($autocomplete, ['email'])) {
                $add(SemanticFieldRole::ContactEmail, 100, 'autocomplete=email');
            }
            if ($this->containsAny($autocomplete, ['tel', 'mobile'])) {
                $add(SemanticFieldRole::ContactPhone, 100, 'autocomplete=tel');
            }
            if ($this->containsAny($autocomplete, ['organization title'])) {
                $add(SemanticFieldRole::CompanyPosition, 98, 'autocomplete=organization-title');
            }
            if ($this->containsAny($autocomplete, ['organization'])) {
                $add(SemanticFieldRole::CompanyName, 98, 'autocomplete=organization');
            }
            if ($this->containsAny($autocomplete, ['street address', 'address line', 'postal address'])) {
                $add(SemanticFieldRole::PersonAddress, 98, 'autocomplete=address');
            }
            if ($this->containsAny($autocomplete, ['bday', 'birthday'])) {
                $add(SemanticFieldRole::PersonDateOfBirth, 98, 'autocomplete=bday');
            }
            if ($this->containsAny($autocomplete, ['name', 'given name', 'family name'])) {
                $add(SemanticFieldRole::PersonName, 96, 'autocomplete=name');
            }
        }

        $combined = trim(implode(' ', array_filter([
            $signals['key'],
            $signals['label'],
            $signals['placeholder'],
            $signals['id'],
            $signals['name'],
            $signals['aria'],
        ])));

        $this->scorePhrases($add, SemanticFieldRole::ContactEmail, $combined, [
            'email', 'e mail', 'email address', 'mail address', 'thu dien tu', 'email ca nhan', 'email doanh nghiep',
        ], 96, 'email wording');
        $this->scorePhrases($add, SemanticFieldRole::ContactPhone, $combined, [
            'phone', 'phone number', 'telephone', 'mobile', 'mobile number', 'hotline', 'dien thoai', 'so dien thoai', 'sdt',
        ], 96, 'phone wording');
        $this->scorePhrases($add, SemanticFieldRole::ServiceInterest, $combined, [
            'service interest', 'service', 'service package', 'package', 'product interest', 'product',
            'dich vu', 'goi dich vu', 'goi hosting', 'goi san pham', 'san pham', 'nhu cau dich vu',
        ], 92, 'service/package wording');
        $this->scorePhrases($add, SemanticFieldRole::CompanyTaxCode, $combined, [
            'tax code', 'tax id', 'vat number', 'vat', 'ma so thue', 'mst',
        ], 98, 'tax-code wording');
        $this->scorePhrases($add, SemanticFieldRole::CompanyWebsite, $combined, [
            'company website', 'website', 'web site', 'website doanh nghiep', 'trang web',
        ], 90, 'website wording');
        $this->scorePhrases($add, SemanticFieldRole::CompanyPosition, $combined, [
            'job title', 'position', 'contact position', 'organization title', 'role', 'chuc vu', 'chuc danh',
        ], 94, 'position/title wording');
        $this->scorePhrases($add, SemanticFieldRole::PersonAddress, $combined, [
            'address', 'street address', 'dia chi', 'dia chi lien he',
        ], 92, 'address wording');
        $this->scorePhrases($add, SemanticFieldRole::PersonDateOfBirth, $combined, [
            'date of birth', 'birthday', 'birth date', 'dob', 'ngay sinh',
        ], 95, 'date-of-birth wording');
        $this->scorePhrases($add, SemanticFieldRole::ContactNotes, $combined, [
            'notes', 'note', 'message', 'comment', 'customer need', 'requirements', 'request',
            'ghi chu', 'noi dung', 'nhu cau', 'yeu cau', 'loi nhan',
        ], $type === FormFieldType::Textarea ? 90 : 78, 'notes/message wording');

        // Company-specific phrases must outrank generic "name" phrases.
        $this->scorePhrases($add, SemanticFieldRole::CompanyName, $combined, [
            'company name', 'business name', 'organization name', 'organisation name', 'enterprise name',
            'ten cong ty', 'ten doanh nghiep',
        ], 99, 'company-name wording');
        $this->scorePhrases($add, SemanticFieldRole::CompanyRepresentative, $combined, [
            'company representative', 'legal representative', 'representative', 'contact person',
            'nguoi dai dien', 'dai dien lien he', 'nguoi lien he', 'nguoi dai dien lien he',
        ], 100, 'representative wording');

        $this->scorePhrases($add, SemanticFieldRole::PersonName, $combined, [
            'full name', 'fullname', 'customer name', 'customer fullname', 'contact name', 'contact fullname', 'your name', 'first name', 'last name',
            'ho va ten', 'ho ten', 'ten khach hang', 'ten lien he', 'ten day du',
        ], 98, 'person-name wording');

        $exactKey = $signals['key'];
        if (in_array($exactKey, ['name', 'fullname', 'full name', 'ho ten', 'ho va ten', 'hoten'], true)) {
            $add(
                SemanticFieldRole::PersonName,
                $audience === FormAudienceType::Business ? 78 : 92,
                'generic name key',
            );
        }
        if (in_array($exactKey, ['company', 'company name', 'business name', 'cong ty', 'ten cong ty', 'ten doanh nghiep'], true)) {
            $add(SemanticFieldRole::CompanyName, 96, 'company key');
        }

        // A very generic Vietnamese/English "Name" label can still be useful in
        // a Personal form, but stays a suggestion in Business forms.
        if (in_array($signals['label'], ['name', 'ten'], true)) {
            if ($audience === FormAudienceType::Personal) {
                $add(SemanticFieldRole::PersonName, 88, 'personal form generic name label');
            } elseif ($audience === FormAudienceType::Business) {
                $add(SemanticFieldRole::CompanyRepresentative, 86, 'business form generic name label');
            }
        }

        if ($scores === []) {
            return ['role' => null, 'confidence' => 0, 'source' => null, 'reason' => null];
        }

        uasort($scores, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        $bestKey = (string) array_key_first($scores);
        $best = $scores[$bestKey];

        if ($best['score'] < self::SUGGEST_THRESHOLD) {
            return ['role' => null, 'confidence' => $best['score'], 'source' => null, 'reason' => $best['reason']];
        }

        return [
            'role' => SemanticFieldRole::tryFrom($bestKey),
            'confidence' => $best['score'],
            'source' => $best['score'] >= self::AUTO_THRESHOLD ? 'auto' : 'suggested',
            'reason' => $best['reason'],
        ];
    }

    /**
     * @param callable(SemanticFieldRole,int,string):void $add
     * @param array<int, string> $phrases
     */
    private function scorePhrases(
        callable $add,
        SemanticFieldRole $role,
        string $haystack,
        array $phrases,
        int $score,
        string $reason,
    ): void {
        if ($this->containsAny($haystack, $phrases)) {
            $add($role, $score, $reason);
        }
    }

    /** @param array<int, string> $needles */
    private function containsAny(string $haystack, array $needles): bool
    {
        if ($haystack === '') {
            return false;
        }

        foreach ($needles as $needle) {
            $needle = $this->normalize($needle);
            if ($needle === '') {
                continue;
            }

            if ($haystack === $needle || str_contains(' '.$haystack.' ', ' '.$needle.' ')) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = Str::lower(Str::ascii($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
