<?php

namespace App\Filament\Resources;

use App\Enums\Marketing\SuppressionReason;
use App\Filament\Resources\SuppressionEntryResource\Pages;
use App\Models\Marketing\SuppressionEntry;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SuppressionEntryResource extends Resource
{
    protected static ?string $model = SuppressionEntry::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.email_marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.suppression_entry.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.suppression_entry.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.suppression_entry.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';

    protected static ?int $navigationSort = 70;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.view-suppression') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.suppression_entry'))->schema([
                TextInput::make('email')->label(__('field.email'))->email()->required()->maxLength(255),
                Select::make('reason')->label(__('field.reason'))
                    ->options([
                        'unsubscribe' => __('field.suppression_unsubscribe'),
                        'bounce' => __('field.suppression_bounce'),
                        'complaint' => __('field.suppression_complaint'),
                        'manual' => __('field.suppression_manual'),
                        'invalid_email' => __('field.suppression_invalid'),
                        'do_not_contact' => __('field.suppression_dnc'),
                    ])
                    ->required(),
                TextInput::make('source')->label(__('field.source'))->maxLength(255),
                TextInput::make('note')->label(__('field.note'))->maxLength(65535),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('email')->label(__('field.email'))->searchable()->sortable(),
            TextColumn::make('reason')->label(__('field.reason'))->badge()
                ->formatStateUsing(fn (string $state): string => SuppressionReason::tryFrom($state)?->label() ?? $state)
                ->color(fn ($state): string => SuppressionReason::tryFrom((string) $state)?->color() ?? 'gray'),
            TextColumn::make('source')->label(__('field.source'))->toggleable(),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime('d/m/Y H:i')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppressionEntries::route('/'),
            'create' => Pages\CreateSuppressionEntry::route('/create'),
            'edit' => Pages\EditSuppressionEntry::route('/{record}/edit'),
        ];
    }
}
