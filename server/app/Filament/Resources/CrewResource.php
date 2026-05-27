<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CrewResource\Pages;
use App\Models\Crew;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Concerns\ChecksResourceAccess;
use Filament\Actions;

class CrewResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = Crew::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Crew')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('General')
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('code')
                                ->label('Crew Code')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Select::make('foreman_id')
                                ->relationship('foreman', 'name')
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('division')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('legacy_id')
                                ->label('Legacy ID')
                                ->disabled()
                                ->maxLength(255),
                            Forms\Components\CheckboxList::make('categories')
                                ->label('Crew categories')
                                ->helperText('Choose one or more types this crew handles.')
                                ->options(Crew::CATEGORIES)
                                ->columns(2)
                                ->bulkToggleable()
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('notes')
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Assigned')
                        ->icon('heroicon-o-user-group')
                        ->badge(fn (?Crew $record): ?string => $record?->members()->count() ?: null)
                        ->hidden(fn (?Crew $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.crew.crew-members-tab'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('foreman.name')
                    ->label('Foreman'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('categories')
                    ->label('Categories')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Crew::CATEGORIES[$state] ?? $state)
                    ->color('gray'),
                Tables\Columns\TextColumn::make('division')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Members'),
                Tables\Columns\TextColumn::make('legacy_id')
                    ->label('Legacy ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categories')
                    ->label('Category')
                    ->options(Crew::CATEGORIES)
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }
                        return $query->whereJsonContains('categories', $value);
                    }),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrews::route('/'),
            'create' => Pages\CreateCrew::route('/create'),
            'edit' => Pages\EditCrew::route('/{record}/edit'),
        ];
    }
}
