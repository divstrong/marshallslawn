<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Filament\Resources\SmsLogResource\Pages;
use App\Models\SmsLog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only history of every outbound text. Exists to answer one question the
 * office asks constantly — "did the customer actually get that?" — without
 * anyone opening a log file or the Twilio console.
 */
class SmsLogResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = SmsLog::class;

    protected static ?string $navigationLabel = 'Message Log';

    protected static ?string $modelLabel = 'text message';

    protected static ?string $pluralModelLabel = 'text messages';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string | \UnitEnum | null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 6;

    /** Nothing here is authored by hand — rows are written by the send pipeline. */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime('M j, g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_id')
                    ->label('Customer')
                    ->getStateUsing(function (SmsLog $record): string {
                        $customer = $record->customer;
                        if (! $customer) {
                            return '—';
                        }

                        return trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                            ?: ($customer->company_name ?? '—');
                    })
                    ->url(fn (SmsLog $record): ?string => $record->customer_id
                        ? route('filament.admin.resources.customers.edit', $record->customer_id)
                        : null),

                Tables\Columns\TextColumn::make('to_number')
                    ->label('To')
                    ->searchable(),

                Tables\Columns\TextColumn::make('template_key')
                    ->label('Type')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('body')
                    ->label('Message')
                    ->limit(60)
                    ->tooltip(fn (SmsLog $record): string => $record->body)
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SmsLog::STATUS_DELIVERED => 'success',
                        SmsLog::STATUS_FAILED, SmsLog::STATUS_UNDELIVERED => 'danger',
                        SmsLog::STATUS_SKIPPED => 'gray',
                        default => 'warning',
                    })
                    ->description(fn (SmsLog $record): ?string => $record->reason ?: $record->error_code),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        SmsLog::STATUS_DELIVERED => 'Delivered',
                        SmsLog::STATUS_SENT => 'Sent',
                        SmsLog::STATUS_QUEUED => 'Queued',
                        SmsLog::STATUS_FAILED => 'Failed',
                        SmsLog::STATUS_UNDELIVERED => 'Undelivered',
                        SmsLog::STATUS_SKIPPED => 'Not sent',
                    ]),

                Tables\Filters\Filter::make('problems')
                    ->label('Problems only')
                    ->query(fn (Builder $query): Builder => $query->whereIn('status', [
                        SmsLog::STATUS_FAILED,
                        SmsLog::STATUS_UNDELIVERED,
                        SmsLog::STATUS_SKIPPED,
                    ])),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmsLogs::route('/'),
        ];
    }
}
