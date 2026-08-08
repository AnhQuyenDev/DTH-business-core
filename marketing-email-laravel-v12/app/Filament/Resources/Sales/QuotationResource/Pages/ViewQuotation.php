<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\Marketing\EmailTemplate;
use App\Services\Sales\QuotationApprovalService;
use App\Services\Sales\QuotationMailService;
use App\Services\Sales\QuotationPaymentService;
use App\Services\Sales\QuotationPdfService;
use App\Services\Sales\QuotationRevisionService;
use App\Services\Sales\QuotationTemplateRenderer;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;
use App\Enums\Sales\QuotationEmailStatus;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        $q = $this->record;

        return array_filter([
            Action::make('edit')->label(__('action.edit'))
                ->url(route('filament.admin.resources.sales.quotations.edit', $q))
                ->visible(fn () => auth()->user()?->can('update', $q) ?? false),

            Action::make('submit_approval')->label(__('action.submit_approval'))
                ->action(function () use ($q) {
                    app(QuotationApprovalService::class)->submitForApproval($q, auth()->user());
                    $this->redirect($this->getUrl(['record' => $this->record]));
                })
                ->visible(fn () => $q->status === QuotationStatus::Draft
                    && (auth()->user()?->can('update', $q) ?? false)),

            Action::make('approve')->label(__('action.approve'))
                ->action(function () use ($q) {
                    app(QuotationApprovalService::class)->approve($q, auth()->user());
                    $this->redirect($this->getUrl(['record' => $this->record]));
                })
                ->visible(fn () => $q->status === QuotationStatus::PendingApproval
                    && (auth()->user()?->can('approve', $q) ?? false)),

            Action::make('reject_approval')->label(__('action.reject_approval'))->color('danger')
                ->form([
                    Textarea::make('reason')
                        ->label(__('field.reason'))
                        ->required()
                        ->maxLength(1000),
                ])
                ->action(function (array $data) use ($q) {
                    app(QuotationApprovalService::class)->reject(
                        $q,
                        auth()->user(),
                        $data['reason'],
                    );
                    $this->redirect($this->getUrl(['record' => $this->record]));
                })
                ->visible(fn () => $q->status === QuotationStatus::PendingApproval
                    && (auth()->user()?->can('approve', $q) ?? false)),

            Action::make('send')->label(__('action.send'))
                ->form(function () use ($q): array {
                    $mailService = app(QuotationMailService::class);
                    $signers = $mailService
                        ->authorizedSignerContacts($q)
                        ->mapWithKeys(fn ($contact): array => [
                            mb_strtolower(trim((string) $contact->email)) => sprintf(
                                '%s — %s',
                                $contact->full_name ?: ('Contact #'.$contact->id),
                                $contact->email,
                            ),
                        ])
                        ->all();
                    $partyEmail = mb_strtolower(trim((string) ($q->party_email ?? '')));
                    if ($signers === [] && $partyEmail !== '') {
                        $signers[$partyEmail] = collect([
                            $q->party_contact_name ?: $q->party_display_name,
                            $q->party_email,
                        ])->filter()->implode(' — ');
                    }

                    $recipientLabel = collect([
                        $q->party_contact_name,
                        $q->party_display_name,
                    ])->filter()->unique()->implode(' — ');

                    $templateQuery = EmailTemplate::query()
                        ->whereHas(
                            'categoryRelation',
                            fn ($query) => $query->where('slug', 'quotation')
                        )
                        ->where('status', 'active')
                        ->whereNotNull('subject')
                        ->where('subject', '!=', '')
                        ->whereNotNull('html_body')
                        ->where('html_body', '!=', '')
                        ->whereRaw('LOWER(name) <> ?', ['meta'])
                        ->orderBy('name');

                    $defaultTemplate = (clone $templateQuery)->first();
                    $defaultSubject = sprintf(
                        '[%s] %s',
                        $q->quotation_code,
                        $q->title,
                    );
                    $defaultBody = view('sales.emails.quotation-sent', [
                        'quotation' => $q,
                        'publicUrl' => route('sales.quotation.public.show', [
                            'quotationCode' => $q->quotation_code,
                            'token' => $q->public_token,
                        ]),
                    ])->render();

                    if ($defaultTemplate !== null) {
                        $renderedDefault = app(QuotationTemplateRenderer::class)
                            ->render($defaultTemplate, $q);
                        $defaultSubject = $renderedDefault['subject'];
                        $defaultBody = $renderedDefault['body'];
                    }

                    return [
                        TextInput::make('party_name')
                            ->label(__('field.quotation_recipient'))
                            ->default($recipientLabel)
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('recipient_email')
                            ->label(__('field.recipient_email'))
                            ->email()
                            ->required()
                            ->default($q->party_email)
                            ->disabled()
                            ->dehydrated(),

                        Select::make('authorized_signer_email')
                            ->label('Người được phép xác nhận báo giá')
                            ->options($signers)
                            ->default($partyEmail)
                            ->required()
                            ->searchable()
                            ->helperText(
                                'Mã OTP và quyền Chấp nhận/Từ chối/Yêu cầu sửa báo giá chỉ áp dụng cho email này.'
                            ),

                        Select::make('template_id')
                            ->label(__('field.email_template'))
                            ->options((clone $templateQuery)->pluck('name', 'id')->all())
                            ->default($defaultTemplate?->id)
                            ->searchable()
                            ->preload()
                            ->helperText(__('field.email_template_helper'))
                            ->live()
                            ->afterStateUpdated(function (
                                Set $set,
                                ?string $state,
                            ) use ($q): void {
                                if (! $state) {
                                    return;
                                }

                                $template = EmailTemplate::find($state);
                                if (! $template) {
                                    return;
                                }

                                $rendered = app(QuotationTemplateRenderer::class)
                                    ->render($template, $q);

                                $set('subject', $rendered['subject']);
                                $set('body', $rendered['body']);
                            }),

                        TextInput::make('subject')
                            ->label(__('field.subject'))
                            ->required()
                            ->maxLength(255)
                            ->default($defaultSubject),

                        Hidden::make('body')
                            ->default($defaultBody),

                        Placeholder::make('body_preview')
                            ->label('Xem trước nội dung email')
                            ->content(function (Get $get): HtmlString {
                                $body = (string) ($get('body') ?? '');

                                if ($body === '') {
                                    return new HtmlString(
                                        '<div style="padding:12px;border:1px solid #374151;border-radius:8px">'
                                        .'</div>'
                                    );
                                }

                                return new HtmlString(
                                    '<div style="max-height:420px;overflow:auto;padding:16px;background:#fff;color:#111827;border-radius:8px;border:1px solid #d1d5db">'
                                    .$body
                                    .'</div>'
                                );
                            }),
                    ];
                })
                ->action(function (array $data) use ($q): void {
                    $log = app(QuotationMailService::class)->send(
                        quotation: $q,
                        user: auth()->user(),
                        recipientEmail: (string) $data['recipient_email'],
                        options: [
                            'subject' => $data['subject'] ?? null,
                            'body' => $data['body'] ?? null,
                            'sending_account_id' => $data['sending_account_id'] ?? null,
                        ],
                    );

                    $this->record->refresh();

                    if ($log->status === QuotationEmailStatus::Failed) {
                        Notification::make()
                            ->danger()
                            ->title('Gửi báo giá thất bại')
                            ->body($log->error_message ?: 'Không thể gửi email bằng tài khoản gửi đã cấu hình.')
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title(
                            $log->status === QuotationEmailStatus::Sent
                                ? 'Đã gửi báo giá'
                                : 'Báo giá đã được đưa vào hàng đợi gửi email'
                        )
                        ->body('Từ: '.($log->sender_email ?: '—').' → '.$log->recipient_email)
                        ->send();
                })
                ->visible(fn () => filled($q->party_email)
                    && (auth()->user()?->can('send', $q) ?? false)),

            Action::make('create_revision')
                ->label('Tạo phiên bản chỉnh sửa')
                ->icon('heroicon-o-document-duplicate')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Tạo phiên bản báo giá mới')
                ->modalDescription('Phiên bản hiện tại sẽ được đánh dấu là Đã thay thế. Phiên bản mới quay về Nháp và phải phê duyệt lại trước khi gửi.')
                ->action(function () use ($q): void {
                    $revision = app(QuotationRevisionService::class)
                        ->createRevision($q, auth()->user());

                    $this->redirect(
                        QuotationResource::getUrl('edit', [
                            'record' => $revision,
                        ])
                    );
                })
                ->visible(
                    fn (): bool => auth()->user()?->can('revise', $q) ?? false
                ),

            Action::make('generate_pdf')->label(__('action.generate_pdf'))
                ->action(function () use ($q) {
                    app(QuotationPdfService::class)->regenerate($q);
                    $this->redirect($this->getUrl(['record' => $this->record]));
                })
                // After Sent the customer-facing PDF is immutable. Public PDF
                // downloads always serve the exact document generated for the
                // outbound email, so never create a newer PDF after sending.
                ->visible(fn (): bool => in_array(
                    $q->status,
                    [
                        QuotationStatus::Draft,
                        QuotationStatus::PendingApproval,
                        QuotationStatus::Approved,
                    ],
                    true,
                ) && (
                    (auth()->user()?->can('update', $q) ?? false)
                    || (auth()->user()?->can('approve', $q) ?? false)
                )),

            Action::make('cancel')->label(__('action.cancel'))->color('danger')
                ->visible(fn () => auth()->user()?->can('cancel', $q) ?? false)
                ->action(function () use ($q) {
                    app(QuotationApprovalService::class)->logCancellation($q, auth()->user());
                    $this->redirect($this->getUrl(['record' => $this->record]));
                }),

            Action::make('public_link')->label(__('action.public_link'))
                ->url(route('sales.quotation.public.show', [
                    'quotationCode' => $q->quotation_code,
                    'token' => $q->public_token,
                ]), shouldOpenInNewTab: true)
                ->visible(fn (): bool => in_array(
                    $q->status,
                    [
                        QuotationStatus::Sent,
                        QuotationStatus::Viewed,
                        QuotationStatus::Accepted,
                        QuotationStatus::Rejected,
                        QuotationStatus::RevisionRequested,
                        QuotationStatus::Expired,
                    ],
                    true,
                )),

            Action::make('confirm_payment')
                ->label(__('action.confirm_payment'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('action.confirm_payment'))
                ->modalDescription(__('payment.confirm_conversion_warning'))
                ->form([
                    Textarea::make('payment_note')
                        ->label(__('field.payment_note'))
                        ->rows(4)
                        ->required()
                        ->maxLength(2000),
                ])
                ->action(function (array $data) use ($q): void {
                    app(QuotationPaymentService::class)->updateStatus(
                        quotation: $q,
                        newStatus: PaymentStatus::Paid,
                        user: auth()->user(),
                        note: $data['payment_note'],
                    );

                    Notification::make()
                        ->success()
                        ->title(__('notification.payment_confirmed'))
                        ->body(__('notification.customer_created_from_payment'))
                        ->send();

                    $this->record->refresh();

                    $this->redirect(
                        static::getResource()::getUrl('view', [
                            'record' => $this->record,
                        ])
                    );
                })
                ->visible(function () use ($q): bool {
                    $paymentStatus = $q->payment_status?->value
                        ?? (string) $q->payment_status;

                    return (auth()->user()?->can('verifyPayment', $q) ?? false)
                        && $q->status === QuotationStatus::Accepted
                        && $paymentStatus !== PaymentStatus::Paid->value;
                }),
        ]);
    }
}
