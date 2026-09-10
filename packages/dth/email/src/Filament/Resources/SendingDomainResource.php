<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\SendingDomainResource\Pages;
use Dth\Email\Filament\Support\FormHelp;
use Dth\Email\Models\SendingDomain;
use Dth\Email\Services\DomainVerificationService;
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
    protected static ?string $navigationLabel = 'Sending Domains';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Domain verification')
                    ->icon('heroicon-o-globe-alt')
                    ->description('Configure the sender domain and optional DKIM selector used for DNS verification.')
                    ->schema([
                        Forms\Components\TextInput::make('domain')
                            ->label('Domain')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('example.com')
                            ->afterLabel([
                                FormHelp::icon('Enter only the domain name. Do not include http://, https://, paths, or trailing slashes.'),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 6]),
                        Forms\Components\TextInput::make('dkim_selector')
                            ->label('DKIM selector')
                            ->maxLength(255)
                            ->placeholder('default')
                            ->afterLabel([
                                FormHelp::icon('The selector used to check selector._domainkey.domain. Leave blank if DKIM is not configured yet.'),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 6]),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
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
                Tables\Columns\TextColumn::make('domain')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('spf_status')->label('SPF')->badge(),
                Tables\Columns\TextColumn::make('dkim_status')->label('DKIM')->badge(),
                Tables\Columns\TextColumn::make('dmarc_status')->label('DMARC')->badge(),
                Tables\Columns\TextColumn::make('last_checked_at')->dateTime()->sortable(),
            ])
            ->actions([
                Actions\Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-shield-check')
                    ->requiresConfirmation()
                    ->action(function (SendingDomain $record, DomainVerificationService $service): void {
                        $result = $service->verify($record);

                        Notification::make()
                            ->title($result->isVerified() ? 'Domain verified' : 'Verification incomplete')
                            ->body("SPF: {$result->spfStatus}; DKIM: {$result->dkimStatus}; DMARC: {$result->dmarcStatus}")
                            ->status($result->isVerified() ? 'success' : 'warning')
                            ->send();
                    }),
                Actions\EditAction::make()
                    ->label('Edit')
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
