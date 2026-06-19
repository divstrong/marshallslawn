<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\ChecksResourceAccess;
use Filament\Actions;

class ServiceResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = Service::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string | \UnitEnum | null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('code')
                ->maxLength(255),
            Forms\Components\TextInput::make('parent_service')
                ->maxLength(255),
            Forms\Components\TextInput::make('full_name')
                ->maxLength(255),
            Forms\Components\TextInput::make('category')
                ->maxLength(255),
            Forms\Components\TextInput::make('default_price')
                ->label('Default Rate')
                ->numeric()
                ->prefix('$'),
            Forms\Components\TextInput::make('minimum_amount')
                ->numeric()
                ->prefix('$'),
            Forms\Components\TextInput::make('unit')
                ->maxLength(255),
            Forms\Components\TextInput::make('service_mode')
                ->maxLength(255),
            Forms\Components\Select::make('service_group')
                ->label('Service Group')
                ->helperText('Drives the Spraying / Mowing / Mulching filters on the Dispatch board.')
                ->options(Service::GROUPS)
                ->native(false),
            Forms\Components\FileUpload::make('icon_path')
                ->label('Icon')
                ->helperText('Optional. Shown on Dispatch job cards & map pins when "Service Icons" is enabled.')
                ->image()
                ->disk('public')
                ->directory('service-icons')
                ->imageResizeMode('contain')
                ->imageResizeTargetWidth(128)
                ->imageResizeTargetHeight(128)
                ->maxSize(2048),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
            Forms\Components\Toggle::make('track_chemicals')
                ->default(false),
            Forms\Components\Toggle::make('show_in_snow')
                ->default(false),
            Forms\Components\Textarea::make('invoice_description')
                ->label('Invoice Terms')
                ->helperText('Qualifier text shown to the customer on invoices for this service.')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('estimate_description')
                ->label('Estimate Terms')
                ->helperText('Qualifier text shown to the customer on estimates for this service.')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('description')
                ->label('Internal Description')
                ->helperText('Used on dispatch cards and the crew app — not shown to customers.')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('legacy_id')
                ->label('Legacy ID')
                ->disabled()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon_path')
                    ->label('Icon')
                    ->disk('public')
                    ->square()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_group')
                    ->label('Group')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Service::GROUPS[$state] ?? $state)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('default_price')
                    ->label('Default Rate')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_mode')
                    ->label('Mode')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('options_count')
                    ->label('Options')
                    ->counts('options')
                    ->badge()
                    ->placeholder('0')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('track_chemicals')
                    ->label('Chemicals')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('legacy_id')
                    ->label('Legacy ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_group')
                    ->label('Group')
                    ->options(Service::GROUPS),
            ])
            ->defaultPaginationPageOption(50)
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ServiceResource\RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
