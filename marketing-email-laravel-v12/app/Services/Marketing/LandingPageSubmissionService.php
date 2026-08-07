<?php

namespace App\Services\Marketing;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\TaxVerificationStatus;
use App\Enums\Marketing\LandingPageContactAction;
use App\Enums\Marketing\LandingPageSubmissionStatus;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Crm\CompanyMatchCandidate;
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
use App\Services\Crm\CompanyCodeGenerator;
use App\Services\Crm\CompanyContactLinkService;
use App\Services\Crm\CompanyNormalizationService;
use App\Services\Crm\CompanyResolutionService;
use App\Services\Crm\LeadCreationService;
use App\Services\Crm\LeadFormAnswerSnapshotService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LandingPageSubmissionService
{
    public function __construct(
        private readonly LandingPageServiceCatalogService $serviceCatalog,
        private readonly MarketingCampaignServiceScopeService $campaignScope,
    ) {}

    public function handle(
        LandingPage $landingPage,
        array $payload,
        Request $request,
        ?int $campaignId = null,
    ): LandingPageSubmission {
        // Lớp bảo vệ runtime: kể cả dữ liệu bị sửa trực tiếp trong DB hoặc
        // Landing Page cũ chưa được người dùng mở lại trên Filament, submission
        // vẫn không được tạo nếu dịch vụ của Landing Page nằm ngoài scope Campaign.
        $this->campaignScope->assertLandingPageServiceAllowed(
            $landingPage->marketing_campaign_id !== null
                ? (int) $landingPage->marketing_campaign_id
                : null,
            $landingPage->service_id !== null
                ? (int) $landingPage->service_id
                : null,
        );

        $this->validateEnvelope($payload);

        $submissionType = (string) ($payload['submission_type'] ?? 'personal');
        $resolvedType = $submissionType === 'generic'
            ? (string) ($payload['customer_type'] ?? '')
            : $submissionType;

        $landingPageForm = $landingPage->forms()
            ->where('form_type', $submissionType)
            ->where('status', 'active')
            ->first();
        $formTemplate = $landingPageForm?->formTemplate;

        if ($formTemplate === null) {
            throw ValidationException::withMessages([
                'submission_type' => 'Biểu mẫu đang không hoạt động hoặc chưa được gắn vào trang đích.',
            ]);
        }

        $formTemplate->loadMissing('fields');
        $validatedData = $this->validatePayload(
            $formTemplate,
            $payload,
            $resolvedType,
            $landingPage,
        );

        $submissionToken = filled($payload['_submission_token'] ?? null)
            ? (string) $payload['_submission_token']
            : null;

        if ($submissionToken !== null && ! Str::isUuid($submissionToken)) {
            throw ValidationException::withMessages([
                '_submission_token' => 'Mã gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang và thử lại.',
            ]);
        }

        $payloadFingerprint = $this->buildPayloadFingerprint(
            $landingPage,
            $resolvedType,
            $validatedData,
        );
        $lockIdentity = $submissionToken !== null
            ? 'token:'.$submissionToken
            : 'fingerprint:'.$payloadFingerprint;
        $lockKey = 'landing-page-submission:'
            .$landingPage->id.':'
            .hash('sha256', $lockIdentity);

        try {
            return Cache::lock($lockKey, 20)->block(5, function () use (
                $landingPage,
                $formTemplate,
                $validatedData,
                $payload,
                $request,
                $campaignId,
                $resolvedType,
                $submissionToken,
                $payloadFingerprint,
            ): LandingPageSubmission {
                $existing = $this->findExistingSubmission(
                    $landingPage,
                    $submissionToken,
                    $payloadFingerprint,
                );

                if ($existing !== null) {
                    return $existing;
                }

                return DB::transaction(function () use (
                    $landingPage,
                    $formTemplate,
                    $validatedData,
                    $payload,
                    $request,
                    $campaignId,
                    $resolvedType,
                    $submissionToken,
                    $payloadFingerprint,
                ): LandingPageSubmission {
                    // Kiểm tra lại trong transaction để bảo vệ khi cache lock
                    // không dùng chung giữa nhiều application instance.
                    $existing = $this->findExistingSubmission(
                        $landingPage,
                        $submissionToken,
                        $payloadFingerprint,
                    );

                    if ($existing !== null) {
                        return $existing;
                    }

                    $emailField = $resolvedType === 'business'
                        ? 'business_email'
                        : 'email';
                    $phoneField = $resolvedType === 'business'
                        ? 'business_phone'
                        : 'phone';
                    $emailKey = $this->findFieldKeyByMapping(
                        $formTemplate,
                        $resolvedType,
                        $emailField,
                    );
                    $phoneKey = $this->findFieldKeyByMapping(
                        $formTemplate,
                        $resolvedType,
                        $phoneField,
                    );
                    $normalizedEmail = $emailKey !== null
                        && filled($validatedData[$emailKey] ?? null)
                        ? strtolower(trim((string) $validatedData[$emailKey]))
                        : null;
                    $normalizedPhone = $phoneKey !== null
                        && filled($validatedData[$phoneKey] ?? null)
                        ? preg_replace(
                            '/[^0-9]/',
                            '',
                            (string) $validatedData[$phoneKey]
                        )
                        : null;

                    $contact = $this->findDuplicateContact(
                        $normalizedEmail,
                        $normalizedPhone,
                        $validatedData,
                    );

                    if ($contact === null) {
                        $contact = Contact::create([
                            'contact_type' => $resolvedType,
                        ]);
                        $action = LandingPageContactAction::Created;
                    } else {
                        $action = LandingPageContactAction::Updated;
                    }

                    $profile = null;

                    if ($resolvedType === 'personal') {
                        $this->savePersonalProfile(
                            $contact,
                            $validatedData,
                            $normalizedEmail,
                            $normalizedPhone,
                            $formTemplate,
                        );
                    } elseif ($resolvedType === 'business') {
                        $profile = $this->saveBusinessProfile(
                            $contact,
                            $validatedData,
                            $normalizedEmail,
                            $normalizedPhone,
                            $formTemplate,
                        );
                    }

                    $this->applyTagsAndLists(
                        $contact,
                        $landingPage,
                        $formTemplate,
                        $validatedData,
                    );
                    $this->saveCustomFields(
                        $contact,
                        $formTemplate,
                        $validatedData,
                    );

                    $taxCodeKey = $this->findFieldKeyByMapping(
                        $formTemplate,
                        $resolvedType,
                        'tax_code',
                    );
                    $utm = app(LandingPageTrackingService::class)
                        ->extractUtm($request);

                    $submission = LandingPageSubmission::create([
                        'landing_page_id' => $landingPage->id,
                        'campaign_id' => $campaignId,
                        'marketing_campaign_id' => $landingPage->marketing_campaign_id,
                        'landing_form_template_id' => $formTemplate->id,
                        'submission_token' => $submissionToken,
                        'payload_fingerprint' => $payloadFingerprint,
                        'contact_id' => $contact->id,
                        'data' => array_merge(
                            $validatedData,
                            [
                                'submission_type' => $resolvedType,
                                'customer_type' => $payload['customer_type'] ?? null,
                            ],
                        ),
                        'normalized_email' => $normalizedEmail,
                        'status' => LandingPageSubmissionStatus::Processed,
                        'contact_action' => $action,
                        'submission_type' => $resolvedType,
                        'business_tax_code' => $taxCodeKey !== null
                            ? ($validatedData[$taxCodeKey] ?? null)
                            : null,
                        'qualification_status' => 'received',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'referrer' => $request->headers->get('referer'),
                        'utm_source' => $utm['utm_source'] ?? null,
                        'utm_medium' => $utm['utm_medium'] ?? null,
                        'utm_campaign' => $utm['utm_campaign'] ?? null,
                        'utm_content' => $utm['utm_content'] ?? null,
                        'utm_term' => $utm['utm_term'] ?? null,
                        'submitted_at' => now(),
                    ]);

                    $company = null;

                    if ($resolvedType === 'business' && $profile !== null) {
                        $company = $this->resolveCompany(
                            $contact,
                            $profile,
                            $validatedData,
                            $formTemplate,
                            $submission,
                        );
                    }

                    if (config('business_flow.v2_enabled')) {
                        $submission->refresh();
                        $serviceInterest = $this->resolveServiceInterest(
                            $formTemplate,
                            $resolvedType,
                            $validatedData,
                        );
                        $serviceInterestField = $this->resolveServiceInterestField(
                            $formTemplate,
                            $resolvedType,
                        );
                        $serviceOptions = $landingPage->service_id !== null
                            ? $this->serviceCatalog->options(
                                $landingPage,
                                $resolvedType,
                            )
                            : [];
                        $optionOverrides = $serviceInterestField !== null
                            && $serviceOptions !== []
                            ? [
                                $serviceInterestField->field_key => $serviceOptions,
                            ]
                            : [];
                        $formAnswers = app(
                            LeadFormAnswerSnapshotService::class
                        )->build(
                            $formTemplate,
                            $validatedData,
                            $optionOverrides,
                        );
                        $serviceContext = $landingPage->service_id !== null
                            ? $this->serviceCatalog->selectionContext(
                                $landingPage,
                                $serviceInterest,
                                $resolvedType,
                            )
                            : [];

                        app(LeadCreationService::class)->createFromSubmission(
                            submission: $submission->loadMissing(
                                'landingPage.marketingCampaign'
                            ),
                            companyId: $company?->id ?? $submission->company_id,
                            serviceInterest: $serviceInterest,
                            formAnswers: $formAnswers,
                            serviceContext: $serviceContext,
                            userId: auth()->id(),
                        );
                    } else {
                        ContactQualification::query()->firstOrCreate(
                            ['contact_id' => $contact->id],
                            ['status' => ContactQualificationStatus::New->value]
                        );
                    }

                    if ($landingPage->auto_create_segment) {
                        $this->autoCreateSegmentIfNeeded($landingPage);
                    }

                    return $submission;
                });
            });
        } catch (LockTimeoutException) {
            $existing = $this->findExistingSubmission(
                $landingPage,
                $submissionToken,
                $payloadFingerprint,
            );

            if ($existing !== null) {
                return $existing;
            }

            throw ValidationException::withMessages([
                '_submission_token' => 'Yêu cầu đang được xử lý. Vui lòng chờ vài giây rồi thử lại.',
            ]);
        } catch (QueryException $exception) {
            // Nếu hai request đồng thời vượt qua cache lock trên hai node khác
            // nhau, unique index của submission token là lớp bảo vệ cuối cùng.
            $existing = $this->findExistingSubmission(
                $landingPage,
                $submissionToken,
                $payloadFingerprint,
            );

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    public function validatePayload(
        mixed $formTemplate,
        array $payload,
        string $submissionType,
        ?LandingPage $landingPage = null,
    ): array {
        $rules = [];
        $attributes = [];
        $hiddenValues = [];

        if ($formTemplate && $formTemplate->fields) {
            foreach ($formTemplate->fields as $field) {
                $type = $field->field_type?->value
                    ?? (string) $field->field_type;
                $attributes[$field->field_key] = $field->label;

                if ($type === 'hidden') {
                    $hiddenValues[$field->field_key] = $field->default_value;

                    continue;
                }

                $mapping = (string) ($field->contact_mapping ?? '');
                $isServiceInterest = in_array($mapping, [
                    'lead.service_interest',
                    'personal.service_interest',
                    'business.service_interest',
                ], true);
                $fieldRules = [
                    ($field->is_required || $isServiceInterest)
                        ? 'required'
                        : 'nullable',
                ];

                if ($type === 'email') {
                    $fieldRules[] = 'email:rfc';
                }

                if ($type === 'number') {
                    $fieldRules[] = 'numeric';
                }

                if ($type === 'date') {
                    $fieldRules[] = 'date';
                }

                $effectiveOptions = $field->options ?? [];

                if (
                    $isServiceInterest
                    && $landingPage?->service_id !== null
                ) {
                    $effectiveOptions = $this->serviceCatalog->options(
                        $landingPage,
                        $submissionType,
                    );
                }

                $allowedValues = $this->allowedOptionValues(
                    $effectiveOptions
                );

                if (
                    in_array($type, ['select', 'radio'], true)
                    || $isServiceInterest
                ) {
                    $fieldRules[] = Rule::in($allowedValues);
                }

                if (
                    $type === 'multi_select'
                    || ($type === 'checkbox' && $allowedValues !== [])
                ) {
                    $fieldRules[] = 'array';
                    $rules[$field->field_key.'.*'] = [
                        Rule::in($allowedValues),
                    ];
                }

                if (
                    $type === 'checkbox'
                    && $allowedValues === []
                    && $field->is_required
                ) {
                    $fieldRules[] = 'accepted';
                }

                if (filled($field->validation_rules)) {
                    foreach (
                        explode('|', (string) $field->validation_rules)
                        as $rule
                    ) {
                        $rule = trim($rule);

                        if ($rule !== '') {
                            $fieldRules[] = $rule;
                        }
                    }
                }

                $rules[$field->field_key] = $fieldRules;
            }
        }

        $validated = $rules === []
            ? []
            : validator(
                $payload,
                $rules,
                [
                    '*.required' => 'Vui lòng nhập :attribute.',
                    '*.in' => 'Giá trị của :attribute không hợp lệ.',
                    '*.array' => ':attribute phải là danh sách hợp lệ.',
                    '*.email' => ':attribute không đúng định dạng email.',
                    '*.numeric' => ':attribute phải là số.',
                    '*.date' => ':attribute không đúng định dạng ngày.',
                    '*.accepted' => 'Bạn phải xác nhận :attribute.',
                ],
                $attributes,
            )->validate();

        return array_merge($validated, $hiddenValues);
    }

    private function validateEnvelope(array $payload): void
    {
        validator($payload, [
            'submission_type' => [
                'nullable',
                Rule::in(['personal', 'business', 'generic']),
            ],
            'customer_type' => [
                'nullable',
                Rule::in(['personal', 'business']),
            ],
        ])->validate();

        if (
            ($payload['submission_type'] ?? null) === 'generic'
            && blank($payload['customer_type'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'customer_type' => 'Vui lòng chọn loại khách hàng.',
            ]);
        }
    }

    private function findExistingSubmission(
        LandingPage $landingPage,
        ?string $submissionToken,
        string $payloadFingerprint,
    ): ?LandingPageSubmission {
        if ($submissionToken !== null) {
            $byToken = LandingPageSubmission::query()
                ->where('landing_page_id', $landingPage->id)
                ->where('submission_token', $submissionToken)
                ->first();

            if ($byToken !== null) {
                if (
                    filled($byToken->payload_fingerprint)
                    && $byToken->payload_fingerprint !== $payloadFingerprint
                ) {
                    throw ValidationException::withMessages([
                        '_submission_token' => 'Mã gửi đã được sử dụng cho dữ liệu khác. Vui lòng tải lại trang.',
                    ]);
                }

                return $byToken;
            }
        }

        return LandingPageSubmission::query()
            ->where('landing_page_id', $landingPage->id)
            ->where('payload_fingerprint', $payloadFingerprint)
            ->where('created_at', '>=', now()->subSeconds(60))
            ->first();
    }

    /** @return array<int, string> */
    private function allowedOptionValues(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        if (! array_is_list($options)) {
            return array_map('strval', array_keys($options));
        }

        return collect($options)
            ->map(function (mixed $option): ?string {
                if (is_array($option)) {
                    $value = $option['value'] ?? $option['key'] ?? null;

                    return $value === null ? null : (string) $value;
                }

                return (string) $option;
            })
            ->filter(fn (?string $value): bool => filled($value))
            ->values()
            ->all();
    }

    private function buildPayloadFingerprint(
        LandingPage $landingPage,
        string $resolvedType,
        array $validatedData,
    ): string {
        $normalized = $this->sortRecursively([
            'landing_page_id' => $landingPage->id,
            'submission_type' => $resolvedType,
            'answers' => $validatedData,
        ]);

        return hash(
            'sha256',
            json_encode(
                $normalized,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ) ?: ''
        );
    }

    private function sortRecursively(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursively($item);
            }
        }

        if (array_is_list($value)) {
            if (collect($value)->every(fn (mixed $item): bool => is_scalar($item))) {
                sort($value, SORT_STRING);
            }

            return $value;
        }

        ksort($value);

        return $value;
    }

    protected function findDuplicateContact(?string $normalizedEmail, ?string $normalizedPhone, array $validatedData): ?Contact
    {
        if ($normalizedEmail) {
            $profile = PersonalContactProfile::where('email', $normalizedEmail)->first();
            if ($profile) {
                return $profile->contact;
            }

            $profile = BusinessContactProfile::where('business_email', $normalizedEmail)->first();
            if ($profile) {
                return $profile->contact;
            }
        }

        if ($normalizedPhone) {
            $profile = PersonalContactProfile::where('phone', $normalizedPhone)->first();
            if ($profile) {
                return $profile->contact;
            }

            $profile = BusinessContactProfile::where('business_phone', $normalizedPhone)->first();
            if ($profile) {
                return $profile->contact;
            }
        }

        return null;
    }

    protected function savePersonalProfile(Contact $contact, array $validatedData, ?string $normalizedEmail, ?string $normalizedPhone, $formTemplate): void
    {
        $profile = $contact->personalProfile;
        $isNew = ! $profile;

        $profileData = [];

        if (filled($normalizedEmail)) {
            $profileData['email'] = $normalizedEmail;
        }
        if (filled($normalizedPhone)) {
            $profileData['phone'] = $normalizedPhone;
        }

        if ($formTemplate && $formTemplate->fields) {
            foreach ($formTemplate->fields as $field) {
                $mapping = $field->contact_mapping;
                if (! $mapping || ! str_starts_with($mapping, 'personal.')) {
                    continue;
                }
                $profileField = explode('.', $mapping, 2)[1] ?? null;
                if ($profileField === 'service_interest') {
                    continue;
                }
                if (! $profileField || ! isset($validatedData[$field->field_key])) {
                    continue;
                }
                $value = $validatedData[$field->field_key];
                if ($profileField === 'email') {
                    $profileData['email'] = $normalizedEmail ?? $value;

                    continue;
                }
                if ($profileField === 'phone') {
                    $profileData['phone'] = $normalizedPhone ?? $value;

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
                if (! $mapping || ! str_starts_with($mapping, 'business.')) {
                    continue;
                }
                $profileField = explode('.', $mapping, 2)[1] ?? null;
                if ($profileField === 'service_interest') {
                    continue;
                }
                if (! $profileField || ! isset($validatedData[$field->field_key])) {
                    continue;
                }
                $value = $validatedData[$field->field_key];
                if ($profileField === 'business_email') {
                    $profileData['business_email'] = $normalizedEmail ?? $value;

                    continue;
                }
                if ($profileField === 'business_phone') {
                    $profileData['business_phone'] = $normalizedPhone ?? $value;

                    continue;
                }
                if ($profileField === 'tax_code') {
                    $profileData['tax_code'] = app(
                        CompanyNormalizationService::class
                    )->normalizeTaxCode($value);

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

    protected function resolveCompany(
        Contact $contact,
        ?BusinessContactProfile $profile,
        array $validatedData,
        $formTemplate,
        LandingPageSubmission $submission,
    ): ?Company {
        if (
            ! config('business_flow.v2_enabled')
            || ! config('business_flow.company_resolution_enabled')
        ) {
            return null;
        }

        $taxCodeKey = $this->findFieldKeyByMapping(
            $formTemplate,
            'business',
            'tax_code'
        );

        $nameKey = $this->findFieldKeyByMapping(
            $formTemplate,
            'business',
            'company_name'
        );

        $emailKey = $this->findFieldKeyByMapping(
            $formTemplate,
            'business',
            'business_email'
        );

        $companyName = $nameKey
            ? ($validatedData[$nameKey] ?? null)
            : null;

        $businessEmail = $emailKey
            ? ($validatedData[$emailKey] ?? null)
            : null;

        $taxCode = $taxCodeKey
            ? ($validatedData[$taxCodeKey] ?? null)
            : null;

        if (blank($taxCode) && blank($businessEmail) && blank($companyName)) {
            return null;
        }

        $result = app(CompanyResolutionService::class)->resolve([
            'tax_code' => $taxCode,
            'business_email' => $businessEmail,
            'company_name' => $companyName,
        ]);

        if ($result->isAutoMatch()) {
            $company = $result->company;
        } elseif ($result->found()) {
            CompanyMatchCandidate::query()->updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'contact_id' => $contact->id,
                    'suggested_company_id' => $result->company->id,
                ],
                [
                    'confidence_score' => $result->confidenceScore,
                    'matched_by' => $result->matchedBy,
                    'status' => 'pending',
                    'evidence' => [
                        'submitted_company_name' => $companyName,
                        'submitted_tax_code' => app(
                            CompanyNormalizationService::class
                        )->normalizeTaxCode($taxCode),
                        'submitted_business_email' => $businessEmail,
                        'suggested_company_name' => $result->company->legal_name,
                        'suggested_company_tax_code' => $result->company->tax_code,
                        'suggested_company_domain' => $result->company->email_domain,
                    ],
                    'reviewed_by_user_id' => null,
                    'reviewed_at' => null,
                ]
            );

            return null;
        } else {
            $company = $this->createCompanyFromSubmission(
                $submission,
                $validatedData,
                $formTemplate
            );
        }

        if ($company === null) {
            return null;
        }

        app(CompanyContactLinkService::class)->link(
            company: $company,
            contact: $contact,
            profile: $profile,
            submission: $submission,
            jobTitle: $profile?->contact_position,
        );

        return $company;
    }

    protected function createCompanyFromSubmission(
        LandingPageSubmission $submission,
        array $validatedData,
        $formTemplate,
    ): ?Company {
        $taxCodeKey = $this->findFieldKeyByMapping(
            $formTemplate,
            'business',
            'tax_code'
        );

        $nameKey = $this->findFieldKeyByMapping(
            $formTemplate,
            'business',
            'company_name'
        );

        $emailKey = $this->findFieldKeyByMapping(
            $formTemplate,
            'business',
            'business_email'
        );

        $addressKey = $this->findFieldKeyByMapping(
            $formTemplate,
            'business',
            'company_address'
        );

        $companyName = $nameKey
            ? ($validatedData[$nameKey] ?? null)
            : null;

        if (blank($companyName)) {
            return null;
        }

        $normalizer = app(CompanyNormalizationService::class);
        $businessEmail = $emailKey
            ? ($validatedData[$emailKey] ?? null)
            : null;
        $taxCode = $taxCodeKey
            ? ($validatedData[$taxCodeKey] ?? null)
            : null;

        return Company::query()->create([
            'company_code' => app(CompanyCodeGenerator::class)->next(),
            'legal_name' => trim((string) $companyName),
            'normalized_name' => $normalizer->normalizeName($companyName),
            'tax_code' => $normalizer->normalizeTaxCode($taxCode),
            'email_domain' => $normalizer->extractBusinessDomain(
                $businessEmail
            ),
            'address' => $addressKey
                ? ($validatedData[$addressKey] ?? null)
                : null,
            'lifecycle_stage' => 'prospect',
            'created_from_submission_id' => $submission->id,
        ]);
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
                if (! $field->tag_from_value) {
                    continue;
                }
                $value = $validatedData[$field->field_key] ?? null;
                if (blank($value)) {
                    continue;
                }
                $tagValue = is_array($value) ? implode(', ', $value) : (string) $value;
                if (blank($tagValue)) {
                    continue;
                }
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
        $segmentName = 'Lead từ LP - '.$landingPage->name;
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
            'description' => 'Auto-created from Landing Page: '.$landingPage->name,
            'rules' => ['conditions' => $conditions],
            'status' => 'active',
        ]);
    }

    protected function saveCustomFields(Contact $contact, $formTemplate, array $validatedData): void
    {
        if (! $formTemplate) {
            return;
        }

        foreach ($formTemplate->fields as $field) {
            $mapping = $field->contact_mapping;
            if (! $mapping || ! str_starts_with($mapping, 'custom_field:')) {
                continue;
            }

            $cfKey = substr($mapping, strlen('custom_field:'));
            $value = $validatedData[$field->field_key] ?? null;

            if ($value === null) {
                continue;
            }

            $customField = CustomField::where('key', $cfKey)->first();
            if (! $customField) {
                continue;
            }

            ContactCustomFieldValue::updateOrCreate(
                [
                    'contact_id' => $contact->id,
                    'custom_field_id' => $customField->id,
                ],
                $this->customFieldValuePayload($customField, $value)
            );
        }
    }

    /** @return array<string, mixed> */
    private function customFieldValuePayload(
        CustomField $customField,
        mixed $value,
    ): array {
        $payload = [
            'value_text' => null,
            'value_number' => null,
            'value_date' => null,
            'value_boolean' => null,
            'value_json' => null,
        ];

        switch ($customField->type) {
            case 'number':
                $payload['value_number'] = $value;
                break;

            case 'date':
                $payload['value_date'] = $value;
                break;

            case 'boolean':
                $payload['value_boolean'] = filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );
                break;

            case 'multi_select':
                $payload['value_json'] = is_array($value)
                    ? array_values($value)
                    : [$value];
                break;

            default:
                if (is_array($value)) {
                    $payload['value_json'] = array_values($value);
                } else {
                    $payload['value_text'] = (string) $value;
                }
                break;
        }

        return $payload;
    }

    protected function findFieldKeyByMapping($formTemplate, string $type, string $field): ?string
    {
        if (! $formTemplate || ! $formTemplate->fields) {
            return null;
        }
        $prefix = $type === 'personal' ? 'personal.' : 'business.';
        foreach ($formTemplate->fields as $f) {
            if ($f->contact_mapping === $prefix.$field) {
                return $f->field_key;
            }
        }

        return null;
    }

    protected function resolveServiceInterest(
        mixed $formTemplate,
        string $resolvedType,
        array $validatedData,
    ): ?string {
        $field = $this->resolveServiceInterestField(
            $formTemplate,
            $resolvedType,
        );

        if ($field === null) {
            return null;
        }

        $value = $validatedData[$field->field_key] ?? null;

        if (is_array($value)) {
            $value = implode(', ', array_filter($value));
        }

        return filled($value) ? trim((string) $value) : null;
    }

    protected function resolveServiceInterestField(
        mixed $formTemplate,
        string $resolvedType,
    ): mixed {
        if (! $formTemplate || ! $formTemplate->fields) {
            return null;
        }

        $field = collect($formTemplate->fields)->first(
            fn ($field): bool => $field->contact_mapping
                === 'lead.service_interest'
        );

        if ($field !== null) {
            return $field;
        }

        $legacyMapping = $resolvedType === 'personal'
            ? 'personal.service_interest'
            : 'business.service_interest';

        return collect($formTemplate->fields)->first(
            fn ($field): bool => $field->contact_mapping
                === $legacyMapping
        );
    }

}
