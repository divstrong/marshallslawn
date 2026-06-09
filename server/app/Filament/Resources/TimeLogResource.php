<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAccess;
use App\Filament\Resources\TimeLogResource\Pages;
use App\Models\Job;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Job-level time logs (issue #17).
 *
 * Reflects the start / stop times the crew records against each job in the field
 * app (Job::started_at / finished_at), replacing the retired employee time clock.
 */
class TimeLogResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = Job::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 5;

    protected static ?string $label = 'Time Log';

    protected static ?string $pluralLabel = 'Time Logs';

    protected static ?string $navigationLabel = 'Time Logs';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        // Only jobs that have actually been started in the field have a time log.
        return parent::getEloquentQuery()->whereNotNull('started_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Placeholder::make('job_summary')
                ->label('Job')
                ->content(fn (?Job $record) => $record
                    ? trim(($record->title ?: 'Job #' . $record->id) . ' — ' . self::customerName($record))
                    : '—'),
            Forms\Components\DateTimePicker::make('started_at')
                ->label('Started')
                ->seconds(false)
                ->helperText('When the crew clocked on to this job.'),
            Forms\Components\DateTimePicker::make('finished_at')
                ->label('Finished')
                ->seconds(false)
                ->helperText('Leave blank while the job is still in progress.'),
            Forms\Components\Textarea::make('notes')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Job')
                    ->formatStateUsing(fn (?string $state, Job $record) => $state ?: 'Job #' . $record->id)
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('customer')
                    ->label('Customer')
                    ->state(fn (Job $record) => self::customerName($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                        'customer',
                        fn (Builder $q) => $q
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%"),
                    )),
                Tables\Columns\TextColumn::make('crew.name')
                    ->label('Crew')
                    ->badge()
                    ->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M j, g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Finished')
                    ->dateTime('M j, g:i A')
                    ->placeholder('In progress')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->state(fn (Job $record) => self::duration($record))
                    ->badge()
                    ->color(fn (Job $record) => $record->finished_at ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->toggleable(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('crew_id')
                    ->label('Crew')
                    ->relationship('crew', 'name'),
                Tables\Filters\TernaryFilter::make('in_progress')
                    ->label('In progress')
                    ->placeholder('All')
                    ->trueLabel('Still running')
                    ->falseLabel('Finished')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('finished_at'),
                        false: fn (Builder $q) => $q->whereNotNull('finished_at'),
                    ),
                Tables\Filters\Filter::make('started_at')
                    ->schema([
                        Forms\Components\DatePicker::make('from')->label('Started from'),
                        Forms\Components\DatePicker::make('until')->label('Started until'),
                    ])
                    ->query(function (Builder $q, array $data): Builder {
                        return $q
                            ->when($data['from'] ?? null, fn ($qq, $d) => $qq->whereDate('started_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($qq, $d) => $qq->whereDate('started_at', '<=', $d));
                    }),
            ])
            ->defaultPaginationPageOption(50)
            ->actions([
                Actions\Action::make('stopTimer')
                    ->label('Stop')
                    ->icon('heroicon-o-stop-circle')
                    ->color('warning')
                    ->visible(fn (Job $record): bool => $record->isTimerRunning())
                    ->requiresConfirmation()
                    ->modalHeading('Stop this job timer?')
                    ->modalDescription('Sets the finish time to now (capped at 12 hours from start) and marks the job completed. Use this if a crew left the timer running by accident.')
                    ->action(function (Job $record): void {
                        $record->stopTimer();
                        Notification::make()
                            ->title('Timer stopped')
                            ->success()
                            ->send();
                    }),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function customerName(Job $job): string
    {
        $customer = $job->customer;
        if (! $customer) {
            return '—';
        }

        $name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));

        return $name ?: ($customer->company_name ?? '—');
    }

    private static function duration(Job $job): string
    {
        if (! $job->started_at) {
            return '—';
        }

        $end = $job->finished_at ?? now();
        $minutes = (int) abs($job->started_at->diffInMinutes($end));
        // A still-running timer never displays more than the daily maximum.
        if (! $job->finished_at) {
            $minutes = min($minutes, Job::MAX_TIMER_HOURS * 60);
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        $label = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";

        return $job->finished_at ? $label : "{$label} (running)";
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimeLogs::route('/'),
            'edit' => Pages\EditTimeLog::route('/{record}/edit'),
        ];
    }
}
