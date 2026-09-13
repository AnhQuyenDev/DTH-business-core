<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Contracts\AudienceProvider;
use Dth\Marketing\Models\ContactList;
use Dth\Marketing\Models\ContactListMember;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\LandingPageSubmission;
use Dth\Marketing\Models\Segment;
use Illuminate\Support\Str;

final class AudienceAutomationService
{
    public function __construct(
        private readonly AudienceProvider $audienceProvider,
    ) {}

    public function apply(
        LandingPageSubmission $submission,
        LandingPage $landingPage,
        FormTemplate $formTemplate,
    ): void {
        $tags = $this->configuredTags($landingPage, $formTemplate, $submission);
        $lists = $this->configuredLists($landingPage, $formTemplate);

        $submission->forceFill(['tags' => $tags])->saveQuietly();

        if (
            $tags !== []
            && filled($submission->contact_reference)
            && $this->audienceProvider->available()
        ) {
            $this->audienceProvider->addTags(
                (string) $submission->contact_reference,
                $tags,
            );
        }

        $createdBy = $landingPage->created_by ?? $formTemplate->created_by ?? null;

        foreach ($lists as $listName) {
            $list = $this->resolveList($listName, $createdBy);
            $this->subscribe($list, $submission);
        }

        if ($landingPage->auto_create_segment || $formTemplate->auto_create_segment) {
            $this->ensureAutomaticSegment($landingPage, $createdBy);
        }
    }

    /** @return array<int, string> */
    private function configuredTags(
        LandingPage $page,
        FormTemplate $template,
        LandingPageSubmission $submission,
    ): array {
        $values = [];

        if ($page->auto_create_tags) {
            $values = array_merge($values, (array) ($page->auto_tag_names ?? []));
        }

        if ($template->auto_create_tags) {
            $values = array_merge($values, (array) ($template->auto_tag_names ?? []));
        }

        // Restored from the original Marketing flow: a field may promote the
        // submitted value itself to a tag. The tag is still applied through the
        // AudienceProvider boundary; Marketing never imports CRM tag models.
        foreach ($template->fields as $field) {
            if (! $field->tag_from_value) {
                continue;
            }

            $value = data_get($submission->data, (string) $field->field_key);
            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map(
                    static fn (mixed $item): string => trim((string) $item),
                    $value,
                )));
            }

            $value = trim((string) ($value ?? ''));
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return collect($values)
            ->map(static fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->unique(fn (string $value): string => mb_strtolower($value))
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function configuredLists(LandingPage $page, FormTemplate $template): array
    {
        $values = [];

        if ($page->auto_create_lists) {
            $values = array_merge($values, (array) ($page->auto_list_names ?? []));
        }

        if ($template->auto_create_lists) {
            $values = array_merge($values, (array) ($template->auto_list_names ?? []));
        }

        return collect($values)
            ->map(static fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->unique(fn (string $value): string => mb_strtolower($value))
            ->values()
            ->all();
    }

    private function resolveList(string $name, ?int $createdBy): ContactList
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'list-'.Str::lower(Str::random(8));

        $existing = ContactList::query()->where('slug', $slug)->first();
        if ($existing !== null) {
            return $existing;
        }

        return ContactList::query()->create([
            'name' => $name,
            'slug' => $slug,
            'type' => 'service',
            'status' => 'active',
            'created_by' => $createdBy,
        ]);
    }

    private function subscribe(ContactList $list, LandingPageSubmission $submission): void
    {
        ContactListMember::query()->updateOrCreate(
            [
                'contact_list_id' => $list->getKey(),
                'member_key' => $submission->member_key,
            ],
            [
                'source_submission_id' => $submission->getKey(),
                'contact_reference' => $submission->contact_reference,
                'normalized_email' => $submission->normalized_email,
                'normalized_phone' => $submission->normalized_phone,
                'display_name' => $submission->display_name,
                'audience_type' => $submission->submission_type,
                'status' => 'subscribed',
                'metadata' => [
                    'landing_page_id' => $submission->landing_page_id,
                    'marketing_campaign_id' => $submission->marketing_campaign_id,
                    'source' => $submission->source,
                ],
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ],
        );
    }

    private function ensureAutomaticSegment(LandingPage $page, ?int $createdBy): Segment
    {
        $name = 'Leads - '.$page->name;
        $slug = 'lp-'.$page->getKey().'-leads';

        return Segment::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => 'Automatically created from Landing Page: '.$page->name,
                'rules' => [
                    'conditions' => [[
                        'field' => 'landing_page',
                        'operator' => 'equals',
                        'value' => (string) $page->getKey(),
                    ]],
                ],
                'status' => 'active',
                'is_automatic' => true,
                'created_by' => $createdBy,
            ],
        );
    }
}
