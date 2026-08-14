<?php

namespace App\Filament\Pages;

use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerLifecycleStage;
use App\Filament\Resources\CustomerResource;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\CustomerInteraction;
use App\Models\Sales\Quotation;
use App\Services\Crm\CustomerCareEmailService;
use App\Services\Crm\CustomerCareService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

class CustomerCarePage extends Page implements HasTable
{
    use InteractsWithTable, WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.customer-care';

    public ?int $selectedCustomerId = null;

    public string $activeTab = 'overview';

    public string $emailTo = '';

    public string $emailCc = '';

    public string $emailBcc = '';

    public string $emailSubject = '';

    public string $emailBody = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $emailAttachments = [];

    public string $callType = 'call';

    public string $callStatus = 'completed';

    public string $callContent = '';

    public string $callOutcome = '';


    public ?string $callNextFollowUp = null;

    public ?string $releaseReason = null;

    public string $releaseNote = '';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.customer_care');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.customer_care');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('customer-care.view') ?? false;
    }

    public function getTitle(): string
    {
        return __('page.title.customer_care');
    }

    public function getCustomerCounts(): array
    {
        $user = auth()->user();
        $staff = $user->staff;

        if (! $staff && ! ($user->isAdmin() || $user->canReadAcrossBusiness())) {
            return ['total' => 0, 'needs_follow_up' => 0];
        }

        $assignedIds = $this->getAccessibleCustomerIds();

        return [
            'total' => count($assignedIds),
            'needs_follow_up' => CustomerInteraction::whereIn('customer_id', $assignedIds)
                ->where('status', 'scheduled')
                ->whereDate('next_follow_up_at', '<=', now())
                ->distinct('customer_id')->count('customer_id'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getBaseQuery())
            ->columns([
                TextColumn::make('display_name')
                    ->label(__('field.customer_name'))
                    ->searchable(['display_name', 'customer_code', 'email', 'phone'])
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (Customer $record): ?string => $record->customer_code),
                TextColumn::make('customer_type')
                    ->label(__('field.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'personal' => __('field.type.personal'),
                        'business' => __('field.type.business'),
                        default => $state ?? '—',
                    })
                    ->color(fn ($state): string => \App\Support\Ui\BadgePalette::audience((string) $state)),
                TextColumn::make('email')
                    ->label(__('field.email'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('field.phone'))
                    ->searchable(),
                TextColumn::make('priority')
                    ->label(__('field.priority'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? __('field.priority.'.$state) : '—')
                    ->color(fn (?string $state): string => \App\Support\Ui\BadgePalette::managed(
                        'crm.customer_priority',
                        $state,
                        match ($state) {
                            'vip' => 'danger',
                            'high' => 'warning',
                            'normal' => 'info',
                            default => 'gray',
                        },
                    )),
                TextColumn::make('lifecycle_stage')
                    ->label(__('field.lifecycle_stage'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? CustomerLifecycleStage::tryFrom($state)?->label() ?? $state
                        : __('common.not_available'))
                    ->color(fn (?string $state): string => CustomerLifecycleStage::tryFrom((string) $state)?->color() ?? 'gray'),
                TextColumn::make('next_follow_up_at')
                    ->label(__('field.next_follow_up'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder(__('common.not_available'))
                    ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'success')
                    ->description(fn ($state): ?string => $state && $state->isPast() ? __('page.customer_care.overdue') : null),
            ])
            ->defaultSort('next_follow_up_at', 'asc')
            ->filters([
                SelectFilter::make('lifecycle_stage')
                    ->label(__('field.lifecycle_stage'))
                    ->options(CustomerLifecycleStage::options()),
                SelectFilter::make('priority')
                    ->label(__('field.priority'))
                    ->options([
                        'low' => __('field.priority.low'),
                        'normal' => __('field.priority.normal'),
                        'high' => __('field.priority.high'),
                        'vip' => __('field.priority.vip'),
                    ]),
            ])
            ->actions([
                Action::make('open_care')
                    ->label(__('action.care'))
                    ->icon('heroicon-m-heart')
                    ->color('primary')
                    ->action(fn (Customer $record) => $this->openCareWorkspace($record)),
                ViewAction::make()
                    ->url(fn (Customer $record): string => CustomerResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading(__('page.customer_care.no_customers'));
    }

    public function openCareWorkspace(Customer $record): void
    {
        abort_unless(
            auth()->user()?->can('view', $record),
            403
        );

        $this->selectedCustomerId = $record->id;
        $this->activeTab = 'overview';
        $this->emailTo = $record->email ?? '';
        $this->emailCc = '';
        $this->emailBcc = '';
        $this->emailSubject = '';
        $this->emailBody = '';
        $this->emailAttachments = [];
        $this->callType = 'call';
        $this->callStatus = 'completed';
        $this->callContent = '';
        $this->callOutcome = '';
        $this->callNextFollowUp = null;
        $this->releaseReason = null;
        $this->releaseNote = '';
        $this->dispatch('open-modal', id: 'customer-care-workspace');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function removeAttachment(int $index): void
    {
        unset($this->emailAttachments[$index]);
        $this->emailAttachments = array_values($this->emailAttachments);
    }

    public function sendEmail(): void
    {
        $customer = $this->getSelectedCustomer();

        if (! $customer) {
            return;
        }

        abort_unless(
            auth()->user()?->can('interact', $customer),
            403
        );

        $this->validate([
            'emailTo' => ['required', 'string'],
            'emailSubject' => ['required', 'string', 'max:255'],
            'emailBody' => ['required', 'string'],
            'emailAttachments.*' => ['file', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,csv,txt,zip,mp3,mp4,mov', 'max:20480'],
        ]);

        $to = $this->parseEmails($this->emailTo);
        $cc = $this->parseEmails($this->emailCc);
        $bcc = $this->parseEmails($this->emailBcc);

        if (empty($to)) {
            Notification::make()
                ->danger()
                ->title(__('page.customer_care.email_invalid_recipient'))
                ->send();

            return;
        }

        $attachments = [];
        foreach ($this->emailAttachments as $upload) {
            $attachments[] = [
                'disk' => 'public',
                'path' => $upload->store('care-emails', 'public'),
                'name' => $upload->getClientOriginalName(),
                'mime' => $upload->getMimeType(),
            ];
        }

        try {
            app(CustomerCareEmailService::class)->send(
                customer: $customer,
                subject: $this->emailSubject,
                htmlBody: $this->emailBody,
                staffId: auth()->user()?->staff?->id,
                to: $to,
                cc: $cc,
                bcc: $bcc,
                attachments: $attachments,
            );

            $this->emailTo = $customer->email ?? '';
            $this->emailCc = '';
            $this->emailBcc = '';
            $this->emailSubject = '';
            $this->emailBody = '';
            $this->emailAttachments = [];
            $this->activeTab = 'email';

            Notification::make()
                ->success()
                ->title(__('page.customer_care.email_sent_title'))
                ->body(__('page.customer_care.email_sent_body'))
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->danger()
                ->title(__('page.customer_care.email_failed_title'))
                ->body($e->getMessage())
                ->send();
        }
    }

    private function parseEmails(string $value): array
    {
        return collect(preg_split('/[,;]/', $value))
            ->map(fn (string $part): string => strtolower(trim($part)))
            ->filter(fn (string $part): bool => filled($part) && filter_var($part, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }

    public function logCall(): void
    {
        $customer = $this->getSelectedCustomer();

        if (! $customer) {
            return;
        }

        abort_unless(
            auth()->user()?->can('interact', $customer),
            403
        );

        $this->validate([
            'callType' => ['required', 'in:call,message'],
            'callStatus' => ['required', 'string'],
            'callContent' => ['required', 'string'],
        ]);

        CustomerInteraction::create([
            'customer_id' => $customer->id,
            'staff_id' => auth()->user()?->staff?->id,
            'interaction_type' => $this->callType,
            'subject' => $this->callType === 'call'
                ? __('page.customer_care.call_subject')
                : __('page.customer_care.message_subject'),
            'content' => $this->callContent,
            'outcome' => $this->callOutcome,
            'status' => $this->callStatus,
            'interaction_at' => now(),
            'next_follow_up_at' => filled($this->callNextFollowUp) ? $this->callNextFollowUp : null,
        ]);

        $this->callContent = '';
        $this->callOutcome = '';
        $this->callNextFollowUp = null;

        Notification::make()
            ->success()
            ->title(__('page.customer_care.call_logged_title'))
            ->send();
    }

    public function releaseCustomer(): void
    {
        $user = auth()->user();
        $staff = $user?->staff;

        if (! $staff || ! $this->selectedCustomerId) {
            return;
        }

        $this->validate([
            'releaseReason' => ['required', 'string'],
        ]);

        $assignment = CustomerAssignment::query()
            ->where('customer_id', $this->selectedCustomerId)
            ->where('staff_id', $staff->id)
            ->where('status', CustomerAssignmentStatus::Active->value)
            ->first();

        if (! $assignment) {
            return;
        }

        $assignment->update([
            'status' => CustomerAssignmentStatus::Ended->value,
            'ends_at' => now(),
            'ended_at' => now(),
            'ended_by_user_id' => $user->id,
            'note' => filled($this->releaseNote) ? $this->releaseNote : __('enum.assignment_reason.'.$this->releaseReason),
        ]);

        $this->releaseReason = null;
        $this->releaseNote = '';

        $this->dispatch('close-modal', id: 'release-customer-modal');
        $this->dispatch('close-modal', id: 'customer-care-workspace');

        $this->selectedCustomerId = null;

        Notification::make()
            ->success()
            ->title(__('page.customer_care.released_title'))
            ->send();
    }

    public function canInteractSelectedCustomer(): bool
    {
        $customer = $this->getSelectedCustomer();

        return $customer !== null
            && (auth()->user()?->can('interact', $customer) ?? false);
    }

    public function canRelease(): bool
    {
        $user = auth()->user();

        if (! $user || $user->isAdmin() || $user->isMarketingManager() || $user->isCustomerServiceManager()) {
            return false;
        }

        $staff = $user->staff;

        if (! $staff || ! $this->selectedCustomerId) {
            return false;
        }

        return CustomerAssignment::query()
            ->where('customer_id', $this->selectedCustomerId)
            ->where('staff_id', $staff->id)
            ->where('status', CustomerAssignmentStatus::Active->value)
            ->exists();
    }

    public function getSelectedCustomer(): ?Customer
    {
        if (! $this->selectedCustomerId) {
            return null;
        }

        return $this->getBaseQuery()
            ->with(['tags', 'assignments.staff', 'currentOwner'])
            ->find($this->selectedCustomerId);
    }

    public function getStats(): array
    {
        $customer = $this->getSelectedCustomer();

        return $customer ? app(CustomerCareService::class)->stats($customer) : [];
    }

    public function getTimeline(): Collection
    {
        $customer = $this->getSelectedCustomer();

        return $customer ? app(CustomerCareService::class)->timeline($customer) : collect();
    }

    public function getEmailThread(): Collection
    {
        $customer = $this->getSelectedCustomer();

        return $customer ? app(CustomerCareService::class)->emailThread($customer) : collect();
    }

    public function getComposerSender(): array
    {
        return app(CustomerCareEmailService::class)->getSenderInfo(auth()->user()?->staff?->id);
    }

    public function getEmailSummary(): array
    {
        $customer = $this->getSelectedCustomer();

        return $customer ? app(CustomerCareService::class)->emailSummary($customer) : [];
    }

    public function getQuotations(): Collection
    {
        if (! $this->selectedCustomerId) {
            return collect();
        }

        return Quotation::query()
            ->where('customer_id', $this->selectedCustomerId)
            ->orderByDesc('created_at')
            ->get();
    }

    private function getBaseQuery(): Builder
    {
        $user = auth()->user();

        if (
            $user->isAdmin()
            || $user->canReadAcrossBusiness()
            || $user->isCustomerServiceManager()
        ) {
            return Customer::query();
        }

        if (! $user->isCustomerServiceStaff()) {
            return Customer::query()->whereRaw('0 = 1');
        }

        $staff = $user->staff;

        if (! $staff) {
            return Customer::query()->whereRaw('0 = 1');
        }

        $assignedIds = CustomerAssignment::where('staff_id', $staff->id)
            ->where('status', 'active')
            ->pluck('customer_id');

        return Customer::query()->whereIn('id', $assignedIds);
    }

    private function getAccessibleCustomerIds(): array
    {
        return $this->getBaseQuery()->pluck('id')->toArray();
    }
}
