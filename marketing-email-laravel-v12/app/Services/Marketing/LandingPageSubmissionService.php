<?php

namespace App\Services\Marketing;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\TaxVerificationStatus;
use App\Enums\Marketing\LandingPageContactAction;
use App\Enums\Marketing\LandingPageSubmissionStatus;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\PersonalContactProfile;
use App\Models\Marketing\Contact;
use App\Models\Marketing\ContactCustomFieldValue;
use App\Models\Marketing\ContactList;
use App\Models\Marketing\CustomField;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\Segment;
use App\Models\Marketing\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LandingPageSubmissionService
{
    public function handle(LandingPage $landingPage, array $payload, Request $request, ?int $campaignId = null): LandingPageSubmission
    {
        $submissionType = $payload['submission_type'] ?? 'personal';

        // Generic form dùng customer_type từ payload để xác định personal/business
        $resolvedType = $submissionType;
        if ($submissionType === 'generic' && ! empty($payload['customer_type'])) {
            $resolvedType = $payload['customer_type'];
        }

        $landingPageForm = $landingPage->forms()
            ->where('form_type', $submissionType)
            ->where('status', 'active')
            ->first();
        $formTemplate = $landingPageForm?->formTemplate;
        $formTemplateId = $formTemplate?->id;

        $validatedData = $this->validatePayload($formTemplate, $payload, $submissionType);
        $utm = app(LandingPageTrackingService::class)->extractUtm($request);

        $emailField = $resolvedType === 'business' ? 'business_email' : 'email';
        $phoneField = $resolvedType === 'business' ? 'business_phone' : 'phone';
        $emailKey = $this->findFieldKeyByMapping($formTemplate, $resolvedType, $emailField);
        $phoneKey = $this->findFieldKeyByMapping($formTemplate, $resolvedType, $phoneField);
        $normalizedEmail = isset($validatedData[$emailKey]) ? strtolower(trim($validatedData[$emailKey])) : null;
        $normalizedPhone = isset($validatedData[$phoneKey]) ? preg_replace('/[^0-9]/', '', $validatedData[$phoneKey]) : null;

        $contact = $this->findDuplicateContact($normalizedEmail, $normalizedPhone, $validatedData);

        if (! $contact) {
            $contact = Contact::create(['contact_type' => $resolvedType]);
            $action = LandingPageContactAction::Created;
        } else {
            $action = LandingPageContactAction::Updated;
        }

        if ($resolvedType === 'personal') {
            $this->savePersonalProfile($contact, $validatedData, $normalizedEmail, $normalizedPhone, $formTemplate);
        } elseif ($resolvedType === 'business') {
            $profile = $this->saveBusinessProfile($contact, $validatedData, $normalizedEmail, $normalizedPhone, $formTemplate);
            // Tax code verification happens manually via the "Xác thực MST" button on Form Submissions page
        }

        $this->applyTagsAndLists($contact, $landingPage, $formTemplate, $validatedData);
        $this->saveCustomFields($contact, $formTemplate, $validatedData);

        $qualification = ContactQualification::firstOrCreate(
            ['contact_id' => $contact->id],
            ['status' => ContactQualificationStatus::New->value]
        );

        $taxCodeKey = $this->findFieldKeyByMapping($formTemplate, $resolvedType, 'tax_code');

        $submission = LandingPageSubmission::create([
            'landing_page_id'          => $landingPage->id,
            'campaign_id'              => $campaignId,
            'landing_form_template_id' => $formTemplateId,
            'contact_id'               => $contact->id,
            'data'                     => $payload,
            'normalized_email'         => $normalizedEmail,
            'status'                   => LandingPageSubmissionStatus::Processed,
            'contact_action'           => $action,
            'submission_type'          => $submissionType,
            'business_tax_code'        => $taxCodeKey ? ($validatedData[$taxCodeKey] ?? null) : null,
            'qualification_status'     => 'received',
            'ip_address'               => $request->ip(),
            'user_agent'               => $request->userAgent(),
            'referrer'                 => $request->headers->get('referer'),
            'utm_source'               => $utm['utm_source'] ?? null,
            'utm_medium'               => $utm['utm_medium'] ?? null,
            'utm_campaign'             => $utm['utm_campaign'] ?? null,
            'utm_content'              => $utm['utm_content'] ?? null,
            'utm_term'                 => $utm['utm_term'] ?? null,
            'submitted_at'             => now(),
        ]);

        if ($landingPage->auto_create_segment) {
            $this->autoCreateSegmentIfNeeded($landingPage);
        }

        return $submission;
    }

    public function validatePayload($formTemplate, array $payload, string $submissionType): array
    {
        $rules = [];

        if ($formTemplate && $formTemplate->fields) {
            foreach ($formTemplate->fields as $field) {
                if ($field->field_type->value === 'hidden') continue;
                $fieldRules = [];
                if ($field->is_required) {
                    $fieldRules[] = 'required';
                } else {
                    $fieldRules[] = 'nullable';
                }
                if ($field->field_type->value === 'email') {
                    $fieldRules[] = 'email:rfc';
                }
                if ($field->validation_rules) {
                    foreach (explode('|', $field->validation_rules) as $r) {
                        $fieldRules[] = $r;
                    }
                }
                $rules[$field->field_key] = $fieldRules;
            }
        }

        return $rules ? validator($payload, $rules)->validate() : $payload;
    }

    protected function findDuplicateContact(?string $normalizedEmail, ?string $normalizedPhone, array $validatedData): ?Contact
    {
        if ($normalizedEmail) {
            $profile = PersonalContactProfile::where('email', $normalizedEmail)->first();
            if ($profile) return $profile->contact;

            $profile = BusinessContactProfile::where('business_email', $normalizedEmail)->first();
            if ($profile) return $profile->contact;
        }

        if ($normalizedPhone) {
            $profile = PersonalContactProfile::where('phone', $normalizedPhone)->first();
            if ($profile) return $profile->contact;

            $profile = BusinessContactProfile::where('business_phone', $normalizedPhone)->first();
            if ($profile) return $profile->contact;
        }

        if (isset($validatedData['tax_code']) && filled($validatedData['tax_code'])) {
            $contact = Contact::whereHas('businessProfile', fn ($q) => $q->where('tax_code', $validatedData['tax_code']))->first();
            if ($contact) return $contact;
        }

        return null;
    }

    protected function savePersonalProfile(Contact $contact, array $validatedData, ?string $normalizedEmail, ?string $normalizedPhone, $formTemplate): void
    {
        $profile = $contact->personalProfile;
        $isNew = ! $profile;

        $profileData = [];

        if (filled($normalizedEmail)) $profileData['email'] = $normalizedEmail;
        if (filled($normalizedPhone)) $profileData['phone'] = $normalizedPhone;

        if ($formTemplate && $formTemplate->fields) {
            foreach ($formTemplate->fields as $field) {
                $mapping = $field->contact_mapping;
                if (! $mapping || ! str_starts_with($mapping, 'personal.')) continue;
                $profileField = explode('.', $mapping, 2)[1] ?? null;
                if (! $profileField || ! isset($validatedData[$field->field_key])) continue;
                $value = $validatedData[$field->field_key];
                if ($profileField === 'email') {
                    $profileData['email'] = $normalizedEmail ?? $value;
                    continue;
                }
                if ($profileField === 'phone') {
                    $profileData['phone'] = $value;
                    continue;
                }
                $profileData[$profileField] = $value;
            }
        }

        if (! empty($profileData)) {
            PersonalContactProfile::updateOrCreate(
                ['contact_id' => $contact->id],
                $profileData
            );
        }
    }

    protected function saveBusinessProfile(Contact $contact, array $validatedData, ?string $normalizedEmail, ?string $normalizedPhone, $formTemplate): ?BusinessContactProfile
    {
        $profile = $contact->businessProfile;
        $isNew = ! $profile;

        $profileData = [
            'tax_verification_status' => TaxVerificationStatus::Pending->value,
        ];

        if ($formTemplate && $formTemplate->fields) {
            foreach ($formTemplate->fields as $field) {
                $mapping = $field->contact_mapping;
                if (! $mapping || ! str_starts_with($mapping, 'business.')) continue;
                $profileField = explode('.', $mapping, 2)[1] ?? null;
                if (! $profileField || ! isset($validatedData[$field->field_key])) continue;
                $value = $validatedData[$field->field_key];
                if ($profileField === 'business_email') {
                    $profileData['business_email'] = $normalizedEmail ?? $value;
                    continue;
                }
                if ($profileField === 'business_phone') {
                    $profileData['business_phone'] = $value;
                    continue;
                }
                if ($profileField === 'tax_code') {
                    $profileData['tax_code'] = $value;
                    continue;
                }
                $profileData[$profileField] = $value;
            }
        }

        return BusinessContactProfile::updateOrCreate(
            ['contact_id' => $contact->id],
            $profileData
        );
    }

    public function applyTagsAndLists(Contact $contact, LandingPage $landingPage, $formTemplate, array $validatedData = []): void
    {
        $tagNames = collect($landingPage->auto_tag_names ?? []);
        $listNames = collect($landingPage->auto_list_names ?? []);

        if ($formTemplate) {
            $tagNames = $tagNames->merge($formTemplate->auto_tag_names ?? []);
            $listNames = $listNames->merge($formTemplate->auto_list_names ?? []);
        }

        $tagNames = $tagNames->unique()->filter()->values();
        $listNames = $listNames->unique()->filter()->values();

        if ($tagNames->isNotEmpty()) {
            $tagIds = [];
            foreach ($tagNames as $tagName) {
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($tagName)],
                    ['name' => $tagName]
                );
                $tagIds[] = $tag->id;
            }
            // Tags pivot (contact_tag) was removed — tags now apply at customer level
            // Skipping $contact->tags()->syncWithoutDetaching($tagIds);
        }

        if ($formTemplate && ! empty($validatedData)) {
            $tagIds = [];
            foreach ($formTemplate->fields as $field) {
                if (! $field->tag_from_value) continue;
                $value = $validatedData[$field->field_key] ?? null;
                if (blank($value)) continue;
                $tagValue = is_array($value) ? implode(', ', $value) : (string) $value;
                if (blank($tagValue)) continue;
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($tagValue)],
                    ['name' => $tagValue]
                );
                $tagIds[] = $tag->id;
            }
            if ($tagIds) {
                // Tags pivot (contact_tag) was removed
                // Skipping $contact->tags()->syncWithoutDetaching($tagIds);
            }
        }

        if ($listNames->isNotEmpty()) {
            foreach ($listNames as $listName) {
                $list = ContactList::firstOrCreate(
                    ['slug' => Str::slug($listName)],
                    ['name' => $listName, 'type' => 'regular', 'status' => 'active']
                );

                if ($list) {
                    // List pivot (contact_list_members) was removed — lists now apply at customer level
                    // Skipping $contact->lists()->attach(...)
                }
            }
        }
    }

    public function autoCreateSegmentIfNeeded(LandingPage $landingPage): ?Segment
    {
        $segmentName = 'Lead từ LP - ' . $landingPage->name;
        $segmentSlug = Str::slug($segmentName);

        if (Segment::where('slug', $segmentSlug)->exists()) {
            return null;
        }

        $firstForm = $landingPage->forms()->where('status', 'active')->first()?->formTemplate;
        $allTagNames = array_merge(
            $landingPage->auto_tag_names ?? [],
            $firstForm?->auto_tag_names ?? []
        );
        $tagName = $allTagNames[0] ?? null;
        $tagId = $tagName ? Tag::where('slug', Str::slug($tagName))->value('id') : null;

        $conditions = [];
        if ($tagId) {
            $conditions[] = ['field' => 'has_tag', 'operator' => 'equals', 'value' => $tagId];
        }
        $conditions[] = ['field' => 'consent_status_equals', 'operator' => 'equals', 'value' => 'subscribed'];

        return Segment::create([
            'name' => $segmentName,
            'slug' => $segmentSlug,
            'description' => 'Auto-created from Landing Page: ' . $landingPage->name,
            'rules' => ['conditions' => $conditions],
            'status' => 'active',
        ]);
    }

    protected function saveCustomFields(Contact $contact, $formTemplate, array $validatedData): void
    {
        if (! $formTemplate) return;

        foreach ($formTemplate->fields as $field) {
            $mapping = $field->contact_mapping;
            if (! $mapping || ! str_starts_with($mapping, 'custom_field:')) continue;

            $cfKey = substr($mapping, strlen('custom_field:'));
            $value = $validatedData[$field->field_key] ?? null;

            if ($value === null) continue;

            $customField = CustomField::where('key', $cfKey)->first();
            if (! $customField) continue;

            ContactCustomFieldValue::updateOrCreate(
                ['contact_id' => $contact->id, 'custom_field_id' => $customField->id],
                ['value' => $value]
            );
        }
    }

    protected function findFieldKeyByMapping($formTemplate, string $type, string $field): ?string
    {
        if (! $formTemplate || ! $formTemplate->fields) return null;
        $prefix = $type === 'personal' ? 'personal.' : 'business.';
        foreach ($formTemplate->fields as $f) {
            if ($f->contact_mapping === $prefix . $field) {
                return $f->field_key;
            }
        }
        return null;
    }
}
