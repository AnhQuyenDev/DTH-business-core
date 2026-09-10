<?php

namespace Dth\Email\Services;

use Dth\Email\Enums\CampaignRecipientStatus;
use Dth\Email\Models\EmailCampaign;
use LogicException;

class CampaignVariableValidator
{
    public function __construct(
        private readonly TemplateVariableRegistry $registry,
        private readonly CampaignVariableResolver $resolver,
    ) {}

    public function assertRecipientsComplete(EmailCampaign $campaign): void
    {
        $requiredVariables = array_values(array_filter(
            $this->registry->campaignVariables($campaign),
            fn (string $variable): bool => ! $this->registry->isAutomaticallyResolved($variable),
        ));

        if ($requiredVariables === []) {
            return;
        }

        $missingCounts = [];

        $campaign->recipients()
            ->where('status', CampaignRecipientStatus::Pending->value)
            ->orderBy('id')
            ->chunkById(200, function ($recipients) use ($requiredVariables, &$missingCounts): void {
                foreach ($recipients as $recipient) {
                    $variables = $this->resolver->forRecipient($recipient);

                    foreach ($requiredVariables as $variable) {
                        $value = data_get($variables, $variable);

                        if ($this->hasValue($value)) {
                            continue;
                        }

                        $missingCounts[$variable] = ($missingCounts[$variable] ?? 0) + 1;
                    }
                }
            });

        if ($missingCounts === []) {
            return;
        }

        $parts = [];

        foreach ($missingCounts as $variable => $count) {
            $parts[] = sprintf('{{ %s }} (%d recipient%s)', $variable, $count, $count === 1 ? '' : 's');
        }

        throw new LogicException(
            'Personalization data is incomplete. Missing values: '
            .implode(', ', $parts)
            .'. Update the recipients or import a CSV with the required columns before sending.'
        );
    }

    private function hasValue(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }
}
