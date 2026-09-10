<?php

namespace Dth\Email\Services;

use Dth\Email\Enums\CampaignRecipientStatus;
use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Models\CampaignRecipient;
use Dth\Email\Models\EmailCampaign;
use LogicException;

class CampaignRecipientService
{
    public function add(EmailCampaign $campaign, array $recipient): CampaignRecipient
    {
        $this->assertDraft($campaign);

        $email = mb_strtolower(trim((string) $recipient['email']));

        return CampaignRecipient::query()->updateOrCreate(
            [
                'campaign_id' => $campaign->id,
                'email' => $email,
            ],
            [
                'external_type' => $recipient['external_type'] ?? null,
                'external_id' => $recipient['external_id'] ?? null,
                'name' => $recipient['name'] ?? null,
                'variables' => $recipient['variables'] ?? null,
                'status' => CampaignRecipientStatus::Pending,
                'failure_reason' => null,
                'failed_at' => null,
            ],
        );
    }


    public function update(CampaignRecipient $recipient, array $attributes): CampaignRecipient
    {
        $recipient->loadMissing('campaign');
        $this->assertDraft($recipient->campaign);

        if (array_key_exists('email', $attributes)) {
            $attributes['email'] = mb_strtolower(trim((string) $attributes['email']));
        }

        $recipient->update($attributes);

        return $recipient->refresh();
    }

    public function delete(CampaignRecipient $recipient): void
    {
        $recipient->loadMissing('campaign');
        $this->assertDraft($recipient->campaign);
        $recipient->delete();
    }

    public function addMany(EmailCampaign $campaign, iterable $recipients): int
    {
        $this->assertDraft($campaign);

        $count = 0;

        foreach ($recipients as $recipient) {
            $this->add($campaign, $recipient);
            $count++;
        }

        return $count;
    }

    private function assertDraft(EmailCampaign $campaign): void
    {
        $campaign->refresh();

        if ($campaign->status !== EmailCampaignStatus::Draft) {
            throw new LogicException(
                'Recipients can only be added or changed while the campaign is in Draft status.'
            );
        }
    }
}
