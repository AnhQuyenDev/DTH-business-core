<?php

namespace Dth\Email\Services;

class EmailComplianceContentService
{
    public function injectPreheader(string $html, ?string $preheader): string
    {
        if ($preheader === null || trim($preheader) === '') {
            return $html;
        }

        $block = '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;mso-hide:all;">'
            .e($preheader)
            .'</div>';

        if (preg_match('/<body\b[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $match = $matches[0][0];
            $position = $matches[0][1] + strlen($match);

            return substr($html, 0, $position).$block.substr($html, $position);
        }

        return $block.$html;
    }

    public function ensureHtmlUnsubscribeFooter(
        string $html,
        string $unsubscribeUrl,
        ?string $senderName = null,
    ): string {
        if (trim($unsubscribeUrl) === '' || str_contains($html, $unsubscribeUrl)) {
            return $html;
        }

        $sender = trim((string) $senderName);
        $sender = $sender !== '' ? $sender : 'the sender';
        $notice = str_replace(
            ':sender',
            e($sender),
            (string) config(
                'dth-email.compliance.footer_notice',
                'You are receiving this marketing email from :sender.'
            ),
        );
        $label = e((string) config('dth-email.compliance.unsubscribe_label', 'Unsubscribe'));

        $footer = '<div data-dth-email-compliance-footer="1" style="margin-top:32px;padding-top:16px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.5;color:#6b7280;text-align:center;">'
            .$notice.' '
            .'<a href="'.e($unsubscribeUrl).'" style="color:#4b5563;text-decoration:underline;">'.$label.'</a>'
            .'</div>';

        $bodyPosition = strripos($html, '</body>');

        if ($bodyPosition !== false) {
            return substr($html, 0, $bodyPosition).$footer.substr($html, $bodyPosition);
        }

        return $html.$footer;
    }

    public function ensureTextUnsubscribeFooter(
        ?string $text,
        string $unsubscribeUrl,
        ?string $senderName = null,
    ): ?string {
        if ($text === null || trim($text) === '') {
            return $text;
        }

        if (trim($unsubscribeUrl) === '' || str_contains($text, $unsubscribeUrl)) {
            return $text;
        }

        $sender = trim((string) $senderName);
        $sender = $sender !== '' ? $sender : 'the sender';
        $notice = str_replace(
            ':sender',
            $sender,
            (string) config(
                'dth-email.compliance.footer_notice',
                'You are receiving this marketing email from :sender.'
            ),
        );
        $label = (string) config('dth-email.compliance.unsubscribe_label', 'Unsubscribe');

        return rtrim($text)
            ."\n\n---\n{$notice}\n{$label}: {$unsubscribeUrl}";
    }
}
