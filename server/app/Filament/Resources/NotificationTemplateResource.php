<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Filament\Resources\NotificationTemplateResource\Pages;
use App\Models\NotificationTemplate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class NotificationTemplateResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = NotificationTemplate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string | \UnitEnum | null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Push Notifications';

    protected static ?string $modelLabel = 'notification template';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->helperText('A label so you can recognise this notification.'),
            Forms\Components\TextInput::make('key')
                ->label('Key')
                ->required()
                ->maxLength(255)
                // The app sends notifications against this key — locked once created.
                ->disabled(fn (?NotificationTemplate $record): bool => $record !== null)
                ->dehydrated()
                ->unique(ignoreRecord: true)
                ->helperText('Machine key the app references (e.g. job_skipped). Cannot be changed after creation.'),
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->helperText('Push notification title. Placeholders allowed.'),
            Forms\Components\Textarea::make('body')
                ->required()
                ->rows(3)
                ->helperText('Push notification body. Placeholders allowed.'),
            Forms\Components\Select::make('channel')
                ->options([
                    'default' => 'Default',
                    'alerts' => 'Alerts',
                    'chat' => 'Chat',
                ])
                ->default('alerts')
                ->required(),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('When off, the app falls back to its built-in default text.'),
            Forms\Components\Placeholder::make('placeholders')
                ->label('Available placeholders')
                ->content(new HtmlString(
                    collect(NotificationTemplate::PLACEHOLDERS)
                        ->map(fn ($desc, $token) => '<code>' . e($token) . '</code> — ' . e($desc))
                        ->implode('<br>')
                ))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('key')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->limit(40),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationTemplates::route('/'),
            'create' => Pages\CreateNotificationTemplate::route('/create'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
