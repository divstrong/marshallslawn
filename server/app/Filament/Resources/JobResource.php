<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JobResource\Pages;
use App\Models\Job;
use Filament\Forms;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use App\Filament\Concerns\ChecksResourceAccess;
use Filament\Actions;

class JobResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = Job::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Job')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('General')
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            // The first decision on the form, because it decides what
                            // the rest of it asks for: a service job is priced by its
                            // service lines, a quick job by a single flat price.
                            Forms\Components\Radio::make('kind')
                                ->label('Job type')
                                ->options(Job::kindOptions())
                                ->descriptions([
                                    Job::KIND_SERVICE => 'Build a scope from services, each priced. Can repeat.',
                                    Job::KIND_QUICK => 'One flat price plus notes for an existing customer. No services.',
                                ])
                                ->default(Job::KIND_SERVICE)
                                ->required()
                                ->inline()
                                ->inlineLabel(false)
                                ->live()
                                ->columnSpanFull(),
                            Forms\Components\Select::make('status')
                                ->options(fn (): array => \App\Models\JobStatus::options())
                                ->default('pending')
                                ->native(false)
                                ->required(),
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'last_name')
                                ->getOptionLabelFromRecordUsing(function ($record): string {
                                    $name = trim("{$record->first_name} {$record->last_name}");

                                    return $name !== ''
                                        ? $name
                                        : ($record->company_name ?: "Customer #{$record->id}");
                                })
                                ->searchable(['first_name', 'last_name', 'company_name'])
                                ->preload()
                                ->optionsLimit(50)
                                ->live()
                                // Changing the customer invalidates any property picked for the old one.
                                ->afterStateUpdated(fn (Set $set) => $set('property_id', null))
                                ->required()
                                // Create a customer without leaving the job form; Filament selects
                                // the new record automatically (issue #52).
                                ->createOptionForm(static::inlineCustomerForm())
                                ->createOptionUsing(fn (array $data): int => static::createInlineCustomer($data)),
                            Forms\Components\Select::make('property_id')
                                ->relationship(
                                    name: 'property',
                                    titleAttribute: 'address',
                                    modifyQueryUsing: fn ($query, Get $get) => $query
                                        ->where('customer_id', $get('customer_id')),
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record): string => trim(
                                    $record->address.($record->city ? ", {$record->city}" : '')
                                ))
                                ->searchable(['address', 'city', 'zip'])
                                ->preload()
                                // Locked until a customer is chosen; then only their properties show.
                                ->disabled(fn (Get $get): bool => blank($get('customer_id')))
                                ->placeholder(fn (Get $get): string => blank($get('customer_id'))
                                    ? 'Select a customer first'
                                    : 'Select a property'),
                            Forms\Components\Select::make('estimate_id')
                                ->relationship('estimate', 'estimate_number')
                                ->searchable()
                                ->preload()
                                ->live(),
                            // The quick job's whole scope: one flat price. Service
                            // jobs total up from their lines instead, and an attached
                            // estimate already carries the price (issue #23).
                            Forms\Components\TextInput::make('price')
                                ->label('Price')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->placeholder('0.00')
                                ->helperText('Leave blank to quote it later.')
                                ->visible(fn (Get $get): bool => $get('kind') === Job::KIND_QUICK
                                    && blank($get('estimate_id'))),
                            Forms\Components\Select::make('crew_id')
                                ->relationship('crew', 'name')
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('priority')
                                ->options([
                                    'low' => 'Low',
                                    'normal' => 'Normal',
                                    'high' => 'High',
                                    'urgent' => 'Urgent',
                                ])
                                ->default('normal'),

                            // Estimated time to completion — two inputs combined into estimated_minutes.
                            Fieldset::make('Estimated Time to Completion')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\TextInput::make('est_hours')
                                        ->label('Hours')
                                        ->numeric()->minValue(0)->maxValue(99)->placeholder('0')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set(
                                            'estimated_minutes',
                                            (((int) $state) * 60) + (int) $get('est_minutes'),
                                        )),
                                    Forms\Components\TextInput::make('est_minutes')
                                        ->label('Minutes')
                                        ->numeric()->minValue(0)->maxValue(59)->placeholder('0')
                                        ->dehydrated(false)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set(
                                            'estimated_minutes',
                                            (((int) $get('est_hours')) * 60) + (int) $state,
                                        )),
                                    Forms\Components\Hidden::make('estimated_minutes'),
                                ]),
                            Forms\Components\Toggle::make('do_not_move')
                                ->label('Do Not Move')
                                ->helperText("Locks this job's scheduled date — flags admins not to reschedule it.")
                                ->inline(false),

                            // --- Recurrence (create only) — issue #13. A series is
                            // generated from a service, so quick jobs are one-offs.
                            Forms\Components\Radio::make('job_type')
                                ->label('Frequency')
                                ->options([
                                    'one_time' => 'One Time',
                                    'recurring' => 'Recurring',
                                ])
                                ->default('one_time')
                                ->inline()
                                ->inlineLabel(false)
                                ->live()
                                ->visible(fn (Get $get, string $operation): bool => $operation === 'create'
                                    && $get('kind') !== Job::KIND_QUICK)
                                ->columnSpanFull(),
                            // Two plain rows: when it repeats, then how long for.
                            // No stop date — a series ends by visit count or runs on.
                            Fieldset::make('Recurrence')
                                // Only on the create form, and only when Recurring is chosen.
                                ->visible(fn (Get $get, string $operation): bool => $operation === 'create'
                                    && $get('job_type') === 'recurring')
                                ->columns(3)
                                ->schema([
                                    Forms\Components\Select::make('recur_frequency')
                                        ->label('Repeats')
                                        ->options([
                                            'weekly' => 'Weekly',
                                            'monthly' => 'Monthly',
                                        ])
                                        ->default('weekly')
                                        ->native(false)
                                        ->live()
                                        ->required(fn (Get $get): bool => $get('job_type') === 'recurring'),
                                    Forms\Components\Select::make('recur_day_of_week')
                                        ->label('On day')
                                        ->options([
                                            0 => 'Sunday',
                                            1 => 'Monday',
                                            2 => 'Tuesday',
                                            3 => 'Wednesday',
                                            4 => 'Thursday',
                                            5 => 'Friday',
                                            6 => 'Saturday',
                                        ])
                                        ->native(false)
                                        ->visible(fn (Get $get): bool => $get('recur_frequency') === 'weekly')
                                        ->placeholder('Any day'),
                                    Forms\Components\DatePicker::make('recur_start')
                                        ->label('First visit')
                                        ->default(now())
                                        ->required(fn (Get $get): bool => $get('job_type') === 'recurring'),
                                    // Stored as the boolean recur_indefinite; a select
                                    // states both outcomes plainly instead of a toggle
                                    // whose "off" meaning had to be inferred.
                                    Forms\Components\Select::make('recur_indefinite')
                                        ->label('Ends')
                                        ->options([
                                            0 => 'After a set number of visits',
                                            1 => 'Never — keep generating',
                                        ])
                                        ->default(0)
                                        ->native(false)
                                        ->live()
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('recur_occurrences')
                                        ->label('Number of visits')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(260)
                                        ->visible(fn (Get $get): bool => ! $get('recur_indefinite'))
                                        ->required(fn (Get $get): bool => $get('job_type') === 'recurring' && ! $get('recur_indefinite')),
                                ]),

                            Forms\Components\Radio::make('is_scheduled')
                                ->label('Scheduled')
                                ->options([
                                    'no' => 'No (TBD)',
                                    'yes' => 'Yes',
                                ])
                                ->default('no')
                                ->inline()
                                ->inlineLabel(false)
                                ->live()
                                ->dehydrated(false)
                                ->afterStateHydrated(function ($component, ?Job $record) {
                                    $component->state($record?->scheduled_date ? 'yes' : 'no');
                                })
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state !== 'yes') {
                                        $set('scheduled_date', null);
                                    }
                                }),
                            Forms\Components\DatePicker::make('scheduled_date')
                                ->label('Scheduled date')
                                ->visible(fn (Get $get): bool => $get('is_scheduled') === 'yes')
                                ->required(fn (Get $get): bool => $get('is_scheduled') === 'yes'),
                            Forms\Components\Textarea::make('notes')
                                ->helperText(fn (Get $get): ?string => $get('kind') === Job::KIND_QUICK
                                    ? 'The first line becomes the job\'s label on the board, the app and time logs.'
                                    : null)
                                ->columnSpanFull(),
                        ]),
                    // Available from the very first save (issue #52): on create the lines
                    // live in the form state, and on edit the Livewire manager takes over.
                    // Estimate-style service grid (Description / Qty / Rate / Total)
                    // via a custom Livewire component — no Filament repeater (issue #52).
                    // On create the grid buffers rows against a draft id and
                    // JobFromFormCreator persists them; on edit it writes straight to
                    // job_services. Job-level notes live on the General tab.
                    Tab::make('Services')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->badge(fn (?Job $record): ?string => $record?->jobServices()->count() ?: null)
                        // A quick job is priced by the flat Price field on General;
                        // it has no scope to build here.
                        ->hidden(fn (Get $get): bool => $get('kind') === Job::KIND_QUICK)
                        ->schema([
                            Forms\Components\Hidden::make('services_draft_id')
                                // Create-only: the draft buffer id the grid writes to. On
                                // edit it isn't dehydrated, so it never hits the model.
                                ->visibleOn('create')
                                ->default(fn (): string => (string) \Illuminate\Support\Str::uuid()),
                            \Filament\Schemas\Components\Livewire::make(
                                \App\Livewire\JobServiceLines::class,
                                fn (Get $get, ?Job $record): array => [
                                    'jobId' => $record?->exists ? $record->id : null,
                                    'draftId' => $record?->exists ? null : $get('services_draft_id'),
                                ],
                            )->key('job-service-lines'),
                        ]),
                    Tab::make('Attachments')
                        ->icon('heroicon-o-paper-clip')
                        ->badge(fn (?Job $record): ?string => $record?->media()->count() ?: null)
                        ->hidden(fn (?Job $record): bool => ! $record?->exists)
                        ->schema([
                            View::make('filament.resources.job.attachments-tab'),
                        ]),
                ]),
        ]);
    }

    /**
     * The trimmed-down customer form offered behind the "+" on the Customer select.
     * Just enough to start a job; the rest is filled in on the customer record later.
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    public static function inlineCustomerForm(): array
    {
        return [
            Forms\Components\TextInput::make('first_name')->required()->maxLength(255),
            Forms\Components\TextInput::make('last_name')->required()->maxLength(255),
            Forms\Components\TextInput::make('company_name')->maxLength(255),
            Forms\Components\TextInput::make('email')->email()->maxLength(255),
            Forms\Components\TextInput::make('phone')->tel()->maxLength(255),
            Forms\Components\Select::make('customer_type')
                ->label('Customer Type')
                ->options([
                    'Residential' => 'Residential',
                    'Commercial' => 'Commercial',
                ])
                ->native(false),
            Forms\Components\TextInput::make('address')
                ->maxLength(255)
                ->helperText('Creates the primary property for this customer.')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('city')->maxLength(255),
            Forms\Components\TextInput::make('state')->maxLength(255),
            Forms\Components\TextInput::make('zip')->maxLength(255),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return int  The new customer's id, which Filament selects into the field.
     */
    public static function createInlineCustomer(array $data): int
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data): int {
            $customer = \App\Models\Customer::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'company_name' => $data['company_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'customer_type' => $data['customer_type'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'zip' => $data['zip'] ?? null,
                'status' => 'active',
            ]);

            // A job needs somewhere to happen, so seed the primary property too.
            if (filled($data['address'] ?? null)) {
                \App\Models\Property::create([
                    'customer_id' => $customer->id,
                    'address' => $data['address'],
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'is_primary' => true,
                ]);
            }

            return $customer->id;
        });
    }

    /**
     * Copy a job and its service lines. The copy starts fresh: pending, never
     * run, with no timer or completion history carried over, and it is always a
     * one-off — duplicating a job should not clone a recurring series.
     */
    public static function duplicateJob(Job $record, ?string $scheduledDate = null): Job
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($record, $scheduledDate): Job {
            $copy = $record->replicate([
                'status',
                'type',
                'recurring_job_template_id',
                'scheduled_date',
                'completed_date',
                'started_at',
                'finished_at',
            ]);

            $copy->fill([
                'status' => 'pending',
                'type' => 'one_time',
                'recurring_job_template_id' => null,
                'scheduled_date' => $scheduledDate ?: null,
            ]);
            $copy->save();

            foreach ($record->jobServices()->orderBy('sort_order')->get() as $line) {
                $copy->jobServices()->create([
                    'service_id' => $line->service_id,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'price' => $line->price,
                    'description' => $line->description,
                    'sort_order' => $line->sort_order,
                ]);
            }

            return $copy;
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            // Newest work first. Unscheduled (TBD) jobs have a null date, which MySQL
            // sorts last on a descending sort — they belong at the bottom, not the top.
            ->defaultSort('scheduled_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Scheduled')
                    ->date()
                    ->placeholder('TBD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('id')
                    ->label('Job #')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('crew.name')
                    ->label('Crew'),
                Tables\Columns\TextColumn::make('jobServices.service.name')
                    ->label('Services')
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kind')
                    ->label('Job type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Job::kindOptions()[$state] ?? 'Service Job')
                    ->color(fn (?string $state): string => $state === Job::KIND_QUICK ? 'warning' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->label('Frequency')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'recurring' ? 'Recurring' : 'One Time')
                    ->color(fn (?string $state) => $state === 'recurring' ? 'info' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => \App\Models\JobStatus::options()[$state] ?? $state)
                    ->color(fn (?string $state): string => \App\Models\JobStatus::colorMap()[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'urgent', 'high' => 'danger',
                        'normal' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('do_not_move')
                    ->label('Do Not Move')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn (): array => \App\Models\JobStatus::options()),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
                // Crew filter, matching the Dispatch board's crew dropdown: tick the
                // crews you want on screen, untick the rest. Multi-select rather than a
                // single choice so two crews can be compared side by side.
                Tables\Filters\SelectFilter::make('crew_id')
                    ->label('Crews')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => \App\Models\Crew::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn ($query, array $data) => $query->when(
                        $data['values'] ?? [],
                        fn ($q, array $crewIds) => $q->whereIn('crew_id', $crewIds),
                    ))
                    ->indicateUsing(function (array $data): array {
                        $ids = $data['values'] ?? [];
                        if (empty($ids)) {
                            return [];
                        }

                        $names = \App\Models\Crew::whereKey($ids)->orderBy('name')->pluck('name')->all();

                        return ['Crews: ' . implode(', ', $names)];
                    }),
                // Searchable, multi-select service picker — the list is long, so it is
                // searched rather than rendered as a flat wall of checkboxes (issue #52).
                Tables\Filters\SelectFilter::make('services')
                    ->label('Services')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => \App\Models\Service::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn ($query, array $data) => $query->when(
                        $data['values'] ?? [],
                        fn ($q, array $serviceIds) => $q->whereHas(
                            'jobServices',
                            fn ($js) => $js->whereIn('service_id', $serviceIds),
                        ),
                    )),
            ])
            // Behind the funnel button: there are enough filters here that laid out
            // above the table they pushed the jobs themselves off the screen.
            ->filtersLayout(FiltersLayout::Dropdown)
            ->filtersFormColumns(2)
            ->filtersFormWidth(Width::ExtraLarge)
            ->defaultPaginationPageOption(50)
            ->actions([
                Actions\Action::make('duplicate')
                    ->label('Copy')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->modalHeading('Duplicate job')
                    ->modalSubmitActionLabel('Duplicate')
                    ->schema([
                        Forms\Components\DatePicker::make('scheduled_date')
                            ->label('Scheduled date')
                            ->helperText('Leave blank to create the copy as unscheduled (TBD).'),
                    ])
                    ->action(function (Job $record, array $data): void {
                        $copy = static::duplicateJob($record, $data['scheduled_date'] ?? null);

                        \Filament\Notifications\Notification::make()
                            ->title('Job duplicated')
                            ->body("Created job #{$copy->id}.")
                            ->success()
                            ->send();
                    }),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('changeStatus')
                        ->label('Change status')
                        ->icon('heroicon-o-flag')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('New status')
                                ->options(fn (): array => \App\Models\JobStatus::options())
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data): void {
                            // Save each record rather than mass-updating: the JobObserver
                            // keeps route stops and crew notifications in step with status.
                            foreach ($records as $record) {
                                $record->update(['status' => $data['status']]);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title($records->count() . ' ' . \Illuminate\Support\Str::plural('job', $records->count()) . ' updated')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListJobs::route('/'),
            'waiting-list' => Pages\ListWaitingListJobs::route('/waiting-list'),
            'create' => Pages\CreateJob::route('/create'),
            'edit' => Pages\EditJob::route('/{record}/edit'),
        ];
    }

}
