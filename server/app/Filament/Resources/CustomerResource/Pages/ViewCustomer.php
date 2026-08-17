<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\EstimateResource;
use App\Filament\Resources\JobResource;
use App\Models\Job;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

/**
 * A read-only snapshot of one customer: who they are, where their properties are,
 * what work is booked, and what they've been worth. Built so the common question
 * ("what's the story with this account?") is answered without clicking into the
 * edit form's tabs.
 */
class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    /** @var array<string, mixed>|null */
    private ?array $snapshot = null;

    public function getTitle(): string
    {
        return $this->customerName();
    }

    // No subheading: the identity card at the top of the page carries the account
    // meta (type, account number, customer since) with room to lay it out properly.

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('chat')
                ->label('Chat')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->slideOver()
                ->modalWidth('lg')
                ->modalHeading(fn (): string => 'Chat with ' . $this->customerName())
                ->modalContent(fn () => view('filament.customer-chat-modal', ['customerId' => $this->record->id]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            // Both carry ?customer_id= so the create screens open already pointed at
            // this customer rather than asking who it's for.
            Actions\Action::make('newEstimate')
                ->label('New Estimate')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn (): string => EstimateResource::getUrl('create', ['customer_id' => $this->record->id])),
            Actions\Action::make('newJob')
                ->label('New Job')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(fn (): string => JobResource::getUrl('create', ['customer_id' => $this->record->id])),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        // One column at the root so each Grid below is a full-width row of its own.
        // Filament otherwise gives a ViewRecord's infolist two columns, which packed
        // both rows of cards side by side into five narrow slivers.
        return $schema->columns(1)->components([
            // Who this is, at a glance: monogram, name, standing, and the contact
            // details you need while the customer is on the phone.
            View::make('filament.resources.customer.overview-header')
                ->columnSpanFull(),

            // Then the headline numbers — the "how is this account doing" row.
            View::make('filament.resources.customer.overview-stats')
                ->columnSpanFull(),

            // Second row: an even third each on wide screens, two up at tablet size,
            // stacked on narrow. Contact's label/value pairs are inline so it scans
            // the way Billing does.
            Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])
                ->columnSpanFull()
                ->schema([
                    Section::make('Contact')
                        ->icon('heroicon-o-identification')
                        ->columnSpan(1)
                        ->inlineLabel()
                        ->schema([
                            TextEntry::make('email')
                                ->label('Primary email')
                                ->copyable()
                                ->placeholder('—'),
                            TextEntry::make('phone')
                                ->copyable()
                                ->placeholder('—'),
                            TextEntry::make('mailing_address')
                                ->label('Mailing address')
                                ->state(fn (): string => $this->mailingAddress())
                                ->placeholder('—'),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn (?string $state): string => match ($state) {
                                    'active' => 'success',
                                    'lead' => 'warning',
                                    default => 'gray',
                                }),
                            TextEntry::make('scheduling_type')
                                ->label('Scheduling')
                                ->badge()
                                ->color(fn (?string $state): string => $state === 'firm' ? 'danger' : 'gray')
                                ->formatStateUsing(fn (?string $state): string => $state === 'firm'
                                    ? 'Firm — hold dates'
                                    : 'Flexible — dates may shift'),
                            TextEntry::make('tagRecords.name')
                                ->label('Tags')
                                ->badge()
                                ->placeholder('None'),
                        ]),

                    Section::make('Properties')
                        ->icon('heroicon-o-home-modern')
                        ->description(fn (): string => $this->snapshot()['properties']->count() . ' on file')
                        ->columnSpan(1)
                        ->schema([
                            View::make('filament.resources.customer.overview-properties'),
                        ]),

                    // What's booked ahead — the question the office asks most, so it
                    // sits in the top row rather than being compressed into a byline.
                    Section::make('Upcoming')
                        ->icon('heroicon-o-calendar-days')
                        ->description(fn (): string => $this->upcomingByline())
                        ->columnSpan(1)
                        ->schema([
                            View::make('filament.resources.customer.overview-upcoming'),
                        ]),
            ]),

            // Third row: history beside the money summary, both short enough to
            // share a line.
            Grid::make(['default' => 1, 'lg' => 2])
                ->columnSpanFull()
                ->schema([
                    Section::make('Recently completed')
                        ->icon('heroicon-o-check-circle')
                        ->description(fn (): string => $this->completedByline())
                        ->columnSpan(1)
                        ->schema([
                            View::make('filament.resources.customer.overview-completed'),
                        ]),

                    Section::make('Billing')
                        ->icon('heroicon-o-banknotes')
                        ->columnSpan(1)
                        ->schema([
                            View::make('filament.resources.customer.overview-billing'),
                        ]),
            ]),

            // Notes run full width: free text reads badly in a narrow column.
            Section::make('Notes')
                ->icon('heroicon-o-pencil-square')
                ->columnSpanFull()
                ->collapsible()
                ->schema([
                    TextEntry::make('notes')
                        ->hiddenLabel()
                        ->placeholder('No notes on this account.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /** Completed-work summary: how many, and how recently. */
    public function completedByline(): string
    {
        $snap = $this->snapshot();

        if ($snap['completedCount'] === 0) {
            return 'No completed jobs yet';
        }

        $byline = $snap['completedCount'] . ' completed';

        if ($snap['lastCompletedAt']) {
            $byline .= ' · last ' . \Carbon\Carbon::parse($snap['lastCompletedAt'])->format('M j, Y');
        }

        return $byline;
    }

    /**
     * Header line for the Upcoming card: how much is booked, when the next visit
     * lands, and how much work is still waiting on a date.
     */
    public function upcomingByline(): string
    {
        $snap = $this->snapshot();

        if ($snap['upcomingCount'] === 0) {
            return $snap['unscheduledCount']
                ? $snap['unscheduledCount'] . ' open ' . str('job')->plural($snap['unscheduledCount']) . ', none scheduled'
                : 'Nothing upcoming';
        }

        $byline = $snap['upcomingCount'] . ' upcoming';

        if ($snap['nextVisit']) {
            $byline .= ' · next ' . \Carbon\Carbon::parse($snap['nextVisit'])->format('D, M j');
        }

        if ($snap['unscheduledCount']) {
            $byline .= ' · ' . $snap['unscheduledCount'] . ' awaiting a date';
        }

        return $byline;
    }

    public function customerName(): string
    {
        $record = $this->getRecord();

        return trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? ''))
            ?: ($record->company_name ?: "Customer #{$record->id}");
    }

    public function mailingAddress(): string
    {
        $record = $this->getRecord();
        $line = implode(', ', array_filter([$record->address, $record->city]));
        $tail = trim(($record->state ?? '') . ' ' . ($record->zip ?? ''));

        return trim($line . ($tail ? ' ' . $tail : ''));
    }

    /**
     * Everything the overview partials render, gathered once per request.
     *
     * Job totals are computed from service lines (Job::total()), so the job rows
     * eager-load those lines rather than summing in SQL.
     *
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        if ($this->snapshot !== null) {
            return $this->snapshot;
        }

        $customer = $this->getRecord();
        $today = now()->startOfDay();

        $properties = $customer->properties()
            ->orderByDesc('is_primary')
            ->orderBy('address')
            ->get();

        $completed = $customer->jobs()
            ->with(['jobServices', 'crew'])
            ->where('status', 'completed')
            ->get();

        $upcoming = $customer->jobs()
            ->with(['jobServices.service', 'crew'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('scheduled_date')
            ->where('scheduled_date', '>=', $today->toDateString())
            ->orderBy('scheduled_date')
            ->get();

        $lifetimeValue = $completed->sum(fn (Job $job): float => $job->total());

        $invoices = $customer->invoices()->with('payments')->get();
        $outstanding = $invoices
            ->reject(fn ($invoice): bool => in_array($invoice->status, ['paid', 'cancelled'], true))
            ->sum(fn ($invoice): float => max(0, $invoice->balanceDue()));

        return $this->snapshot = [
            'properties' => $properties,
            'jobsTotal' => $customer->jobs()->count(),
            'completedCount' => $completed->count(),
            'lifetimeValue' => (float) $lifetimeValue,
            'averageJobValue' => $completed->count() ? (float) $lifetimeValue / $completed->count() : 0.0,
            'lastCompletedAt' => $completed
                ->sortByDesc(fn (Job $job) => $job->completed_date ?? $job->scheduled_date)
                ->first()?->completed_date,
            'upcoming' => $upcoming->take(5),
            'upcomingCount' => $upcoming->count(),
            'nextVisit' => $upcoming->first()?->scheduled_date,
            'recentCompleted' => $completed
                ->sortByDesc(fn (Job $job) => $job->completed_date ?? $job->scheduled_date)
                ->take(5)
                ->values(),
            'unscheduledCount' => $customer->jobs()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereNull('scheduled_date')
                ->count(),
            'paymentsReceived' => (float) $customer->payments()->sum('amount'),
            'outstanding' => (float) $outstanding,
            'invoiceCount' => $invoices->count(),
            'openInvoices' => $invoices
                ->reject(fn ($invoice): bool => in_array($invoice->status, ['paid', 'cancelled'], true))
                ->sortBy('due_at')
                ->take(5)
                ->values(),
            'openEstimates' => $customer->estimates()
                ->whereIn('status', ['draft', 'sent'])
                ->count(),
        ];
    }
}
