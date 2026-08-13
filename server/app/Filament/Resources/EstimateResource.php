<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstimateResource\Pages;
use App\Filament\Resources\JobResource;
use App\Models\Estimate;
use App\Models\EstimateLineItem;
use App\Models\Job;
use App\Models\JobService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
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

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

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
                    ->color('gray')
                    ->url(fn (Estimate $record) => $record->share_token ? $record->getPublicUrl() : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Estimate $record) => (bool) $record->share_token),
                Actions\EditAction::make()
                    ->color('gray'),
                Actions\Action::make('convert')
                    ->label('Convert')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('primary')
                    ->modalSubmitAction(fn ($action) => $action->color('primary'))
                    ->modalHeading('Convert estimate to job?')
                    ->modalDescription(fn (Estimate $record) => "This will create a new Job using the customer, property, and services from estimate {$record->estimate_number}. The job will be unscheduled (date = TBD) so you can schedule it later.")
                    ->modalSubmitActionLabel('Yes, create job')
                    ->action(function (Estimate $record) {
                        $job = DB::transaction(function () use ($record) {
                            $job = Job::create([
                                'customer_id' => $record->customer_id,
                                'property_id' => $record->property_id,
                                'estimate_id' => $record->id,
                                'status' => 'pending',
                                'priority' => 'normal',
                                'scheduled_date' => null, // TBD by default
                                'notes' => $record->notes,
                            ]);

                            $sortOrder = 1;
                            foreach ($record->lineItems()->orderBy('sort_order')->get() as $line) {
                                // Skip discount lines — they aren't service work.
                                if ((float) $line->total < 0) {
                                    continue;
                                }

                                JobService::create([
                                    'job_id' => $job->id,
                                    'service_id' => $line->service_id,
                                    'description' => $line->description,
                                    'sort_order' => $sortOrder++,
                                ]);
                            }

                            return $job;
                        });

                        Notification::make()
                            ->title('Job created from estimate')
                            ->body("Job #{$job->id} is unscheduled — set a date when you're ready.")
                            ->success()
                            ->send();

                        return redirect(JobResource::getUrl('edit', ['record' => $job]));
                    }),
                Actions\Action::make('copy')
                    ->label('Copy')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
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
