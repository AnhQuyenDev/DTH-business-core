<?php

namespace App\Enums\Sales;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case RevisionRequested = 'revision_requested';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';

    public static function values(): array
    {
        return array_map(static fn (self $v) => $v->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $v) => $v->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('enum.sales.quotation_status.draft'),
            self::PendingApproval => __('enum.sales.quotation_status.pending_approval'),
            self::Approved => __('enum.sales.quotation_status.approved'),
            self::Sent => __('enum.sales.quotation_status.sent'),
            self::Viewed => __('enum.sales.quotation_status.viewed'),
            self::Accepted => __('enum.sales.quotation_status.accepted'),
            self::Rejected => __('enum.sales.quotation_status.rejected'),
            self::RevisionRequested => __('enum.sales.quotation_status.revision_requested'),
            self::Expired => __('enum.sales.quotation_status.expired'),
            self::Cancelled => __('enum.sales.quotation_status.cancelled'),
            self::Superseded => __('enum.sales.quotation_status.superseded'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingApproval => 'warning',
            self::Approved => 'success',
            self::Sent => 'info',
            self::Viewed => 'primary',
            self::Accepted => 'success',
            self::Rejected, self::Expired, self::Cancelled, self::Superseded => 'danger',
            self::RevisionRequested => 'warning',
        };
    }

    public function canSend(): bool
    {
        return in_array($this, [self::Approved]);
    }

    public function canConfirm(): bool
    {
        return in_array($this, [self::Sent, self::Viewed]);
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::PendingApproval, self::RevisionRequested]);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Accepted, self::Rejected, self::Expired, self::Cancelled, self::Superseded]);
    }

    public static function terminal(): array
    {
        return [self::Accepted, self::Rejected, self::Expired, self::Cancelled, self::Superseded];
    }

    public static function nonTerminal(): array
    {
        return [self::Draft, self::PendingApproval, self::Approved, self::Sent, self::Viewed, self::RevisionRequested];
    }
}
