@php
    // Self-contained styling: the admin panel runs on Filament's precompiled CSS,
    // so arbitrary Tailwind utilities aren't guaranteed to exist here.
    $customer = $this->customer;
@endphp

<div class="ib-scope">
    <style>
        .ib-scope {
            --ib-ink: #0b1220; --ib-soft: #475569; --ib-mute: #6b7280;
            --ib-line: #e5e7eb; --ib-line-soft: #f1f5f9;
            --ib-surface: #fff; --ib-raised: #f8fafc;
            --ib-accent: #e00a35; --ib-warn: #b45309;
            display: block;
        }
        .ib-num { font-variant-numeric: tabular-nums; font-feature-settings: 'tnum' 1; }
        .ib-grid { display: grid; gap: 20px; grid-template-columns: minmax(0, 1fr); align-items: start; }
        @media (min-width: 1280px) { .ib-grid { grid-template-columns: minmax(0, 1.55fr) minmax(340px, 1fr); } }

        .ib-card {
            border: 1px solid var(--ib-line); border-radius: 14px;
            background: var(--ib-surface); padding: 18px 20px; margin-bottom: 16px;
        }
        .ib-card-head {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; margin-bottom: 14px;
        }
        .ib-card-title { font-size: 14px; font-weight: 700; color: var(--ib-ink); }
        .ib-micro {
            font-size: 11px; font-weight: 600; letter-spacing: .06em;
            text-transform: uppercase; color: var(--ib-mute);
        }
        .ib-hint { font-size: 12px; color: var(--ib-mute); }
        .ib-err { font-size: 12px; color: #dc2626; margin-top: 4px; }

        .ib-field {
            width: 100%; height: 38px; box-sizing: border-box;
            border: 1px solid #d1d5db; border-radius: 8px; padding: 0 11px;
            font-size: 13px; font-family: inherit; background: #fff; color: #0f172a;
        }
        .ib-field:focus { outline: 2px solid rgba(224,10,53,.35); outline-offset: 1px; border-color: var(--ib-accent); }
        textarea.ib-field { height: auto; padding: 9px 11px; resize: vertical; }
        .ib-row2 { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }

        .ib-btn {
            display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
            height: 32px; padding: 0 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
            border: 1px solid var(--ib-line); background: #fff; color: #334155;
        }
        .ib-btn:hover { background: var(--ib-raised); }
        .ib-btn-primary {
            background: var(--ib-accent); border-color: var(--ib-accent); color: #fff;
            height: 40px; padding: 0 20px; font-size: 14px;
        }
        .ib-btn-primary:hover { background: #a80828; }
        .ib-btn-icon {
            width: 28px; height: 28px; padding: 0; justify-content: center;
            border: 0; background: transparent; color: var(--ib-mute);
        }
        .ib-btn-icon:hover { background: #fee2e2; color: #b91c1c; }

        /* Line grid: description grows, the numbers stay in fixed columns so they
           line up down the page. */
        .ib-lines { display: flex; flex-direction: column; gap: 8px; }
        .ib-line-head, .ib-line {
            display: grid; gap: 8px; align-items: center;
            grid-template-columns: minmax(0, 1fr) 74px 104px 96px 28px;
        }
        .ib-line-head { padding: 0 2px; }
        .ib-line-head > span:nth-child(n+2) { text-align: right; }
        .ib-line {
            border: 1px solid var(--ib-line); border-radius: 10px; padding: 8px 9px;
            background: var(--ib-surface);
        }
        .ib-line input { height: 32px; }
        .ib-line .ib-right { text-align: right; }
        .ib-line-total { font-size: 13px; font-weight: 700; text-align: right; color: var(--ib-ink); }
        @media (max-width: 720px) {
            .ib-line-head { display: none; }
            .ib-line { grid-template-columns: minmax(0, 1fr) 28px; }
            .ib-line .ib-right, .ib-line-total { text-align: left; }
        }

        /* Search dropdowns sit in flow, not floating, so nothing clips them. */
        .ib-results {
            margin-top: 6px; border: 1px solid var(--ib-line); border-radius: 10px;
            background: #fff; max-height: 220px; overflow-y: auto;
        }
        .ib-result {
            display: flex; justify-content: space-between; gap: 10px; width: 100%;
            text-align: left; padding: 9px 12px; border: 0; background: transparent;
            cursor: pointer; font-size: 13px; color: var(--ib-ink);
        }
        .ib-result:hover { background: var(--ib-raised); }

        .ib-chip {
            display: inline-flex; align-items: center; gap: 8px;
            border: 1px solid var(--ib-line); border-radius: 999px;
            padding: 5px 6px 5px 12px; background: var(--ib-raised); font-size: 13px;
        }

        /* ---- Preview ---- */
        .ib-preview { position: sticky; top: 12px; }
        .ib-doc {
            border: 1px solid var(--ib-line); border-radius: 14px; overflow: hidden;
            background: var(--ib-surface); box-shadow: 0 18px 40px -28px rgba(15,23,42,.4);
        }
        .ib-doc-head {
            padding: 18px 20px; border-bottom: 1px solid var(--ib-line);
            background: linear-gradient(180deg, rgba(224,10,53,.05), rgba(224,10,53,0));
        }
        .ib-doc-title { font-size: 17px; font-weight: 700; color: var(--ib-ink); letter-spacing: -.01em; }
        .ib-doc-body { padding: 16px 20px; }
        .ib-doc-line {
            display: flex; justify-content: space-between; gap: 10px;
            padding: 7px 0; border-bottom: 1px solid var(--ib-line-soft); font-size: 13px;
        }
        .ib-doc-line:last-of-type { border-bottom: 0; }
        .ib-doc-line-desc { color: var(--ib-ink); min-width: 0; }
        .ib-doc-line-meta { font-size: 11.5px; color: var(--ib-mute); }
        .ib-doc-line-amt { font-weight: 600; color: var(--ib-ink); white-space: nowrap; }
        .ib-doc-sums { border-top: 1px solid var(--ib-line); padding: 12px 20px 4px; }
        .ib-sum { display: flex; justify-content: space-between; gap: 10px; padding: 5px 0; font-size: 13px; }
        .ib-sum-label { color: var(--ib-mute); }
        .ib-sum-value { font-weight: 600; color: var(--ib-ink); }
        .ib-sum.is-credit .ib-sum-value { color: var(--ib-warn); }
        .ib-doc-total {
            display: flex; justify-content: space-between; align-items: baseline; gap: 10px;
            margin: 10px 20px 18px; padding: 12px 14px;
            border-radius: 12px; background: var(--ib-raised); border: 1px solid var(--ib-line);
        }
        .ib-doc-total-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--ib-soft); }
        .ib-doc-total-value { font-size: 26px; font-weight: 700; letter-spacing: -.02em; color: var(--ib-ink); }

        .dark .ib-scope {
            --ib-ink: #f1f5f9; --ib-soft: #cbd5e1; --ib-mute: #94a3b8;
            --ib-line: rgba(255,255,255,.12); --ib-line-soft: rgba(255,255,255,.08);
            --ib-surface: rgba(255,255,255,.03); --ib-raised: rgba(255,255,255,.05);
            --ib-accent: #f4657f; --ib-warn: #fbbf24;
        }
        .dark .ib-field, .dark .ib-results, .dark .ib-btn {
            background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.14); color: #e2e8f0;
        }
        .dark .ib-btn-primary { color: #fff; background: #c9092f; border-color: #c9092f; }
    </style>

    <div class="ib-grid">
        {{-- ============================ FORM ============================ --}}
        <div>
            {{-- Customer --}}
            <div class="ib-card">
                <div class="ib-card-head">
                    <span class="ib-card-title">Customer</span>
                </div>

                @if ($customer)
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span class="ib-chip">
                            <span style="font-weight:600;">
                                {{ trim($customer->first_name . ' ' . $customer->last_name) ?: $customer->company_name }}
                            </span>
                            <button type="button" wire:click="clearCustomer" class="ib-btn ib-btn-icon" title="Choose a different customer" aria-label="Choose a different customer">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                        @if ($customer->email)
                            <span class="ib-hint">{{ $customer->email }}</span>
                        @endif
                    </div>
                @else
                    <input type="text" wire:model.live.debounce.300ms="customerSearch" class="ib-field" placeholder="Search customers by name, company or email…" autocomplete="off">
                    @if ($showCustomerDropdown && $this->customerResults->isNotEmpty())
                        <div class="ib-results">
                            @foreach ($this->customerResults as $result)
                                <button type="button" wire:click="selectCustomer({{ $result->id }})" class="ib-result">
                                    <span>{{ trim($result->first_name . ' ' . $result->last_name) ?: $result->company_name }}</span>
                                    <span class="ib-hint">{{ $result->company_name && trim($result->first_name . ' ' . $result->last_name) ? $result->company_name : $result->email }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endif
                @error('customerId') <div class="ib-err">{{ $message }}</div> @enderror

                @if ($customer)
                    @php $selectedEstimate = $this->selectedEstimate; @endphp
                    <div style="margin-top:14px;">
                        <div class="ib-micro" style="margin-bottom:5px;">From estimate <span style="text-transform:none; letter-spacing:0; font-weight:400;">(optional)</span></div>

                        @if ($selectedEstimate)
                            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                <span class="ib-chip">
                                    <span style="font-weight:600;" class="ib-num">
                                        {{ $selectedEstimate->estimate_number ?: 'Estimate #' . $selectedEstimate->id }}
                                    </span>
                                    <span class="ib-hint ib-num">
                                        ${{ number_format((float) $selectedEstimate->total, 2) }} · {{ ucfirst($selectedEstimate->status) }}
                                    </span>
                                    <button type="button" wire:click="clearEstimate" class="ib-btn ib-btn-icon" title="Unlink this estimate" aria-label="Unlink this estimate">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </span>
                                <button type="button" wire:click="importFromEstimate" class="ib-btn">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
                                    Copy its lines in
                                </button>
                            </div>
                        @else
                            {{-- Searchable, and focusing it lists the newest estimates so the
                                 usual case (the one just written) needs no typing. --}}
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="estimateSearch"
                                wire:focus="updatedEstimateSearch"
                                class="ib-field"
                                style="max-width:420px;"
                                placeholder="Search this customer's estimates by number, status or amount…"
                                autocomplete="off"
                            >
                            @if ($showEstimateDropdown)
                                @php $estimateResults = $this->estimateResults; @endphp
                                @if ($estimateResults->isNotEmpty())
                                    <div class="ib-results" style="max-width:420px;">
                                        @foreach ($estimateResults as $estimate)
                                            <button type="button" wire:click="selectEstimate({{ $estimate->id }})" class="ib-result">
                                                <span class="ib-num">
                                                    {{ $estimate->estimate_number ?: 'Estimate #' . $estimate->id }}
                                                    <div class="ib-hint">
                                                        {{ $estimate->created_at?->format('M j, Y') ?? 'No date' }} · {{ ucfirst($estimate->status) }}
                                                    </div>
                                                </span>
                                                <span class="ib-hint ib-num">${{ number_format((float) $estimate->total, 2) }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="ib-hint" style="margin:8px 0 0;">
                                        {{ trim($estimateSearch) === ''
                                            ? 'No estimates on file for this customer.'
                                            : 'No estimates match that search.' }}
                                    </p>
                                @endif
                            @endif
                        @endif
                    </div>
                @endif
            </div>

            {{-- Services: the only place a figure is entered --}}
            <div class="ib-card">
                <div class="ib-card-head">
                    <div>
                        <span class="ib-card-title">Services</span>
                        <div class="ib-hint">Quantity × rate builds each line. The invoice total follows.</div>
                    </div>
                    <button type="button" wire:click="addCustomLine" class="ib-btn">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                        Custom line
                    </button>
                </div>

                <input type="text" wire:model.live.debounce.300ms="serviceSearch" class="ib-field" placeholder="Search services to add…" autocomplete="off">
                @if ($showServiceDropdown && $this->serviceResults->isNotEmpty())
                    <div class="ib-results">
                        @foreach ($this->serviceResults as $service)
                            <button type="button" wire:click="addService({{ $service->id }})" class="ib-result">
                                <span>{{ $service->name }}</span>
                                <span class="ib-hint ib-num">
                                    {{ $service->default_price === null ? 'No default rate' : '$' . number_format((float) $service->default_price, 2) }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif

                @if (count($lines))
                    <div class="ib-line-head ib-micro" style="margin-top:14px;">
                        <span>Description</span><span>Qty</span><span>Rate</span><span>Amount</span><span></span>
                    </div>
                    <div class="ib-lines" style="margin-top:6px;">
                        @foreach ($lines as $i => $line)
                            <div class="ib-line" wire:key="ibline-{{ $i }}">
                                <input type="text" wire:model.live.debounce.400ms="lines.{{ $i }}.description" class="ib-field" placeholder="What is being billed">
                                <input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="lines.{{ $i }}.quantity" class="ib-field ib-right ib-num">
                                <input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="lines.{{ $i }}.unit_price" class="ib-field ib-right ib-num">
                                <span class="ib-line-total ib-num">${{ number_format($this->lineTotal($i), 2) }}</span>
                                <button type="button" wire:click="removeLine({{ $i }})" class="ib-btn ib-btn-icon" title="Remove this line" aria-label="Remove this line">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            @error('lines.' . $i . '.description') <div class="ib-err">{{ $message }}</div> @enderror
                            @error('lines.' . $i . '.quantity') <div class="ib-err">{{ $message }}</div> @enderror
                            @error('lines.' . $i . '.unit_price') <div class="ib-err">{{ $message }}</div> @enderror
                        @endforeach
                    </div>
                @else
                    <p class="ib-hint" style="margin:12px 0 0;">No services yet. Search above, or add a custom line for one-off work.</p>
                @endif
                @error('lines') <div class="ib-err">{{ $message }}</div> @enderror
            </div>

            {{-- Discounts and credits: both reduce what's owed, for different reasons.
                 A discount is a price adjustment on this work; a credit is money the
                 customer already holds with us. --}}
            <div class="ib-card">
                <div class="ib-card-head">
                    <div>
                        <span class="ib-card-title">Discounts</span>
                        <div class="ib-hint">Comes off the subtotal and prints on the invoice.</div>
                    </div>
                    <button type="button" wire:click="addDiscount" class="ib-btn">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                        Add discount
                    </button>
                </div>

                @forelse ($discounts as $i => $discount)
                    <div class="ib-line" wire:key="ibdisc-{{ $i }}" style="grid-template-columns: minmax(0,1fr) 104px 28px; margin-bottom:8px;">
                        <input type="text" wire:model.live.debounce.400ms="discounts.{{ $i }}.description" class="ib-field" placeholder="Reason (e.g. seasonal promotion)">
                        <input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="discounts.{{ $i }}.amount" class="ib-field ib-right ib-num">
                        <button type="button" wire:click="removeDiscount({{ $i }})" class="ib-btn ib-btn-icon" title="Remove this discount" aria-label="Remove this discount">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    @error('discounts.' . $i . '.description') <div class="ib-err">{{ $message }}</div> @enderror
                @empty
                    <p class="ib-hint" style="margin:0;">None.</p>
                @endforelse
                @error('discounts') <div class="ib-err">{{ $message }}</div> @enderror
            </div>

            <div class="ib-card">
                <div class="ib-card-head">
                    <div>
                        <span class="ib-card-title">Credits applied</span>
                        <div class="ib-hint">Deducted after tax, from credit the customer already holds.</div>
                    </div>
                    <button type="button" wire:click="addCredit" class="ib-btn">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                        Add credit
                    </button>
                </div>

                @forelse ($credits as $i => $credit)
                    <div class="ib-line" wire:key="ibcred-{{ $i }}" style="grid-template-columns: 110px minmax(0,1fr) 104px 28px; margin-bottom:8px;">
                        <input type="text" wire:model.live.debounce.400ms="credits.{{ $i }}.code" class="ib-field" placeholder="Code">
                        <input type="text" wire:model.live.debounce.400ms="credits.{{ $i }}.description" class="ib-field" placeholder="Why this credit applies">
                        <input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="credits.{{ $i }}.amount" class="ib-field ib-right ib-num">
                        <button type="button" wire:click="removeCredit({{ $i }})" class="ib-btn ib-btn-icon" title="Remove this credit" aria-label="Remove this credit">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    @error('credits.' . $i . '.description') <div class="ib-err">{{ $message }}</div> @enderror
                @empty
                    <p class="ib-hint" style="margin:0;">None.</p>
                @endforelse
            </div>

            {{-- Terms and admin --}}
            <div class="ib-card">
                <div class="ib-card-head">
                    <span class="ib-card-title">Invoice details</span>
                </div>
                <div class="ib-row2">
                    <div>
                        <div class="ib-micro" style="margin-bottom:5px;">Status</div>
                        <select wire:model.live="status" class="ib-field">
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                            <option value="payment_plan">Payment Plan</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <div class="ib-micro" style="margin-bottom:5px;">Issued</div>
                        <input type="date" wire:model.live="issuedAt" class="ib-field">
                    </div>
                    <div>
                        <div class="ib-micro" style="margin-bottom:5px;">Due</div>
                        <input type="date" wire:model.live="dueAt" class="ib-field">
                    </div>
                    <div>
                        <div class="ib-micro" style="margin-bottom:5px;">Tax</div>
                        <input type="number" min="0" step="0.01" wire:model.live.debounce.400ms="tax" class="ib-field ib-right ib-num">
                        @error('tax') <div class="ib-err">{{ $message }}</div> @enderror
                    </div>
                </div>

                <label style="display:flex; align-items:center; gap:9px; margin-top:14px; cursor:pointer; font-size:13px; color:var(--ib-ink);">
                    <input type="checkbox" wire:model.live="allowsPaymentPlan" style="width:16px; height:16px; accent-color:#e00a35;">
                    Allow this invoice to be paid on a payment plan
                </label>

                <div style="margin-top:14px;">
                    <div class="ib-micro" style="margin-bottom:5px;">Notes</div>
                    <textarea wire:model.live.debounce.500ms="notes" rows="3" class="ib-field" placeholder="Anything the customer should see on the invoice…"></textarea>
                </div>
            </div>
        </div>

        {{-- ============================ PREVIEW ============================ --}}
        <div class="ib-preview">
            <div class="ib-doc">
                <div class="ib-doc-head">
                    <div class="ib-micro">{{ $isNew ? 'Preview — not yet saved' : 'Invoice ' . $invoice->invoice_number }}</div>
                    <div class="ib-doc-title" style="margin-top:4px;">
                        {{ $customer
                            ? (trim($customer->first_name . ' ' . $customer->last_name) ?: $customer->company_name)
                            : 'No customer selected' }}
                    </div>
                    <div class="ib-hint" style="margin-top:4px;">
                        {{ $isNew ? 'Number assigned on save' : '' }}
                        @if ($issuedAt)
                            {{ $isNew ? ' · ' : '' }}Issued {{ \Carbon\Carbon::parse($issuedAt)->format('M j, Y') }}
                        @endif
                        @if ($dueAt)
                            · Due {{ \Carbon\Carbon::parse($dueAt)->format('M j, Y') }}
                        @endif
                    </div>
                </div>

                <div class="ib-doc-body">
                    @forelse ($lines as $i => $line)
                        <div class="ib-doc-line">
                            <span class="ib-doc-line-desc">
                                {{ $line['description'] ?: 'Untitled line' }}
                                <div class="ib-doc-line-meta ib-num">
                                    {{ rtrim(rtrim(number_format((float) ($line['quantity'] ?? 0), 2), '0'), '.') }}
                                    × ${{ number_format((float) ($line['unit_price'] ?? 0), 2) }}
                                </div>
                            </span>
                            <span class="ib-doc-line-amt ib-num">${{ number_format($this->lineTotal($i), 2) }}</span>
                        </div>
                    @empty
                        <p class="ib-hint" style="margin:0;">Add a service and it appears here.</p>
                    @endforelse

                    @foreach ($discounts as $discount)
                        @if ((float) ($discount['amount'] ?? 0) > 0)
                            <div class="ib-doc-line">
                                <span class="ib-doc-line-desc">{{ $discount['description'] ?: 'Discount' }}</span>
                                <span class="ib-doc-line-amt ib-num" style="color:var(--ib-warn);">
                                    −${{ number_format(abs((float) $discount['amount']), 2) }}
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="ib-doc-sums">
                    @if ($this->discountTotal > 0)
                        <div class="ib-sum">
                            <span class="ib-sum-label">Services</span>
                            <span class="ib-sum-value ib-num">${{ number_format($this->servicesTotal, 2) }}</span>
                        </div>
                        <div class="ib-sum">
                            <span class="ib-sum-label">Discounts</span>
                            <span class="ib-sum-value ib-num" style="color:var(--ib-warn);">−${{ number_format($this->discountTotal, 2) }}</span>
                        </div>
                    @endif
                    <div class="ib-sum">
                        <span class="ib-sum-label">Subtotal</span>
                        <span class="ib-sum-value ib-num">${{ number_format($this->subtotal, 2) }}</span>
                    </div>
                    @if ($this->taxAmount > 0)
                        <div class="ib-sum">
                            <span class="ib-sum-label">Tax</span>
                            <span class="ib-sum-value ib-num">${{ number_format($this->taxAmount, 2) }}</span>
                        </div>
                    @endif
                    @if ($this->creditsTotal > 0)
                        <div class="ib-sum is-credit">
                            <span class="ib-sum-label">Credits applied</span>
                            <span class="ib-sum-value ib-num">−${{ number_format($this->creditsTotal, 2) }}</span>
                        </div>
                    @endif
                </div>

                <div class="ib-doc-total">
                    <span class="ib-doc-total-label">Amount due</span>
                    <span class="ib-doc-total-value ib-num">${{ number_format($this->grandTotal, 2) }}</span>
                </div>
            </div>

            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:14px; flex-wrap:wrap;">
                <span class="ib-hint">
                    {{ count($lines) }} {{ \Illuminate\Support\Str::plural('line', count($lines)) }}
                    @if ($this->creditsTotal > 0 || $this->discountTotal > 0)
                        · adjustments applied
                    @endif
                </span>
                <button type="button" wire:click="save" wire:loading.attr="disabled" class="ib-btn ib-btn-primary">
                    <span wire:loading.remove wire:target="save">{{ $isNew ? 'Create invoice' : 'Save invoice' }}</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        </div>
    </div>
</div>
