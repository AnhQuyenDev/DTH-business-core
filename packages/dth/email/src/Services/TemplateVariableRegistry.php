<?php

namespace Dth\Email\Services;

use Dth\Email\Models\EmailCampaign;
use Dth\Email\Support\UiText;
use Illuminate\Support\Str;

class TemplateVariableRegistry
{
    /**
     * Variables that are intentionally exposed to marketing users.
     *
     * @return array<string, array{label:string, description:string, kind:string}>
     */
    public function definitions(): array
    {
        return [
            'name' => [
                'label' => UiText::get('variables.recipient_name', 'Recipient name'),
                'description' => UiText::get('variables.recipient_name_description', 'Name stored on the campaign recipient.'),
                'kind' => 'recipient',
            ],
            'email' => [
                'label' => UiText::get('variables.recipient_email', 'Recipient email'),
                'description' => UiText::get('variables.recipient_email_description', 'Email address of the campaign recipient.'),
                'kind' => 'recipient',
            ],
            'recipient.name' => [
                'label' => UiText::get('variables.recipient_name_nested', 'Recipient name (nested)'),
                'description' => UiText::get('variables.recipient_name_nested_description', 'Alias of the recipient name.'),
                'kind' => 'recipient',
            ],
            'recipient.email' => [
                'label' => UiText::get('variables.recipient_email_nested', 'Recipient email (nested)'),
                'description' => UiText::get('variables.recipient_email_nested_description', 'Alias of the recipient email address.'),
                'kind' => 'recipient',
            ],
            'customer.name' => [
                'label' => UiText::get('variables.customer_name', 'Customer name'),
                'description' => UiText::get('variables.customer_name_description', 'Customer/recipient display name.'),
                'kind' => 'recipient',
            ],
            'customer.email' => [
                'label' => UiText::get('variables.customer_email', 'Customer email'),
                'description' => UiText::get('variables.customer_email_description', 'Customer/recipient email address.'),
                'kind' => 'recipient',
            ],
            'unsubscribe_url' => [
                'label' => UiText::get('variables.unsubscribe_link', 'Unsubscribe link'),
                'description' => UiText::get(
                    'variables.unsubscribe_link_description',
                    'Unique unsubscribe URL generated for each recipient. A compliance footer is added automatically when this variable is omitted.'
                ),
                'kind' => 'system',
            ],
        ];
    }

    /** @return array<string, string> */
    public function mergeTagLabels(): array
    {
        $definitions = $this->definitions();

        return [
            'name' => $definitions['name']['label'],
            'email' => $definitions['email']['label'],
            'customer.name' => $definitions['customer.name']['label'],
            'customer.email' => $definitions['customer.email']['label'],
            'unsubscribe_url' => $definitions['unsubscribe_url']['label'],
        ];
    }

    /** @return list<string> */
    public function extract(?string ...$contents): array
    {
        $variables = [];

        foreach ($contents as $content) {
            if ($content === null || trim($content) === '') {
                continue;
            }

            preg_match_all('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', $content, $matches);

            foreach ($matches[1] ?? [] as $variable) {
                $variables[(string) $variable] = true;
            }
        }

        $keys = array_keys($variables);
        sort($keys);

        return array_values($keys);
    }

    /** @return list<string> */
    public function campaignVariables(EmailCampaign $campaign): array
    {
        return $this->extract(
            $campaign->subject,
            $campaign->preheader,
            $campaign->html_body,
            $campaign->text_body,
        );
    }

    /** @return array<string, string> */
    public function recipientRequirements(EmailCampaign $campaign): array
    {
        $requirements = [];

        foreach ($this->campaignVariables($campaign) as $variable) {
            if ($this->isAutomaticallyResolved($variable)) {
                continue;
            }

            if ($this->isNameAlias($variable)) {
                $requirements['name'] = UiText::get('variables.recipient_name', 'Recipient name');
                continue;
            }

            $requirements[$variable] = $this->label($variable);
        }

        return $requirements;
    }

    /** @return list<string> */
    public function expectedCsvColumns(EmailCampaign $campaign): array
    {
        return [
            'email',
            ...array_keys($this->recipientRequirements($campaign)),
        ];
    }

    public function label(string $variable): string
    {
        $definitions = $this->definitions();

        if (isset($definitions[$variable])) {
            return $definitions[$variable]['label'];
        }

        return Str::of($variable)
            ->replace(['.', '_', '-'], ' ')
            ->headline()
            ->toString();
    }

    public function description(string $variable): string
    {
        $definitions = $this->definitions();

        return $definitions[$variable]['description']
            ?? UiText::get(
                'variables.custom_description',
                'Custom personalization value. Supply this value for each recipient manually or through a CSV column with the same name.'
            );
    }

    public function kind(string $variable): string
    {
        return $this->definitions()[$variable]['kind'] ?? 'custom';
    }

    public function isKnown(string $variable): bool
    {
        return isset($this->definitions()[$variable]);
    }

    public function isAutomaticallyResolved(string $variable): bool
    {
        return in_array($variable, [
            'email',
            'recipient.email',
            'customer.email',
            'unsubscribe_url',
            'open_pixel_url',
        ], true);
    }

    public function isNameAlias(string $variable): bool
    {
        return in_array($variable, [
            'name',
            'recipient.name',
            'customer.name',
        ], true);
    }
}
