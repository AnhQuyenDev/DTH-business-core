<?php

namespace Dth\Email\Services;

use Dth\Email\Models\CampaignRecipient;
use Dth\Email\Models\EmailMessage;

class CampaignVariableResolver
{
    public function __construct(
        private readonly TrackingUrlService $tracking,
    ) {}

    public function forRecipient(
        CampaignRecipient $recipient,
        ?EmailMessage $message = null,
    ): array {
        $recipientVariables = is_array($recipient->variables)
            ? $recipient->variables
            : [];

        $variables = $recipientVariables;

        $variables['name'] = $recipient->name ?? '';
        $variables['email'] = $recipient->email;

        $variables['recipient'] = [
            ...(is_array($recipientVariables['recipient'] ?? null)
                ? $recipientVariables['recipient']
                : []),
            'name' => $recipient->name ?? '',
            'email' => $recipient->email,
        ];

        $variables['customer'] = [
            ...(is_array($recipientVariables['customer'] ?? null)
                ? $recipientVariables['customer']
                : []),
            'name' => $recipient->name ?? '',
            'email' => $recipient->email,
        ];

        if ($message !== null) {
            $variables['unsubscribe_url'] = $this->tracking->unsubscribeUrl($message);
            $variables['open_pixel_url'] = $this->tracking->openPixelUrl($message);
        }

        return $variables;
    }
}
