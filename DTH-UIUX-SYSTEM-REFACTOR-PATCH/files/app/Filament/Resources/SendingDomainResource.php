<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SendingDomainResource\Pages;
use App\Models\Marketing\SendingDomain;
use App\Support\Ui\BadgePalette;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SendingDomainResource extends Resource
{
    protected static ?string $model = SendingDomain::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.email_marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.sending_domain.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.sending_domain.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.sending_domain.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-at-symbol';

    protected static ?int $navigationSort = 60;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.manage-sending') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.domain_checklist'))->schema([
                TextInput::make('domain')->label(__('field.domain'))->required()->unique(ignoreRecord: true)->maxLength(255),
                Select::make('status')
                    ->label(__('field.status'))
                    ->options([
                        'unknown' => __('field.verification_unknown'),
                        'pending' => __('field.verification_pending'),
                        'verified' => __('field.verification_verified'),
                        'failed' => __('field.verification_failed'),
                    ])
                    ->default('unknown')
                    ->required(),
                Select::make('spf_status')
                    ->label(__('field.spf_status'))
                    ->options([
                        'unknown' => __('field.verification_unknown'),
                        'pending' => __('field.verification_pending'),
                        'verified' => __('field.verification_verified'),
                        'failed' => __('field.verification_failed'),
                    ])
                    ->default('unknown')
                    ->required(),
                Select::make('dkim_status')
                    ->label(__('field.dkim_status'))
                    ->options([
                        'unknown' => __('field.verification_unknown'),
                        'pending' => __('field.verification_pending'),
                        'verified' => __('field.verification_verified'),
                        'failed' => __('field.verification_failed'),
                    ])
                    ->default('unknown')
                    ->required(),
                Select::make('dmarc_status')
                    ->label(__('field.dmarc_status'))
                    ->options([
                        'unknown' => __('field.verification_unknown'),
                        'pending' => __('field.verification_pending'),
                        'verified' => __('field.verification_verified'),
                        'failed' => __('field.verification_failed'),
                    ])
                    ->default('unknown')
                    ->required(),
                Textarea::make('notes')->label(__('field.notes'))->rows(4)->columnSpanFull(),
                DateTimePicker::make('verified_at')->label(__('field.verified_at')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('domain')->label(__('field.domain'))->searchable()->sortable(),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn ($state): string => $state ? __('field.verification_'.(string) $state) : __('common.not_available'))
                ->color(fn ($state): string => BadgePalette::status($state, $state === 'unknown' ? 'gray' : null)),
            TextColumn::make('spf_status')->label(__('field.spf_status'))->badge()
                ->formatStateUsing(fn ($state): string => $state ? __('field.verification_'.(string) $state) : __('common.not_available'))
                ->color(fn ($state): string => BadgePalette::status($state, $state === 'unknown' ? 'gray' : null)),
            TextColumn::make('dkim_status')->label(__('field.dkim_status'))->badge()
                ->formatStateUsing(fn ($state): string => $state ? __('field.verification_'.(string) $state) : __('common.not_available'))
                ->color(fn ($state): string => BadgePalette::status($state, $state === 'unknown' ? 'gray' : null)),
            TextColumn::make('dmarc_status')->label(__('field.dmarc_status'))->badge()
                ->formatStateUsing(fn ($state): string => $state ? __('field.verification_'.(string) $state) : __('common.not_available'))
                ->color(fn ($state): string => BadgePalette::status($state, $state === 'unknown' ? 'gray' : null)),
            TextColumn::make('verified_at')->label(__('field.verified_at'))->dateTime('d/m/Y H:i')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSendingDomains::route('/'),
            'create' => Pages\CreateSendingDomain::route('/create'),
            'edit' => Pages\EditSendingDomain::route('/{record}/edit'),
        ];
    }
}
