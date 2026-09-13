<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Contracts\AudienceProvider;
use Dth\Marketing\Contracts\LeadProvider;
use Dth\Marketing\DTO\AudienceContactData;
use Dth\Marketing\DTO\AudienceContactReference;
use Dth\Marketing\DTO\LeadIntakeData;
use Dth\Marketing\DTO\LeadReference;
use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Enums\LandingPageContactAction;
use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Enums\LandingPageSubmissionStatus;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\LandingPageSubmission;
use Dth\Marketing\Support\UiText;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

final class LandingPageSubmissionService
{
    public function __construct(
        private readonly AudienceProvider $audienceProvider,
        private readonly LeadProvider $leadProvider,
        private readonly LandingPageTrackingService $tracking,
        private readonly AudienceAutomationService $automation,
        private readonly SubmissionSemanticNormalizer $semanticNormalizer,
        private readonly MarketingAuditTrailService $audit,
    ) {}

    public function handle(
        LandingPage $landingPage,
        array $payload,
        Request $request,
    ): LandingPageSubmission {
        if ($landingPage->status !== LandingPageStatus::Published) {
            abort(404);
        }

        $this->assertPayloadSize($payload);

        $landingPage->loadMissing([
            'marketingCampaign',
            'personalFormTemplate.fields',
            'businessFormTemplate.fields',
        ]);

        $submissionType = $this->validateSubmissionType($payload);
        $formTemplate = $submissionType === 'business'
            ? $landingPage->businessFormTemplate
            : $landingPage->personalFormTemplate;

        if ($formTemplate === null || $formTemplate->status !== FormTemplateStatus::Active) {
            throw ValidationException::withMessages([
                'submission_type' => UiText::get('submission.form_not_active', 'The selected form is not active on this Landing Page.'),
            ]);
        }

        $validatedData = $this->validatePayload($formTemplate, $payload);
        $submissionToken = $this->submissionToken($payload);
        $fingerprint = $this->payloadFingerprint(
            $landingPage,
            $submissionType,
            $validatedData,
        );
        $identity = $submissionToken !== null
            ? 'token:'.$submissionToken
            : 'fingerprint:'.$fingerprint;
        $lockKey = 'dth-marketing:submission:'
            .$landingPage->getKey().':'
            .hash('sha256', $identity);

        try {
            return Cache::lock($lockKey, 20)->block(5, function () use (
                $landingPage,
                $formTemplate,
                $submissionType,
                $validatedData,
                $submissionToken,
                $fingerprint,
                $payload,
                $request,
            ): LandingPageSubmission {
                $existing = $this->findExisting(
                    $landingPage,
                    $submissionToken,
                    $fingerprint,
                );
                if ($existing !== null) {
                    return $existing;
                }

                $submission = $this->persistReceived(
                    $landingPage,
                    $formTemplate,
                    $submissionType,
                    $validatedData,
                    $submissionToken,
                    $fingerprint,
                    $payload,
                    $request,
                );

                if (filled($payload['_dth_website'] ?? null)) {
                    $submission->forceFill([
                        'status' => LandingPageSubmissionStatus::Spam,
                        'processed_at' => now(),
                        'failure_reason' => UiText::get('submission.spam_honeypot_reason', 'Honeypot field was populated.'),
                    ])->save();

                    $this->audit->log('submission.spam_detected', $submission, metadata: [
                        'reason' => 'honeypot',
                        'landing_page_id' => $landingPage->getKey(),
                    ]);

                    return $submission->refresh();
                }

                return $this->process($submission, $landingPage, $formTemplate);
            });
        } catch (LockTimeoutException) {
            $existing = $this->findExisting(
                $landingPage,
                $submissionToken,
                $fingerprint,
            );

            if ($existing !== null) {
                return $existing;
            }

            throw ValidationException::withMessages([
                '_submission_token' => UiText::get('submission.processing_duplicate', 'This submission is already being processed. Please wait a few seconds and try again.'),
            ]);
        } catch (QueryException $exception) {
            $existing = $this->findExisting(
                $landingPage,
                $submissionToken,
                $fingerprint,
            );

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    public function retry(LandingPageSubmission $submission): LandingPageSubmission
    {
        $this->audit->log('submission.retry_requested', $submission, metadata: [
            'status' => $submission->status instanceof \BackedEnum ? $submission->status->value : (string) $submission->status,
        ]);

        $submission->loadMissing([
            'landingPage.marketingCampaign',
            'formTemplate.fields',
        ]);

        $page = $submission->landingPage;
        $template = $submission->formTemplate;
        if ($page === null || $template === null) {
            throw ValidationException::withMessages([
                'submission' => UiText::get('submission.original_missing', 'The original Landing Page or Form Template no longer exists.'),
            ]);
        }

        return Cache::lock('dth-marketing:submission-retry:'.$submission->getKey(), 30)
            ->block(5, fn (): LandingPageSubmission => $this->process($submission, $page, $template));
    }

    public function markSpamByAdmin(LandingPageSubmission $submission, ?string $reason = null): LandingPageSubmission
    {
        $oldStatus = $submission->status instanceof \BackedEnum
            ? (string) $submission->status->value
            : (string) $submission->status;

        $submission->forceFill([
            'status' => LandingPageSubmissionStatus::Spam,
            'failure_reason' => $reason ?: UiText::get(
                'submission.spam_admin_reason',
                'Marked as spam by an administrator.',
            ),
            'processed_at' => $submission->processed_at ?? now(),
        ])->save();

        $this->audit->log(
            'submission.marked_spam',
            $submission,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => LandingPageSubmissionStatus::Spam->value],
        );

        return $submission->refresh();
    }

    /** @return array<string, mixed> */
    public function validatePayload(FormTemplate $template, array $payload): array
    {
        $rules = [];
        $attributes = [];
        $hidden = [];

        foreach ($template->fields as $field) {
            $key = (string) $field->field_key;
            $type = $field->field_type instanceof FormFieldType
                ? $field->field_type
                : (FormFieldType::tryFrom((string) $field->field_type) ?? FormFieldType::Text);

            $attributes[$key] = (string) $field->label;

            if ($type === FormFieldType::Hidden) {
                $hidden[$key] = $field->default_value;
                continue;
            }

            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            if ($type === FormFieldType::Email) {
                $fieldRules[] = 'email:rfc';
            }

            if ($type === FormFieldType::Select) {
                $allowed = $this->allowedOptionValues((array) ($field->options ?? []));
                if ($allowed !== []) {
                    $fieldRules[] = Rule::in($allowed);
                }
            }

            if ($type === FormFieldType::Checkbox && $field->is_required) {
                $fieldRules[] = 'accepted';
            }

            foreach ($this->additionalRules((string) ($field->validation_rules ?? '')) as $rule) {
                $fieldRules[] = $rule;
            }

            $rules[$key] = $fieldRules;
        }

        $validated = $rules === []
            ? []
            : validator(
                $payload,
                $rules,
                [
                    '*.required' => UiText::get('submission.validation.required', 'Please enter :attribute.'),
                    '*.email' => UiText::get('submission.validation.email', ':attribute must be a valid email address.'),
                    '*.in' => UiText::get('submission.validation.in', 'The selected :attribute is invalid.'),
                    '*.accepted' => UiText::get('submission.validation.accepted', 'Please accept :attribute.'),
                ],
                $attributes,
            )->validate();

        return array_merge($validated, $hidden);
    }

    private function persistReceived(
        LandingPage $page,
        FormTemplate $template,
        string $submissionType,
        array $validatedData,
        ?string $submissionToken,
        string $fingerprint,
        array $rawPayload,
        Request $request,
    ): LandingPageSubmission {
        $normalized = $this->semanticNormalizer->normalize($template, $validatedData);
        $summary = $this->semanticNormalizer->summary($normalized);
        $mapped = $this->semanticNormalizer->legacyMappedValues($template, $validatedData, $normalized);
        $email = $summary['email'] ?? $this->normalizeEmail(
            $this->firstValueByType($template, $validatedData, FormFieldType::Email),
        );
        $phone = $summary['phone'] ?? $this->normalizePhone(
            $this->firstValueByType($template, $validatedData, FormFieldType::Phone),
        );
        $displayName = $summary['display_name'];
        $companyName = $summary['company_name'];
        $memberKey = $this->memberKey($submissionType, $email, $phone, $fingerprint);
        $utm = $this->tracking->extractUtm($request);
        $serviceReference = filled($page->service_reference)
            ? (string) $page->service_reference
            : $summary['service_interest'];
        $packageReference = $this->resolvePackageReference($page, $mapped);

        $submission = DB::transaction(fn (): LandingPageSubmission => LandingPageSubmission::query()->create([
            'landing_page_id' => $page->getKey(),
            'marketing_campaign_id' => $page->marketing_campaign_id,
            'form_template_id' => $template->getKey(),
            'submission_token' => $submissionToken,
            'payload_fingerprint' => $fingerprint,
            'member_key' => $memberKey,
            'submission_type' => $submissionType,
            'data' => $validatedData,
            'normalized_data' => $normalized,
            'normalized_email' => $email,
            'normalized_phone' => $phone,
            'display_name' => $displayName,
            'company_name' => $companyName,
            'service_reference' => $serviceReference,
            'package_reference' => $packageReference,
            'status' => LandingPageSubmissionStatus::Received,
            'contact_action' => LandingPageContactAction::Skipped,
            'source' => $this->tracking->acquisitionSource($page, $request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->headers->get('referer'),
            ...$utm,
            'submitted_at' => now(),
            'integration_snapshot' => [
                'audience_provider_available' => $this->audienceProvider->available(),
                'lead_provider_available' => $this->leadProvider->available(),
                'raw_envelope' => [
                    'submission_type' => $rawPayload['submission_type'] ?? null,
                ],
            ],
        ]));

        $this->audit->log('submission.received', $submission, metadata: [
            'landing_page_id' => $page->getKey(),
            'marketing_campaign_id' => $page->marketing_campaign_id,
            'submission_type' => $submissionType,
            'source' => $submission->source,
            'utm_source' => $submission->utm_source,
        ]);

        return $submission;
    }

    private function process(
        LandingPageSubmission $submission,
        LandingPage $page,
        FormTemplate $template,
    ): LandingPageSubmission {
        try {
            $validatedData = (array) ($submission->data ?? []);
            $normalized = (array) ($submission->normalized_data ?? []);
            if ($normalized === []) {
                $normalized = $this->semanticNormalizer->normalize($template, $validatedData);
                $submission->normalized_data = $normalized;
                $submission->saveQuietly();
            }
            $mapped = $this->semanticNormalizer->legacyMappedValues($template, $validatedData, $normalized);
            $contactReference = $this->upsertAudienceContact(
                $submission,
                $mapped,
                $validatedData,
            );

            if ($contactReference !== null) {
                $submission->contact_reference = $contactReference->reference;
                $submission->contact_action = $this->contactAction($contactReference);
                $submission->saveQuietly();
            }

            $leadReference = $this->upsertLead($submission, $page, $template, $mapped);
            if ($leadReference !== null) {
                $submission->lead_reference = $leadReference->reference;
                $submission->lead_code = $leadReference->code;
                $submission->lead_status = $leadReference->status;
                $submission->saveQuietly();
            }

            $this->automation->apply($submission, $page, $template);

            $snapshot = (array) ($submission->integration_snapshot ?? []);
            $snapshot['audience_provider'] = [
                'available' => $this->audienceProvider->available(),
                'capabilities' => $this->audienceProvider->capabilities(),
                'contact_reference' => $submission->contact_reference,
            ];
            $snapshot['lead_provider'] = [
                'available' => $this->leadProvider->available(),
                'capabilities' => $this->leadProvider->capabilities(),
                'lead_reference' => $submission->lead_reference,
            ];

            $submission->forceFill([
                'status' => LandingPageSubmissionStatus::Processed,
                'failure_reason' => null,
                'processed_at' => now(),
                'integration_snapshot' => $snapshot,
            ])->save();

            $this->audit->log('submission.processed', $submission, newValues: [
                'status' => LandingPageSubmissionStatus::Processed->value,
                'contact_action' => $submission->contact_action instanceof \BackedEnum
                    ? $submission->contact_action->value
                    : $submission->contact_action,
                'has_contact_reference' => filled($submission->contact_reference),
                'has_lead_reference' => filled($submission->lead_reference),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $submission->forceFill([
                'status' => LandingPageSubmissionStatus::Failed,
                'failure_reason' => mb_substr($exception->getMessage(), 0, 4000),
                'processed_at' => now(),
            ])->save();

            $this->audit->log('submission.failed', $submission, newValues: [
                'status' => LandingPageSubmissionStatus::Failed->value,
            ], metadata: [
                'exception' => $exception::class,
            ]);
        }

        return $submission->refresh();
    }

    private function upsertAudienceContact(
        LandingPageSubmission $submission,
        array $mapped,
        array $validatedData,
    ): ?AudienceContactReference {
        if (! $this->audienceProvider->available()) {
            return null;
        }

        return $this->audienceProvider->upsertContact(new AudienceContactData(
            email: $submission->normalized_email,
            phone: $submission->normalized_phone,
            type: (string) $submission->submission_type,
            attributes: [
                'name' => $mapped['lead.name'] ?? null,
                'company_name' => $mapped['lead.company_name'] ?? null,
                'tax_code' => $mapped['lead.tax_code'] ?? null,
                'position' => $mapped['lead.position'] ?? null,
                'service_interest' => $submission->service_reference,
                'answers' => $validatedData,
            ],
            metadata: $this->attribution($submission),
        ));
    }

    private function upsertLead(
        LandingPageSubmission $submission,
        LandingPage $page,
        FormTemplate $template,
        array $mapped,
    ): ?LeadReference {
        if (! $this->leadProvider->available()) {
            return null;
        }

        $serviceLabel = data_get($page->catalog_snapshot, 'service.name');

        return $this->leadProvider->createOrUpdateFromMarketing(new LeadIntakeData(
            submissionReference: (string) $submission->getKey(),
            contactReference: $submission->contact_reference,
            companyReference: null,
            serviceReference: $submission->service_reference,
            serviceLabel: is_scalar($serviceLabel) ? (string) $serviceLabel : null,
            marketingCampaignReference: $page->marketing_campaign_id !== null
                ? (string) $page->marketing_campaign_id
                : null,
            landingPageReference: (string) $page->getKey(),
            formAnswers: $this->formAnswers($template, (array) $submission->data),
            attribution: $this->attribution($submission),
            serviceContext: [
                'service_reference' => $submission->service_reference,
                'package_reference' => $submission->package_reference,
                'catalog_snapshot' => $page->catalog_snapshot,
            ],
            metadata: [
                'submission_type' => $submission->submission_type,
                'display_name' => $submission->display_name,
                'company_name' => $submission->company_name,
                'mapped_values' => $mapped,
                'semantic_data' => (array) ($submission->normalized_data ?? []),
            ],
        ));
    }

    /** @return array<int, array<string, mixed>> */
    private function formAnswers(FormTemplate $template, array $data): array
    {
        if (array_is_list($data)) {
            $aligned = [];
            foreach ($template->fields->values() as $index => $field) {
                if (array_key_exists($index, $data)) {
                    $aligned[(string) $field->field_key] = $data[$index];
                }
            }
            $data = $aligned;
        }

        return $template->fields
            ->map(static function ($field) use ($data): array {
                $semanticRole = $field->semantic_role instanceof \BackedEnum
                    ? (string) $field->semantic_role->value
                    : (string) ($field->semantic_role ?? '');
                $role = \Dth\Marketing\Enums\SemanticFieldRole::tryFrom($semanticRole);

                return [
                    'key' => (string) $field->field_key,
                    'label' => (string) $field->label,
                    'type' => $field->field_type instanceof \BackedEnum
                        ? (string) $field->field_type->value
                        : (string) $field->field_type,
                    'mapping' => $field->contact_mapping ?: $role?->legacyContactMapping(),
                    'semantic_role' => $semanticRole !== '' ? $semanticRole : null,
                    'value' => $data[$field->field_key] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function attribution(LandingPageSubmission $submission): array
    {
        return [
            'source' => $submission->source,
            'utm_source' => $submission->utm_source,
            'utm_medium' => $submission->utm_medium,
            'utm_campaign' => $submission->utm_campaign,
            'utm_content' => $submission->utm_content,
            'utm_term' => $submission->utm_term,
            'referrer' => $submission->referrer,
            'marketing_campaign_id' => $submission->marketing_campaign_id,
            'landing_page_id' => $submission->landing_page_id,
        ];
    }

    private function contactAction(AudienceContactReference $reference): LandingPageContactAction
    {
        $action = strtolower(trim((string) ($reference->metadata['action'] ?? '')));
        if ($action === LandingPageContactAction::Created->value || ($reference->metadata['created'] ?? null) === true) {
            return LandingPageContactAction::Created;
        }

        if ($action === LandingPageContactAction::Skipped->value) {
            return LandingPageContactAction::Skipped;
        }

        return LandingPageContactAction::Updated;
    }

    private function assertPayloadSize(array $payload): void
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $size = $encoded === false ? strlen(serialize($payload)) : strlen($encoded);
        $limit = max(1024, (int) config('dth-marketing.security.max_submission_payload_bytes', 65536));

        if ($size > $limit) {
            throw ValidationException::withMessages([
                'submission' => UiText::get(
                    'submission.payload_too_large',
                    'The submitted form is too large. Please reduce the input and try again.',
                ),
            ]);
        }
    }

    private function validateSubmissionType(array $payload): string
    {
        $validated = validator($payload, [
            'submission_type' => ['required', Rule::in(['personal', 'business'])],
        ])->validate();

        return (string) $validated['submission_type'];
    }

    private function submissionToken(array $payload): ?string
    {
        $token = trim((string) ($payload['_submission_token'] ?? ''));
        if ($token === '') {
            return null;
        }

        if (! Str::isUuid($token)) {
            throw ValidationException::withMessages([
                '_submission_token' => UiText::get('submission.token_invalid', 'The submission token is invalid. Reload the page and try again.'),
            ]);
        }

        return $token;
    }

    private function findExisting(
        LandingPage $page,
        ?string $submissionToken,
        string $fingerprint,
    ): ?LandingPageSubmission {
        if ($submissionToken !== null) {
            $existing = LandingPageSubmission::query()
                ->where('landing_page_id', $page->getKey())
                ->where('submission_token', $submissionToken)
                ->first();

            if ($existing !== null) {
                if ($existing->payload_fingerprint !== $fingerprint) {
                    throw ValidationException::withMessages([
                        '_submission_token' => UiText::get('submission.token_reused', 'This submission token has already been used for different data. Reload the page.'),
                    ]);
                }

                return $existing;
            }
        }

        return LandingPageSubmission::query()
            ->where('landing_page_id', $page->getKey())
            ->where('payload_fingerprint', $fingerprint)
            ->where('created_at', '>=', now()->subSeconds(
                max(1, (int) config('dth-marketing.security.duplicate_window_seconds', 60)),
            ))
            ->first();
    }

    private function payloadFingerprint(
        LandingPage $page,
        string $submissionType,
        array $validatedData,
    ): string {
        $normalized = $this->sortRecursively([
            'landing_page_id' => $page->getKey(),
            'submission_type' => $submissionType,
            'answers' => $validatedData,
        ]);

        return hash('sha256', json_encode(
            $normalized,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ) ?: '');
    }

    private function sortRecursively(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursively($item);
            }
        }

        if (array_is_list($value)) {
            return $value;
        }

        ksort($value);

        return $value;
    }


    private function firstValueByType(
        FormTemplate $template,
        array $data,
        FormFieldType $type,
    ): mixed {
        foreach ($template->fields as $field) {
            $fieldType = $field->field_type instanceof FormFieldType
                ? $field->field_type
                : FormFieldType::tryFrom((string) $field->field_type);
            if ($fieldType === $type && filled($data[$field->field_key] ?? null)) {
                return $data[$field->field_key];
            }
        }

        return null;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return $value !== '' ? mb_substr($value, 0, 255) : null;
    }

    private function normalizePhone(mixed $value): ?string
    {
        $value = preg_replace('/[^0-9+]/', '', trim((string) $value)) ?? '';

        return $value !== '' ? mb_substr($value, 0, 50) : null;
    }

    private function scalarOrNull(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? mb_substr($value, 0, 255) : null;
    }

    private function memberKey(
        string $submissionType,
        ?string $email,
        ?string $phone,
        string $fallbackFingerprint,
    ): string {
        $identity = $email !== null
            ? 'email:'.$email
            : ($phone !== null ? 'phone:'.$phone : 'payload:'.$fallbackFingerprint);

        return hash('sha256', $submissionType.'|'.$identity);
    }


    private function resolvePackageReference(LandingPage $page, array $mapped): ?string
    {
        $candidate = $this->scalarOrNull($mapped['lead.service_interest'] ?? null);
        if ($candidate === null) {
            return null;
        }

        $allowed = array_map('strval', (array) ($page->package_references ?? []));

        return in_array($candidate, $allowed, true) ? $candidate : null;
    }

    /** @return array<int, string> */
    private function allowedOptionValues(array $options): array
    {
        if ($options === []) {
            return [];
        }

        if (! array_is_list($options)) {
            return array_map('strval', array_keys($options));
        }

        return collect($options)
            ->map(static function (mixed $option): ?string {
                if (is_array($option)) {
                    $value = $option['value'] ?? $option['key'] ?? null;

                    return $value === null ? null : (string) $value;
                }

                return is_scalar($option) ? (string) $option : null;
            })
            ->filter(fn (?string $value): bool => filled($value))
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function additionalRules(string $rules): array
    {
        return collect(explode('|', $rules))
            ->map(static fn (string $rule): string => trim($rule))
            ->filter()
            ->values()
            ->all();
    }
}
