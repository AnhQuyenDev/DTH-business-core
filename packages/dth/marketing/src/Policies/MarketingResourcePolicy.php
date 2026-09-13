<?php

namespace Dth\Marketing\Policies;

use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Support\MarketingAuthorizationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class MarketingResourcePolicy
{
    public function __construct(private readonly MarketingAuthorizationService $authorization) {}

    public function viewAny(Authenticatable $user): bool
    {
        return $this->authorization->view($user);
    }

    public function view(Authenticatable $user, Model $model): bool
    {
        return $this->authorization->view($user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->authorization->manage($user);
    }

    public function update(Authenticatable $user, Model $model): bool
    {
        return $this->authorization->manage($user);
    }

    public function delete(Authenticatable $user, Model $model): bool
    {
        if (! $this->authorization->manage($user)) {
            return false;
        }

        // Preserve the business guards that already existed before M-I. This
        // also makes DeleteBulkAction safe when authorizeIndividualRecords()
        // is enabled on the resource tables.
        return match (true) {
            $model instanceof MarketingCampaign => $model->status === MarketingCampaignStatus::Draft,
            $model instanceof LandingPage => $model->status === LandingPageStatus::Draft,
            $model instanceof FormTemplate => ! $model->isUsedByPublishedLandingPage(),
            default => true,
        };
    }

    public function deleteAny(Authenticatable $user): bool
    {
        return $this->authorization->manage($user);
    }

    public function restore(Authenticatable $user, Model $model): bool
    {
        return false;
    }

    public function restoreAny(Authenticatable $user): bool
    {
        return false;
    }

    public function forceDelete(Authenticatable $user, Model $model): bool
    {
        return $this->authorization->manage($user);
    }

    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $this->authorization->manage($user);
    }
}
