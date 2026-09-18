<?php

namespace Dth\AccountManagement\Filament\Resources;

use Dth\AccountManagement\Filament\Navigation\AccountManagementNavigationGroup;
use Dth\AccountManagement\Filament\Resources\AccessSettingResource\Pages;
use Dth\AccountManagement\Models\AccountSetting;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AccessSettingResource extends Resource
{
    protected static ?string $model = AccountSetting::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static string|\UnitEnum|null $navigationGroup = AccountManagementNavigationGroup::Accounts;
    protected static ?int $navigationSort = 70;
    protected static ?string $slug = 'account-settings';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.settings', 'Cấu hình truy cập', context: 'navigation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.setting', 'Cấu hình hệ thống'))
                ->icon('heroicon-o-adjustments-horizontal')
                ->schema([
                    TextInput::make('key')
                        ->label(UiText::get('fields.setting_key', 'Khóa cấu hình'))
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('type')
                        ->label(UiText::get('fields.setting_type', 'Kiểu dữ liệu'))
                        ->options([
                            'boolean' => UiText::get('setting_types.boolean', 'Bật / Tắt'),
                            'integer' => UiText::get('setting_types.integer', 'Số nguyên'),
                            'string' => UiText::get('setting_types.string', 'Văn bản'),
                            'json' => UiText::get('setting_types.json', 'JSON'),
                        ])
                        ->disabled()
                        ->dehydrated(false),
                    Textarea::make('value')
                        ->label(UiText::get('fields.setting_value', 'Giá trị'))
                        ->formatStateUsing(function ($state): string {
                            if (is_bool($state)) {
                                return $state
                                    ? UiText::get('common.boolean.on', 'Bật')
                                    : UiText::get('common.boolean.off', 'Tắt');
                            }

                            return is_scalar($state)
                                ? (string) $state
                                : json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        })
                        ->dehydrateStateUsing(function ($state, $record) {
                            $type = $record?->type ?? 'string';
                            $normalized = mb_strtolower(trim((string) $state));

                            return match ($type) {
                                'boolean' => in_array($normalized, ['1', 'true', 'yes', 'on', 'bật', 'bat'], true),
                                'integer' => (int) $state,
                                'json' => json_decode((string) $state, true) ?? [],
                                'string' => (string) $state,
                                default => $state,
                            };
                        })
                        ->rows(3)
                        ->required()
                        ->helperText(UiText::get('fields.setting_value_help', 'Giá trị phải đúng định dạng với kiểu dữ liệu đã chọn ở trên.')),
                    Textarea::make('description')
                        ->label(UiText::get('common.fields.description', 'Mô tả'))
                        ->rows(3)
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label(UiText::get('fields.setting', 'Cấu hình'))
                    ->searchable(),
                TextColumn::make('value')
                    ->label(UiText::get('fields.setting_value', 'Giá trị'))
                    ->formatStateUsing(function ($state): string {
                        if (is_bool($state)) {
                            return $state
                                ? UiText::get('common.boolean.on', 'Bật')
                                : UiText::get('common.boolean.off', 'Tắt');
                        }

                        return is_scalar($state)
                            ? (string) $state
                            : json_encode($state, JSON_UNESCAPED_UNICODE);
                    })
                    ->badge(),
                TextColumn::make('description')
                    ->label(UiText::get('common.fields.description', 'Mô tả'))
                    ->wrap(),
            ])
            ->recordActions([
                Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Chỉnh sửa')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccessSettings::route('/'),
            'edit' => Pages\EditAccessSetting::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return app(AccountAuthorization::class)->allows('accounts.settings.manage');
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return app(AccountAuthorization::class)->allows('accounts.settings.manage'); }
    public static function canDelete(Model $record): bool { return false; }
}
