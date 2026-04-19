<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstimateResource\Pages;
use App\Models\Estimate;
use App\Models\EstimateLineItem;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use App\Filament\Concerns\ChecksResourceAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class EstimateResource extends Resource
{
    use ChecksResourceAccess;
    protected static ?string $model = Estimate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'last_name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('property_id')
                ->relationship('property', 'address')
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('estimate_number')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'accepted' => 'Accepted',
                    'declined' => 'Declined',
                    'expired' => 'Expired',
                ])
                ->required(),
            Forms\Components\TextInput::make('subtotal')
                ->numeric()
                ->prefix('$'),
            Forms\Components\TextInput::make('tax')
                ->numeric()
                ->prefix('$'),
            Forms\Components\TextInput::make('total')
                ->numeric()
                ->prefix('$'),
            Forms\Components\DatePicker::make('valid_until'),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('estimate_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('Customer')
                    ->formatStateUsing(fn ($record) => $record->customer->first_name . ' ' . $record->customer->last_name)
                    ->description(fn ($record) => $record->customer->company_name ?: null)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('total')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
            ->defaultPaginationPageOption(50)
            ->actions([
                Actions\Action::make('view_public')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Estimate $record) => $record->share_token ? $record->getPublicUrl() : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Estimate $record) => (bool) $record->share_token),
                Actions\EditAction::make(),
                Actions\Action::make('copy')
                    ->label('Copy')
                    ->icon('heroicon-o-document-duplicate')
                    ->requiresConfirmation()
                    ->modalHeading('Duplicate Estimate')
                    ->modalDescription('This will create a new draft estimate with the same customer, property, line items, and notes.')
                    ->action(function (Estimate $record) {
                        $newEstimate = $record->replicate(['estimate_number', 'share_token', 'sent_at', 'accepted_at']);
                        $newEstimate->status = 'draft';
                        $newEstimate->valid_until = now()->addDays(30);
                        $newEstimate->save();

                        foreach ($record->lineItems as $line) {
                            $newLine = $line->replicate();
                            $newLine->estimate_id = $newEstimate->id;
                            $newLine->save();
                        }

                        Notification::make()
                            ->title('Estimate duplicated')
                            ->body("Created {$newEstimate->estimate_number}")
                            ->success()
                            ->send();

                        return redirect(EstimateResource::getUrl('edit', ['record' => $newEstimate]));
                    }),
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
            'index' => Pages\ListEstimates::route('/'),
            'create' => Pages\CreateEstimate::route('/create'),
            'edit' => Pages\EditEstimate::route('/{record}/edit'),
        ];
    }
}
