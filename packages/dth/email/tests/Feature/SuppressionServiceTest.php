<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\Enums\SuppressionReason;
use Dth\Email\Models\EmailSuppression;
use Dth\Email\Services\SuppressionService;
use Dth\Email\Tests\TestCase;

class SuppressionServiceTest extends TestCase
{
    public function test_it_normalizes_and_suppresses_an_email(): void
    {
        $service = $this->app->make(SuppressionService::class);

        $service->suppress('  PERSON@EXAMPLE.COM ', SuppressionReason::Manual);

        $this->assertTrue($service->isSuppressed('person@example.com'));
        $this->assertDatabaseHas('email_suppressions', [
            'email' => 'person@example.com',
            'reason' => 'manual',
        ]);
    }

    public function test_release_keeps_history_but_allows_future_email(): void
    {
        $service = $this->app->make(SuppressionService::class);
        $suppression = $service->suppress(
            'person@example.com',
            SuppressionReason::Unsubscribe,
            'email.unsubscribe',
        );

        $id = $suppression->id;

        $service->release(
            $suppression,
            releasedBy: 123,
            source: 'customer_opt_in',
            note: 'Customer opted in again.',
        );

        $this->assertFalse($service->isSuppressed('person@example.com'));
        $this->assertSame(1, EmailSuppression::query()->whereKey($id)->count());
        $this->assertDatabaseHas('email_suppressions', [
            'id' => $id,
            'release_source' => 'customer_opt_in',
            'released_by' => 123,
        ]);
        $this->assertNotNull(EmailSuppression::query()->findOrFail($id)->released_at);
    }
    public function test_a_new_suppression_cycle_does_not_overwrite_released_history(): void
    {
        $service = $this->app->make(SuppressionService::class);
        $first = $service->suppress('person@example.com', SuppressionReason::Unsubscribe);
        $service->release($first, source: 'customer_opt_in', note: 'Opted in again.');

        $second = $service->suppress('person@example.com', SuppressionReason::Unsubscribe);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, EmailSuppression::query()->where('email', 'person@example.com')->count());
        $this->assertNotNull(EmailSuppression::query()->findOrFail($first->id)->released_at);
        $this->assertNull(EmailSuppression::query()->findOrFail($second->id)->released_at);
        $this->assertTrue($service->isSuppressed('person@example.com'));
    }

}
