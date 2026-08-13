@include('filament.resources.customer.overview-styles')

@php
    $record = $this->getRecord();
    $snap = $this->snapshot();
    $name = $this->customerName();
    $address = $this->mailingAddress();

    // Initials from the person's name, falling back to the company.
    $initials = collect([$record->first_name, $record->last_name])
        ->filter()
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->join('');
    if ($initials === '') {
        $initials = mb_strtoupper(mb_substr($record->company_name ?: 'C', 0, 2));
    }

    $statusPill = match ($record->status) {
        'active' => 'is-good',
        'lead' => 'is-warn',
        default => '',
    };
@endphp

<div class="cov-scope">
    <div class="cov-hero">
        <div class="cov-hero-id">
            <span class="cov-monogram" aria-hidden="true">{{ $initials }}</span>
            <div style="min-width:0;">
                <div class="cov-hero-name">{{ $name }}</div>
                @if ($record->company_name && $record->company_name !== $name)
                    <div class="cov-hero-company">{{ $record->company_name }}</div>
                @endif
                <div class="cov-hero-badges">
                    <span class="cov-pill {{ $statusPill }}">{{ ucfirst($record->status ?: 'unknown') }}</span>
                    @if ($record->customer_type)
                        <span class="cov-pill">{{ $record->customer_type }}</span>
                    @endif
                    <span class="cov-pill {{ $record->scheduling_type === 'firm' ? 'is-warn' : '' }}">
                        {{ $record->scheduling_type === 'firm' ? 'Firm dates' : 'Flexible dates' }}
                    </span>
                    @if ($snap['outstanding'] > 0)
                        <span class="cov-pill is-warn cov-num">${{ number_format($snap['outstanding'], 2) }} due</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- The four facts most often needed while on the phone with someone. --}}
        <div class="cov-facts">
            <div class="cov-fact">
                <span class="cov-micro">Phone</span>
                <span class="cov-fact-value cov-num">
                    @if ($record->phone)
                        <a href="tel:{{ $record->phone }}">{{ $record->phone }}</a>
                    @else
                        —
                    @endif
                </span>
            </div>
            <div class="cov-fact">
                <span class="cov-micro">Email</span>
                <span class="cov-fact-value">
                    @if ($record->email)
                        <a href="mailto:{{ $record->email }}">{{ $record->email }}</a>
                    @else
                        —
                    @endif
                </span>
            </div>
            <div class="cov-fact">
                <span class="cov-micro">Location</span>
                <span class="cov-fact-value">{{ $record->city ? trim($record->city . ' ' . ($record->state ?? '')) : ($address ?: '—') }}</span>
            </div>
            <div class="cov-fact">
                <span class="cov-micro">Customer since</span>
                <span class="cov-fact-value">{{ $record->created_at?->format('M Y') ?? '—' }}</span>
            </div>
            @if ($record->account_number)
                <div class="cov-fact">
                    <span class="cov-micro">Account</span>
                    <span class="cov-fact-value cov-num">{{ $record->account_number }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
