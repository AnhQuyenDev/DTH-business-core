<?php

namespace App\Services\Marketing;

use App\Models\Crm\Customer;
use App\Models\Marketing\Contact;
use App\Models\Marketing\EmailTemplate;
use Illuminate\Support\Str;

class TemplateRenderService
{
    public function render(EmailTemplate $template, ?Contact $contact = null, ?Customer $customer = null, string $unsubscribeUrl = '', ?string $subjectOverride = null, array $extraPlaceholders = []): array
    {
        $context = $customer
            ? $this->contextFromCustomer($customer, $unsubscribeUrl)
            : $this->contextFromContact($contact, $unsubscribeUrl);

        $context = array_merge($context, $extraPlaceholders);

        $htmlContainsUnsubscribeToken = Str::contains($template->html_body, '{{unsubscribe_url}}');
        $textContainsUnsubscribeToken = $template->text_body !== null && Str::contains($template->text_body, '{{unsubscribe_url}}');

        $subjectSource = $subjectOverride ?? $template->subject;
        $subject = $this->replaceTokens($subjectSource, $context);
        $preheader = $template->preheader !== null ? $this->replaceTokens($template->preheader, $context) : null;
        $htmlBody = $this->replaceTokens($template->html_body, $context);
        $textBody = $template->text_body !== null ? $this->replaceTokens($template->text_body, $context) : null;

        if ($unsubscribeUrl !== '' && ! $htmlContainsUnsubscribeToken) {
            $htmlBody .= '<p><a href="' . e($unsubscribeUrl) . '">Unsubscribe</a></p>';
        }

        if ($unsubscribeUrl !== '' && ! $textContainsUnsubscribeToken) {
            $textBody = trim((string) $textBody) . PHP_EOL . 'Unsubscribe: ' . $unsubscribeUrl;
        }

        $landingPageUrl = $context['landing_page_url'] ?? '#';
        $htmlContainsLandingToken = Str::contains($template->html_body, '{{landing_page_url}}');
        if ($landingPageUrl !== '' && $landingPageUrl !== '#' && ! $htmlContainsLandingToken) {
            $htmlBody .= '<div style="margin-top:28px;text-align:center">'
                . '<a href="' . e($landingPageUrl) . '" style="display:inline-block;padding:12px 24px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600">'
                . e(__('action.view_landing_page'))
                . '</a></div>';
        }

        $textContainsLandingToken = $template->text_body !== null && Str::contains($template->text_body, '{{landing_page_url}}');
        if ($landingPageUrl !== '' && $landingPageUrl !== '#' && ! $textContainsLandingToken) {
            $textBody = trim((string) $textBody) . PHP_EOL . __('action.view_landing_page') . ': ' . $landingPageUrl;
        }

        return [
            'subject' => $subject,
            'preheader' => $preheader,
            'html_body' => $htmlBody,
            'text_body' => $textBody,
        ];
    }

    protected function contextFromContact(?Contact $contact, string $unsubscribeUrl): array
    {
        return [
            'first_name'       => $contact?->first_name ?? '{{first_name}}',
            'last_name'        => $contact?->last_name ?? '{{last_name}}',
            'full_name'        => $contact?->full_name ?? '{{full_name}}',
            'email'            => $contact?->email ?? '{{email}}',
            'company_name'     => $contact?->company_name ?? '{{company_name}}',
            'unsubscribe_url'  => $unsubscribeUrl,
            'landing_page_url' => '#',
            'customer_code'    => '{{customer_code}}',
            'display_name'     => '{{display_name}}',
            'phone'            => '{{phone}}',
            'owner_name'       => '{{owner_name}}',
        ];
    }

    protected function contextFromCustomer(Customer $customer, string $unsubscribeUrl): array
    {
        $owner = $customer->currentOwner();

        return [
            'first_name'       => $customer->display_name ?? '{{first_name}}',
            'last_name'        => '',
            'full_name'        => $customer->display_name ?? '{{full_name}}',
            'email'            => $customer->email ?? '{{email}}',
            'company_name'     => $customer->customer_type === 'business' ? ($customer->contact?->businessProfile?->company_name ?? '{{company_name}}') : '{{company_name}}',
            'unsubscribe_url'  => $unsubscribeUrl,
            'landing_page_url' => '#',
            'customer_code'    => $customer->customer_code ?? '{{customer_code}}',
            'display_name'     => $customer->display_name ?? '{{display_name}}',
            'phone'            => $customer->phone ?? '{{phone}}',
            'owner_name'       => $owner?->full_name ?? '{{owner_name}}',
        ];
    }

    protected function replaceTokens(string $content, array $context): string
    {
        $replacements = [];

        foreach ($context as $key => $value) {
            $replacements['{{' . $key . '}}'] = (string) $value;
        }

        return strtr($content, $replacements);
    }

    /**
     * Render merge tokens inside arbitrary content with a customer context.
     */
    public function renderContent(string $content, ?Customer $customer = null, ?Contact $contact = null): string
    {
        if (blank($content)) {
            return '';
        }

        $context = $customer
            ? $this->contextFromCustomer($customer, '')
            : $this->contextFromContact($contact, '');

        return $this->replaceTokens($content, $context);
    }
}
