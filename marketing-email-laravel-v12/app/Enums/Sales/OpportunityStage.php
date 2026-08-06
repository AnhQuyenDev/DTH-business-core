<?php

namespace App\Enums\Sales;

enum OpportunityStage: string
{
    case Discovery = 'discovery';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';
    case Cancelled = 'cancelled';

    public static function values(): array
    {
        return array_map(
            static fn (self $stage): string => $stage->value,
            self::cases(),
        );
    }

    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(
                static fn (self $stage): string => $stage->label(),
                self::cases(),
            ),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Discovery => __('enum.opportunity_stage.discovery'),
            self::Qualified => __('enum.opportunity_stage.qualified'),
            self::Proposal => __('enum.opportunity_stage.proposal'),
            self::Negotiation => __('enum.opportunity_stage.negotiation'),
            self::Won => __('enum.opportunity_stage.won'),
            self::Lost => __('enum.opportunity_stage.lost'),
            self::Cancelled => __('enum.opportunity_stage.cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Discovery => 'gray',
            self::Qualified => 'info',
            self::Proposal => 'warning',
            self::Negotiation => 'primary',
            self::Won => 'success',
            self::Lost => 'danger',
            self::Cancelled => 'gray',
        };
    }

    public function defaultProbability(): int
    {
        return match ($this) {
            self::Discovery => 20,
            self::Qualified => 50,
            self::Proposal => 70,
            self::Negotiation => 85,
            self::Won => 100,
            self::Lost, self::Cancelled => 0,
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [
            self::Discovery,
            self::Qualified,
            self::Proposal,
            self::Negotiation,
        ], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Won,
            self::Lost,
            self::Cancelled,
        ], true);
    }
}
