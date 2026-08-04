<?php

namespace App\Services\Crm;

use App\Enums\Marketing\EmailEventType;
use App\Mail\MarketingCampaignMail;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerInteraction;
use App\Models\Crm\Staff;
use App\Models\Marketing\EmailEvent;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\SendingAccount;
use App\Services\Marketing\EmailSendingService;
use App\Services\Marketing\TemplateRenderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class CustomerCareEmailService
{
    public function __construct(
        private readonly EmailSendingService $emailSending,
        private readonly TemplateRenderService $templateRender,
    ) {}

    /**
     * Send a one-to-one care email to a customer through a sending account.
     *
     * Records an EmailEvent (with tracking token) and a CustomerInteraction.
     *
     * @param  array<string>  $to
     * @param  array<string>  $cc
     * @param  array<string>  $bcc
     * @param  array<int, array{disk?: string, path: string, name?: string, mime?: string|null}>  $attachments
     *
     * @throws Throwable
     */
    public function send(
        Customer $customer,
        string $subject,
        string $htmlBody,
        ?EmailTemplate $template = null,
        ?SendingAccount $account = null,
        ?int $staffId = null,
        array $to = [],
        array $cc = [],
        array $bcc = [],
        array $attachments = [],
    ): void {
        $account ??= $this->resolveAccount($staffId);

        if (! $account) {
            throw new \RuntimeException(__('page.customer_care.email_no_account'));
        }

        if (! $this->isAccountReadyForCare($account)) {
            throw new \RuntimeException(__('page.customer_care.email_account_invalid'));
        }

        $to = $to ?: array_filter([$customer->email]);

        if (empty($to)) {
            throw new \RuntimeException(__('page.customer_care.email_no_address'));
        }

        $token = (string) Str::uuid();

        $subject = $this->templateRender->renderContent($subject, $customer);
        $htmlBody = $this->templateRender->renderContent($htmlBody, $customer);

        $htmlWithTracking = $this->injectTracking($htmlBody, $token);

        $mailerName = 'care_' . $account->id;
        $config = $account->config_encrypted ?? [];

        $this->emailSending->configureSmtpMailer(
            mailer: $mailerName,
            config: $config,
            fromAddress: filled($account->from_email) ? $account->from_email : null,
            fromName: filled($account->from_name) ? $account->from_name : null,
        );

        try {
            Mail::mailer($mailerName)
                ->to($to)
                ->cc($cc)
                ->bcc($bcc)
                ->send(new MarketingCampaignMail(
                    subjectLine: $subject,
                    preheader: null,
                    htmlBody: $htmlWithTracking,
                    textBody: strip_tags($htmlBody),
                    fromAddress: filled($account->from_email) ? $account->from_email : null,
                    fromName: filled($account->from_name) ? $account->from_name : null,
                    attachments: $attachments,
                ));
        } catch (Throwable $e) {
            EmailEvent::create([
                'tracking_token' => $token,
                'customer_id' => $customer->id,
                'contact_id' => $customer->contact_id,
                'event_type' => EmailEventType::Failed->value,
                'event_payload' => [
                    'subject' => $subject,
                    'reason' => $e->getMessage(),
                ],
                'occurred_at' => now(),
            ]);

            Log::error('CustomerCareEmailService: failed to send', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        EmailEvent::create([
            'tracking_token' => $token,
            'customer_id' => $customer->id,
            'contact_id' => $customer->contact_id,
            'event_type' => EmailEventType::Sent->value,
            'event_payload' => [
                'subject' => $subject,
                'template_id' => $template?->id,
            ],
            'occurred_at' => now(),
        ]);

        CustomerInteraction::create([
            'customer_id' => $customer->id,
            'staff_id' => $staffId,
            'interaction_type' => 'email',
            'subject' => $subject,
            'content' => Str::limit(strip_tags($htmlBody), 1000),
            'outcome' => __('page.customer_care.email_outcome'),
            'status' => 'completed',
            'interaction_at' => now(),
        ]);
    }

    /**
     * Render a template with the customer context (used to prefill the compose form).
     */
    public function renderTemplate(EmailTemplate $template, Customer $customer): array
    {
        $rendered = $this->templateRender->render($template, customer: $customer);

        return [
            'subject' => $rendered['subject'],
            'body' => $rendered['html_body'],
        ];
    }

    public function defaultSendingAccount(): ?SendingAccount
    {
        return SendingAccount::query()
            ->where('status', 'active')
            ->whereNull('department_id')
            ->orderBy('id')
            ->first();
    }

    /**
     * Pick the sending account used for care emails sent by a staff member.
     *
     * Staff members send through the account bound to their department. When
     * the department has no active account, sending is rejected with a clear
     * message instead of falling back to a shared account.
     */
    public function resolveAccount(?int $staffId): ?SendingAccount
    {
        $staff = $staffId ? Staff::query()->find($staffId) : null;

        if ($staff && $staff->department_id) {
            $account = $this->departmentSendingAccount($staff->department_id);

            if (! $account) {
                throw new \RuntimeException(
                    __('page.customer_care.email_no_department_account', [
                        'department' => $staff->department?->name ?? ('#' . $staff->department_id),
                    ])
                );
            }

            return $account;
        }

        return $this->defaultSendingAccount();
    }

    public function departmentSendingAccount(int $departmentId): ?SendingAccount
    {
        return SendingAccount::query()
            ->where('department_id', $departmentId)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();
    }

    /**
     * Care emails require a real SMTP account, so they never reuse the shared
     * default mailer credentials.
     */
    public function isAccountReadyForCare(SendingAccount $account): bool
    {
        $config = $account->config_encrypted ?? [];

        return $account->provider === 'smtp'
            && filled($config['host'] ?? null)
            && filled($config['username'] ?? null)
            && filled($config['password'] ?? null);
    }

    /**
     * Info for the composer UI: which sender will be used and why not, if any.
     *
     * @return array{account: SendingAccount|null, error: string|null, ready: bool}
     */
    public function getSenderInfo(?int $staffId): array
    {
        try {
            $account = $this->resolveAccount($staffId);

            if (! $account) {
                return ['account' => null, 'error' => __('page.customer_care.email_no_account'), 'ready' => false];
            }

            return [
                'account' => $account,
                'error' => null,
                'ready' => $this->isAccountReadyForCare($account),
            ];
        } catch (\RuntimeException $e) {
            return ['account' => null, 'error' => $e->getMessage(), 'ready' => false];
        }
    }

    private function injectTracking(string $html, string $token): string
    {
        $openUrl = route('care.track.open', ['token' => $token]) . '.gif';
        $clickBase = route('care.track.click', ['token' => $token]);

        $html = (string) preg_replace_callback(
            '#(<a[^>]+href=")(https?://[^"]+)(")#i',
            static function (array $m) use ($clickBase): string {
                if (str_contains($m[2], $clickBase)) {
                    return $m[0];
                }

                return $m[1] . $clickBase . '?u=' . urlencode($m[2]) . $m[3];
            },
            $html
        );

        $pixel = '<img src="' . $openUrl . '" width="1" height="1" style="display:none" alt="" />';

        return $html . $pixel;
    }
}
