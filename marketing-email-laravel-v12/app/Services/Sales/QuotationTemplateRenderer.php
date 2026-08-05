<?php

namespace App\Services\Sales;

use App\Models\Marketing\EmailTemplate;
use App\Models\Sales\Quotation;

class QuotationTemplateRenderer
{
    public function context(Quotation $quotation): array
    {
        $publicUrl = route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]);

        return [
            'quotation_code' => $quotation->quotation_code,
            'quotation_title' => $quotation->title,
            'grand_total' => number_format($quotation->grand_total, 0).' '.$quotation->currency,
            'currency' => $quotation->currency,
            'quotation_date' => $quotation->quotation_date?->format('d/m/Y'),
            'valid_until' => $quotation->valid_until?->format('d/m/Y'),
            'customer_name' => $quotation->customer_snapshot['display_name'] ?? '',
            'customer_company' => $quotation->customer_snapshot['company_name'] ?? '',
            'customer_email' => $quotation->customer_snapshot['email'] ?? '',
            'customer_phone' => $quotation->customer_snapshot['phone'] ?? '',
            'company_name' => company_name(),
            'assigned_staff' => $quotation->assignee?->name ?? '',
            'public_url' => $publicUrl,
        ];
    }

    public function render(EmailTemplate $template, Quotation $quotation, ?string $subjectOverride = null): array
    {
        $context = $this->context($quotation);

        $subject = $this->replaceTokens($subjectOverride ?? $template->subject, $context);
        $htmlBody = $this->replaceTokens($this->normalizeBody($template->html_body ?? ''), $context);

        $body = view('sales.emails.quotation-template', [
            'quotation' => $quotation,
            'body' => $htmlBody,
        ])->render();

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Templates may store pasted full HTML pages as entity-encoded text
     * (e.g. "&lt;!DOCTYPE html&gt;..."). Decode entities and keep only the
     * inner <body> content so the email renders as HTML instead of source code.
     */
    private function normalizeBody(string $htmlBody): string
    {
        $decoded = html_entity_decode($htmlBody, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $decoded, $matches)) {
            return $matches[1];
        }

        return $decoded;
    }

    private function replaceTokens(string $source, array $context): string
    {
        $tokens = [];
        foreach ($context as $key => $value) {
            $tokens['{{'.$key.'}}'] = (string) ($value ?? '');
        }

        return strtr($source, $tokens);
    }
}
