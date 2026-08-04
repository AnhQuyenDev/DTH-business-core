<?php

namespace App\Services\Marketing;

use App\Enums\Crm\ContactQualificationStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerList;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\Contact;
use App\Models\Marketing\Segment;
use App\Services\Crm\SegmentQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CampaignAudienceService
{
    public function recipientsForCampaign(Campaign $campaign): Collection
    {
        $query = Customer::query()->with(['tags', 'lists'])
            ->whereNotNull('email')
            ->where('consent_status', 'subscribed')
            ->whereNotIn('status', ['blocked', 'archived']);

        return match ($campaign->audience_type) {
            'list' => $this->forList($query, $campaign->audience_id),
            'tag' => $this->forTag($query, $campaign->audience_id),
            'segment' => $this->forSegment($query, $campaign->audience_id),
            'qualified' => $this->forQualifiedFromLandingPage($query, $campaign),
            'all_subscribed' => $query->get(),
            default => collect(),
        };
    }

    public function contactsForLegacyCampaign(Campaign $campaign): Collection
    {
        $query = Contact::query()
            ->whereHas('personalProfile', fn ($q) => $q->whereNotNull('email'));

        return match ($campaign->audience_type) {
            'all_subscribed' => $query->get(),
            default => collect(),
        };
    }

    protected function forList($query, ?int $listId): Collection
    {
        if (! $listId) {
            return collect();
        }
        return $query->whereHas('lists', fn (Builder $q) => $q->where('customer_lists.id', $listId))->get();
    }

    protected function forTag($query, ?int $tagId): Collection
    {
        if (! $tagId) {
            return collect();
        }
        return $query->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $tagId))->get();
    }

    protected function forSegment($query, ?int $segmentId): Collection
    {
        if (! $segmentId) {
            return collect();
        }
        $segment = Segment::find($segmentId);
        if (! $segment) {
            return collect();
        }
        return app(SegmentQueryService::class)->queryForCustomerSegment($segment)->get();
    }

    protected function forQualifiedFromLandingPage($query, Campaign $campaign): Collection
    {
        if (! $campaign->landing_page_id) {
            return collect();
        }

        return $query
            ->whereHas('contact.qualification', fn (Builder $qualificationQuery) => $qualificationQuery->where('status', ContactQualificationStatus::Qualified->value))
            ->whereHas('contact.landingPageSubmissions', fn (Builder $submissionQuery) => $submissionQuery->where('landing_page_id', $campaign->landing_page_id))
            ->get();
    }

    public function audienceOptions(string $type): array
    {
        return match ($type) {
            'list' => CustomerList::query()->orderBy('name')->pluck('name', 'id')->all(),
            'tag' => \App\Models\Marketing\Tag::query()->orderBy('name')->pluck('name', 'id')->all(),
            'segment' => Segment::query()->orderBy('name')->pluck('name', 'id')->all(),
            default => [],
        };
    }
}
