<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Enums\Sales\QuotationStatus;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationApprovalService;
use App\Services\Sales\QuotationMailService;
use App\Services\Sales\QuotationPdfService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Set;
use Filament\Resources\Pages\ViewRecord;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        $q = $this->record;

        return array_filter([
            Action::make('edit')->label(__('action.edit'))
                ->url(route('filament.admin.resources.sales.quotations.edit', $q))
                ->visible(fn () => $q->status->isEditable() && auth()->user()->can('sales.create-quotations')),

            Action::make('submit_approval')->label(__('action.submit_approval'))
                ->action(function () use ($q) {
                    app(QuotationApprovalService::class)->submitForApproval($q, auth()->user());
                    $this->redirect($this->getUrl(['record' => $this->record]));
                })
                ->visible(fn () => $q->status === QuotationStatus::Draft && auth()->user()->can('sales.create-quotations')),

            Action::make('approve')->label(__('action.approve'))
                ->action(function () use ($q) {
                    app(QuotationApprovalService::class)->approve($q, auth()->user());
                    $this->redirect($this->getUrl(['record' => $this->record]));
                })
                ->visible(fn () => $q->status === QuotationStatus::PendingApproval && auth()->user()->can('sales.approve-quotations')),

            Action::make('reject_approval')->label(__('action.reject_approval'))->color('danger')
                ->form([Textarea::make('reason')->required()])
                ->action(function (array $data) use ($q) {
                    app(QuotationApprovalService::class)->reject($q, auth()->user(), $data['reason']);
                    $this->redirect($this->getUrl(['record' => $this->record]));
                })
                ->visible(fn () => $q->status === QuotationStatus::PendingApproval && auth()->user()->can('sales.approve-quotations')),

            Action::make('send')->label(__('action.send'))
                ->form([
                    Select::make('customer_id')
                        ->label(__('field.customer'))
                        ->options(\App\Models\Crm\Customer::query()->orderBy('display_name')->pluck('display_name', 'id'))
                        ->searchable()
                        ->preload()
                        ->default($q->customer_id)
                        ->live()
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('recipient_email', \App\Models\Crm\Customer::find($state)?->email)),
                    TextInput::make('recipient_email')
                        ->label(__('field.recipient_email'))
                        ->email()
                        ->required()
                        ->default($q->customer?->email),
                    Select::make('template_id')
                        ->label(__('field.email_template'))
                        ->options(\App\Models\Marketing\EmailTemplate::query()->whereHas('categoryRelation', fn ($q) => $q->where('slug', 'quotation'))->where('status', 'active')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->helperText(__('field.email_template_helper'))
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) use ($q) {
                            if (!$state) {
                                return;
                            }
                            $template = \App\Models\Marketing\EmailTemplate::find($state);
                            if (!$template) {
                                return;
                            }
                            $rendered = app(\App\Services\Sales\QuotationTemplateRenderer::class)->render($template, $q);
                            $set('subject', $rendered['subject']);
                            $set('body', $rendered['body']);
                        }),
                    TextInput::make('subject')
                        ->label(__('field.subject'))
                        ->default(fn () => sprintf('[%s] %s', $q->quotation_code, $q->title)),
                    Textarea::make('body')
                        ->label(__('field.body'))
                        ->rows(12),
                ])
                ->action(function (array $data) use ($q) {
                    app(QuotationMailService::class)->send($q, auth()->user(), $data['recipient_email'], [
                        'subject' => $data['subject'] ?? null,
                        'body' => $data['body'] ?? null,
                    ]);
                    $this->redirect($this->getUrl(['record' => $this->record]));
                })
                ->visible(fn () => $q->status->canSend() && auth()->user()->can('sales.send-quotations')),

            Action::make('generate_pdf')->label(__('action.generate_pdf'))
                ->action(function () use ($q) {
                    app(QuotationPdfService::class)->generate($q);
                    $this->redirect($this->getUrl(['record' => $this->record]));
                })
                ->visible(fn () => !$q->status->isTerminal()),

            Action::make('cancel')->label(__('action.cancel'))->color('danger')
                ->visible(fn () => !$q->status->isTerminal() && auth()->user()->can('sales.cancel-quotations'))
                ->action(function () use ($q) {
                    app(QuotationApprovalService::class)->logCancellation($q, auth()->user());
                    $this->redirect($this->getUrl(['record' => $this->record]));
                }),

            Action::make('public_link')->label(__('action.public_link'))
                ->url(route('sales.quotation.public.show', [
                    'quotationCode' => $q->quotation_code,
                    'token' => $q->public_token,
                ]), shouldOpenInNewTab: true),
        ]);
    }
}
