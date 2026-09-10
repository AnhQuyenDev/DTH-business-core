<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\SendingDomainResource\Pages;
use Dth\Email\Filament\Support\FormHelp;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\SendingDomain;
use Dth\Email\Services\DomainVerificationService;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SendingDomainResource extends Resource
{
    protected static ?string $model = SendingDomain::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static string|\UnitEnum|null $navigationGroup = EmailNavigationGroup::Email;
    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.sending_domains', 'Sending Domains', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.sending_domain', 'Sending Domain', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.sending_domains', 'Sending Domains', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make(UiText::get('domain.section', 'Domain verification'))
                    ->icon('heroicon-o-globe-alt')
                    ->description(UiText::get(
                        'domain.section_description',
                        'Configure the sender domain and optional DKIM selector used for DNS verification.'
                    ))
                    ->schema([
                        Forms\Components\TextInput::make('domain')
                            ->label(UiText::get('domain.domain', 'Domain'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder('example.com')
                            ->afterLabel([
                                FormHelp::icon(UiText::get(
                                    'domain.domain_help',
                                    'Enter only the domain name. Do not include http://, https://, paths, or trailing slashes.'
                                )),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 6]),
                        Forms\Components\TextInput::make('dkim_selector')
                            ->label(UiText::get('domain.dkim_selector', 'DKIM selector'))
                            ->maxLength(255)
                            ->placeholder('default')
                            ->afterLabel([
                                FormHelp::icon(UiText::get(
                                    'domain.dkim_help',
                                    'The selector used to check selector._domainkey.domain. Leave blank if DKIM is not configured yet.'
                                )),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 6]),
                        Forms\Components\Textarea::make('notes')
                            ->label(UiText::get('domain.notes', 'Notes'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 12])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')
                    ->label(UiText::get('domain.domain', 'Domain'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('spf_status')
                    ->label('SPF')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                Tables\Columns\TextColumn::make('dkim_status')
                    ->label('DKIM')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                Tables\Columns\TextColumn::make('dmarc_status')
                    ->label('DMARC')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->label(UiText::get('domain.last_checked', 'Last checked'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Actions\Action::make('verify')
                    ->label(UiText::get('domain.verify', 'Verify'))
                    ->icon('heroicon-o-shield-check')
                    ->requiresConfirmation()
                    ->action(function (SendingDomain $record, DomainVerificationService $service): void {
                        $result = $service->verify($record);

                        Notification::make()
                            ->title($result->isVerified()
                                ? UiText::get('domain.verified_title', 'Domain verified')
                                : UiText::get('domain.incomplete_title', 'Verification incomplete'))
                            ->body(UiText::get(
                                'domain.verification_body',
                                'SPF: :spf; DKIM: :dkim; DMARC: :dmarc',
                                [
                                    'spf' => UiText::status($result->spfStatus),
                                    'dkim' => UiText::status($result->dkimStatus),
                                    'dmarc' => UiText::status($result->dmarcStatus),
                                ]
                            ))
                            ->status($result->isVerified() ? 'success' : 'warning')
                            ->send();
                    }),
                Actions\EditAction::make()
                    ->label(UiText::get('common.actions.edit', 'Edit'))
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
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
