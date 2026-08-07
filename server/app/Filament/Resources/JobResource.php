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
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use App\Filament\Concerns\ChecksResourceAccess;
use Filament\Actions;

use function Filament\Support\original_request;

class JobResource extends Resource
{
    use ChecksResourceAccess;

    protected static ?string $model = Job::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 3;

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
                            // Title and status share the top row at half width each,
                            // so the status is the first thing seen on an edit.
                            Forms\Components\TextInput::make('title')
                                ->label('Job title')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Mowing, Mulch install, Spring cleanup…'),
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
                            // Optional direct price — only when no estimate is attached (issue #23).
                            Forms\Components\TextInput::make('price')
                                ->label('Price')
                                ->numeric()
                                ->minValue(0)
                                ->prefix('$')
                                ->placeholder('0.00')
                                ->helperText('Optional. Set a direct price for this job when no estimate is assigned.')
                                ->visible(fn (Get $get): bool => blank($get('estimate_id'))),
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

                            // --- Type & recurrence (create only) — issue #13 ---
                            Forms\Components\Radio::make('job_type')
                                ->label('Type')
                                ->options([
                                    'one_time' => 'One Time',
                                    'recurring' => 'Recurring',
                                ])
                                ->default('one_time')
                                ->inline()
                                ->inlineLabel(false)
                                ->live()
                                ->visibleOn('create')
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
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
                // Work with neither a crew nor a date. Named for what it matches,
                // not "waiting list" — that is now the Waiting List *status*, which
                // has its own view in the sidebar.
                Tables\Filters\Filter::make('waiting_list')
                    ->label('Unassigned + unscheduled')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNull('crew_id')->whereNull('scheduled_date')),
                Tables\Filters\Filter::make('unassigned')
                    ->label('Unassigned (no crew)')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNull('crew_id')),
                Tables\Filters\Filter::make('unscheduled')
                    ->label('Unscheduled (no date)')
                    ->toggle()
                    ->query(fn ($query) => $query->whereNull('scheduled_date')),
                Tables\Filters\Filter::make('scheduled_date')
                    ->schema([
                        Forms\Components\DatePicker::make('on')->label('Scheduled on'),
                        Forms\Components\DatePicker::make('from')->label('Scheduled from'),
                        Forms\Components\DatePicker::make('until')->label('Scheduled until'),
                    ])
                    // "on" pins a single day; "from"/"until" are the range form. Setting
                    // "on" makes the range fields redundant, so it wins outright.
                    ->query(fn ($query, array $data) => filled($data['on'] ?? null)
                        ? $query->whereDate('scheduled_date', $data['on'])
                        : $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('scheduled_date', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('scheduled_date', '<=', $d)))
                    ->indicateUsing(function (array $data): array {
                        if (filled($data['on'] ?? null)) {
                            return ['Scheduled on ' . \Illuminate\Support\Carbon::parse($data['on'])->toFormattedDateString()];
                        }

                        $indicators = [];

                        if (filled($data['from'] ?? null)) {
                            $indicators[] = 'Scheduled from ' . \Illuminate\Support\Carbon::parse($data['from'])->toFormattedDateString();
                        }

                        if (filled($data['until'] ?? null)) {
                            $indicators[] = 'Scheduled until ' . \Illuminate\Support\Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
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
            // Filters sit in the top row of the index rather than behind the
            // funnel dropdown, so the toggles are one click away.
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
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

    /**
     * Jobs, plus the Waiting List as its own sidebar entry. Filament only
     * registers a resource's index page by default, so the second item is added
     * here rather than by the page class.
     *
     * @return array<\Filament\Navigation\NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        return [
            ...parent::getNavigationItems(),
            \Filament\Navigation\NavigationItem::make('Waiting List')
                ->group(static::getNavigationGroup())
                ->icon('heroicon-o-queue-list')
                // Same sort as Jobs so it sits directly beneath it.
                ->sort(static::getNavigationSort())
                ->badge(function (): ?string {
                    $count = Job::where('status', 'waiting_list')->count();

                    return $count ? (string) $count : null;
                }, color: 'warning')
                ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName() . '.waiting-list'))
                ->visible(fn (): bool => static::canAccess())
                ->url(fn (): string => static::getUrl('waiting-list')),
        ];
    }

    /** Keep the main Jobs item from lighting up while the Waiting List is open. */
    public static function getNavigationItemActiveRoutePattern(): string | array
    {
        return [
            static::getRouteBaseName() . '.index',
            static::getRouteBaseName() . '.create',
            static::getRouteBaseName() . '.edit',
        ];
    }
}
