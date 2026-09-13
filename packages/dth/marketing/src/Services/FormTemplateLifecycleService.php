<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Models\FormTemplate;
use Illuminate\Validation\ValidationException;

final class FormTemplateLifecycleService
{
    public function __construct(private readonly MarketingAuditTrailService $audit) {}

    /** @return array<string, array<int, string>> */
    public function transitions(): array
    {
        return [
            FormTemplateStatus::Draft->value => [
                FormTemplateStatus::Active->value,
                FormTemplateStatus::Archived->value,
            ],
            FormTemplateStatus::Active->value => [
                FormTemplateStatus::Archived->value,
            ],
            FormTemplateStatus::Archived->value => [],
        ];
    }

    public function assertTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, $this->transitions()[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid Form Template transition: {$from} -> {$to}.",
            ]);
        }
    }

    /** @param array<int, string> $dirty */
    public function assertChangesAllowed(string $originalStatus, array $dirty): void
    {
        if ($originalStatus === FormTemplateStatus::Archived->value) {
            $businessFields = array_diff($dirty, ['updated_at']);

            if ($businessFields !== []) {
                throw ValidationException::withMessages([
                    'status' => 'Archived form templates are read-only.',
                ]);
            }
        }

        if (
            $originalStatus === FormTemplateStatus::Active->value
            && in_array('audience_type', $dirty, true)
        ) {
            throw ValidationException::withMessages([
                'audience_type' => 'Audience type cannot change after a form template is activated.',
            ]);
        }
    }

    public function assertActivationReady(FormTemplate $template): void
    {
        if (! $template->exists || ! $template->fields()->exists()) {
            throw ValidationException::withMessages([
                'fields' => 'A form template must contain at least one field before activation.',
            ]);
        }
    }

    public function transition(FormTemplate $template, FormTemplateStatus $target): FormTemplate
    {
        $from = $template->status instanceof \BackedEnum
            ? (string) $template->status->value
            : (string) $template->status;

        $this->assertTransition($from, $target->value);

        if ($target === FormTemplateStatus::Active) {
            $this->assertActivationReady($template);
        }

        $template->status = $target;
        $template->save();
        $this->audit->log(
            'form_template.status_changed',
            $template,
            ['status' => $from],
            ['status' => $target->value],
        );

        return $template->refresh();
    }

    public function bumpVersion(FormTemplate $template): void
    {
        FormTemplate::withoutEvents(function () use ($template): void {
            $template->newQuery()
                ->whereKey($template->getKey())
                ->increment('version');
        });

        $template->refresh();
    }
}
