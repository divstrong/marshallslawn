@php
    $mapsApiKey = $this->getGoogleMapsApiKey();
    $stops = $this->stops;
    $unroutedJobs = $this->unroutedJobs;
    $pins = array_merge($stops, $unroutedJobs);
    $foremen = $this->foremanPins;
    $crewColors = $this->crewColorMap;
    $summary = $this->summary;
    $unmapped = $this->unmappedStops;
    $selected = $this->selectedStop;
    $selectedJob = $this->selectedUnroutedJob;
    $selectedForeman = $this->selectedForeman;
@endphp

<x-filament-panels::page>
    <style>
        .dispatch-page {
            --d-card-bg: #fff;
            --d-border: rgb(229, 231, 235);
            --d-text: rgb(17, 24, 39);
            --d-muted: rgb(107, 114, 128);
            --d-hover: rgb(249, 250, 251);
            --d-accent: #e00a35;
            font-size: 14px;
            color: var(--d-text);
        }
        .dark .dispatch-page {
            --d-card-bg: rgb(17, 24, 39);
            --d-border: rgba(255, 255, 255, 0.1);
            --d-text: rgb(243, 244, 246);
            --d-muted: rgb(156, 163, 175);
            --d-hover: rgba(255, 255, 255, 0.04);
            --d-accent: #f4657f;
        }
        .dispatch-page .d-stack > * + * { margin-top: 16px; }
        .dispatch-page .d-row { display: flex; align-items: center; gap: 8px; }
        .dispatch-page .d-row-wrap { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
        .dispatch-page .d-spacer { margin-left: auto; }
        .dispatch-page .d-bar {
            background: var(--d-card-bg); border: 1px solid var(--d-border);
            border-radius: 12px; padding: 16px;
        }
        .dispatch-page .d-divider { width: 1px; height: 24px; background: var(--d-border); }
        .dispatch-page .d-icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            height: 36px; width: 36px; border-radius: 8px;
            border: 1px solid var(--d-border); background: var(--d-card-bg);
            color: var(--d-text); cursor: pointer; transition: background 120ms;
        }
        .dispatch-page .d-icon-btn:hover { background: var(--d-hover); }
        .dispatch-page .d-icon-btn svg { height: 16px; width: 16px; }
        .dispatch-page .d-input, .dispatch-page .d-select {
            height: 36px; border-radius: 8px; border: 1px solid var(--d-border);
            background: var(--d-card-bg); color: var(--d-text);
            padding: 0 12px; font-size: 14px; font-family: inherit;
        }
        .dispatch-page .d-btn {
            height: 36px; padding: 0 12px; border-radius: 8px;
            border: 1px solid var(--d-border); background: var(--d-card-bg);
            color: var(--d-text); font-size: 14px; cursor: pointer; transition: background 120ms;
        }
        .dispatch-page .d-btn:hover { background: var(--d-hover); }
        .dispatch-page .d-chip {
            display: inline-flex; align-items: center; gap: 8px;
            height: 32px; padding: 0 12px; border-radius: 9999px;
            border: 1px solid var(--d-border); background: var(--d-card-bg);
            color: var(--d-text); font-size: 12px; font-weight: 500;
            cursor: pointer; transition: all 120ms;
        }
        .dispatch-page .d-chip:hover { filter: brightness(0.96); }
        .dispatch-page .d-chip.is-active {
            color: #fff;
            border-color: transparent;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }
        .dispatch-page .d-chip.is-active .d-dot {
            background-color: rgba(255,255,255,0.9) !important;
            box-shadow: 0 0 0 2px rgba(255,255,255,0.3);
        }
        .dispatch-page .d-dot { display: inline-block; height: 10px; width: 10px; border-radius: 9999px; }
        .dispatch-page .d-grid {
            display: grid; grid-template-columns: 1fr; gap: 16px;
        }
        @media (min-width: 1024px) {
            .dispatch-page .d-grid { grid-template-columns: 2fr 1fr; }
        }
        .dispatch-page .d-map-wrap {
            position: relative; min-height: 600px; border-radius: 12px;
            border: 1px solid var(--d-border); background: rgb(243, 244, 246); overflow: hidden;
        }
        .dark .dispatch-page .d-map-wrap { background: rgb(31, 41, 55); }
        .dispatch-page .d-map-host { width: 100%; height: 100%; min-height: 600px; }
        .dispatch-page .d-map-empty {
            position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
            text-align: center; padding: 32px;
        }
        .dispatch-page .d-card {
            background: var(--d-card-bg); border: 1px solid var(--d-border);
            border-radius: 12px; padding: 16px;
        }
        .dispatch-page .d-card-title { font-size: 14px; font-weight: 600; }
        .dispatch-page .d-card-sub { font-size: 14px; color: var(--d-muted); margin-top: 4px; }
        .dispatch-page .d-label {
            font-size: 11px; font-weight: 500; text-transform: uppercase;
            letter-spacing: 0.05em; color: var(--d-muted);
        }
        .dispatch-page .d-field { margin-top: 12px; }
        .dispatch-page .d-field-val { color: var(--d-text); margin-top: 2px; }
        .dispatch-page .d-link { color: var(--d-accent); text-decoration: none; }
        .dispatch-page .d-link:hover { text-decoration: underline; }
        .dispatch-page .d-summary-grid {
            margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--d-border);
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 12px;
        }
        .dispatch-page .d-summary-row { display: flex; justify-content: space-between; }
        .dispatch-page .d-muted { color: var(--d-muted); }
        .dispatch-page .d-list {
            background: var(--d-card-bg); border: 1px solid var(--d-border);
            border-radius: 12px; overflow: hidden;
        }
        .dispatch-page .d-list-header {
            padding: 12px 16px; border-bottom: 1px solid var(--d-border);
        }
        .dispatch-page .d-list-scroll { max-height: 400px; overflow-y: auto; }
        .dispatch-page .d-list-item {
            width: 100%; text-align: left; padding: 10px 16px;
            border: 0; background: transparent; border-bottom: 1px solid var(--d-border);
            cursor: pointer; display: flex; align-items: center; gap: 8px;
            color: var(--d-text); font: inherit;
        }
        .dispatch-page .d-list-item:last-child { border-bottom: 0; }
        .dispatch-page .d-list-item:hover,
        .dispatch-page .d-list-item.is-active { background: var(--d-hover); }
        .dispatch-page .d-list-empty { padding: 24px 16px; text-align: center; color: var(--d-muted); }
        .dispatch-page .d-list-main { flex: 1; min-width: 0; }
        .dispatch-page .d-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dispatch-page .d-stop-num {
            display: inline-flex; align-items: center; justify-content: center;
            height: 20px; width: 20px; border-radius: 9999px;
            color: #fff; font-size: 10px; font-weight: 600; flex-shrink: 0;
        }
        .dispatch-page .d-status-pills { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
        .dispatch-page .d-status-pill {
            height: 28px; padding: 0 10px; border-radius: 6px;
            border: 1px solid var(--d-border); background: var(--d-card-bg);
            color: var(--d-text); font-size: 12px; font-weight: 500; cursor: pointer;
        }
        .dispatch-page .d-status-pill:hover { background: var(--d-hover); }
        .dispatch-page .d-status-pill.is-active {
            background: rgb(17, 24, 39); color: #fff; border-color: rgb(17, 24, 39);
        }
        .dark .dispatch-page .d-status-pill.is-active {
            background: #fff; color: rgb(17, 24, 39); border-color: #fff;
        }
        .dispatch-page .d-warning {
            background: rgb(254, 243, 199); border: 1px solid rgb(252, 211, 77);
            border-radius: 12px; padding: 16px; color: rgb(120, 53, 15);
        }
        .dark .dispatch-page .d-warning {
            background: rgba(180, 83, 9, 0.2); border-color: rgba(245, 158, 11, 0.4); color: rgb(252, 211, 77);
        }
        .dispatch-page .d-warning-sub { font-size: 12px; margin-top: 4px; opacity: 0.85; }
        .dispatch-page .d-warning ul { margin-top: 12px; font-size: 12px; list-style: none; padding: 0; }
        .dispatch-page .d-warning li + li { margin-top: 4px; }
        .dispatch-page .d-warning code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 11px; padding: 1px 4px; border-radius: 4px;
            background: rgba(0,0,0,0.06);
        }
        .dispatch-page .d-status-dot { font-size: 12px; line-height: 1; }
        .dispatch-page .d-status-dot.completed { color: rgb(22, 163, 74); }
        .dispatch-page .d-status-dot.skipped { color: rgb(220, 38, 38); }
        .dispatch-page .d-status-dot.in_progress { color: rgb(217, 119, 6); }
    </style>

    <div
        class="dispatch-page"
        x-data="dispatchBoard($wire, {
            pins: @js($pins),
            foremen: @js($foremen),
            crewColors: @js($crewColors),
            apiKey: @js($mapsApiKey ?? ''),
        })"
        x-init="init()"
    >
        <div class="d-stack">
            {{-- Filter bar --}}
            <div class="d-bar">
                <div class="d-row-wrap">
                    <div class="d-row">
                        <button type="button" wire:click="shiftDate(-1)" class="d-icon-btn" title="Previous day">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <input type="date" wire:model.live="date" class="d-input">
                        <button type="button" wire:click="shiftDate(1)" class="d-icon-btn" title="Next day">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        @php
                            $today = now()->toDateString();
                            $tomorrow = now()->addDay()->toDateString();
                            $yesterday = now()->subDay()->toDateString();
                            $activeStyle = 'background: var(--d-accent); color: #fff; border-color: var(--d-accent);';
                        @endphp
                        <button
                            type="button"
                            wire:click="$set('date', '{{ $yesterday }}')"
                            class="d-btn"
                            @if ($this->date === $yesterday) style="{{ $activeStyle }}" @endif
                        >Yesterday</button>
                        <button
                            type="button"
                            wire:click="$set('date', '{{ $today }}')"
                            class="d-btn"
                            @if ($this->date === $today) style="{{ $activeStyle }}" @endif
                        >Today</button>
                        <button
                            type="button"
                            wire:click="$set('date', '{{ $tomorrow }}')"
                            class="d-btn"
                            @if ($this->date === $tomorrow) style="{{ $activeStyle }}" @endif
                        >Tomorrow</button>
                    </div>

                    <div class="d-divider"></div>

                    <div class="d-row-wrap">
                        @foreach ($crewColors as $crewId => $crew)
                            @php
                                $crewIds = array_map('intval', $this->crewIds);
                                // Active when explicitly selected. When no filter is set, no chip is highlighted
                                // (the map shows all crews by default). Click any chip to filter to that crew.
                                $isActive = in_array($crewId, $crewIds, true);
                            @endphp
                            <button
                                type="button"
                                wire:click="toggleCrew({{ $crewId }})"
                                class="d-chip {{ $isActive ? 'is-active' : '' }}"
                                @if ($isActive)
                                    style="background: {{ $crew['color'] }};"
                                @endif
                            >
                                <span class="d-dot" style="background-color: {{ $crew['color'] }}"></span>
                                {{ $crew['name'] }}
                            </button>
                        @endforeach
                    </div>

                    <div class="d-spacer"></div>

                    <button
                        type="button"
                        wire:click="toggleGps"
                        class="d-chip"
                        @if ($this->showGps) style="background: var(--d-accent); color: #fff; border-color: var(--d-accent);" @endif
                        title="Show or hide crew foreman avatar pins (GPS placeholder)"
                    >
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                        Show GPS
                    </button>

                    <select wire:model.live="statusFilter" class="d-select">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="skipped">Skipped</option>
                    </select>
                </div>
            </div>

            {{-- Map + side panel --}}
            <div class="d-grid">
                <div class="d-map-wrap">
                    @if (! $mapsApiKey)
                        <div class="d-map-empty">
                            <div>
                                <div class="d-card-title">Map not configured</div>
                                <div class="d-card-sub">
                                    Add <code>GOOGLE_MAPS_API_KEY</code> to your <code>.env</code> file and reload.
                                </div>
                            </div>
                        </div>
                    @else
                        <div wire:ignore id="dispatch-map" class="d-map-host"></div>
                    @endif
                </div>

                <div class="d-stack">
                    @if ($selectedForeman)
                        <div class="d-card">
                            <div class="d-row" style="justify-content: space-between;">
                                <div class="d-row">
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:9999px;background:{{ $selectedForeman['color'] }};color:#fff;font-weight:700;font-size:14px;">{{ $selectedForeman['initials'] }}</span>
                                    <div>
                                        <div class="d-card-title">{{ $selectedForeman['name'] }}</div>
                                        <div class="d-muted" style="font-size:12px;">{{ $selectedForeman['crew_name'] }} foreman</div>
                                    </div>
                                </div>
                                <button type="button" wire:click="clearSelection" class="d-btn" style="height: 28px; padding: 0 8px; font-size: 12px;">Close</button>
                            </div>
                            <div class="d-field">
                                <div class="d-label">Stops today</div>
                                <div class="d-field-val">{{ $selectedForeman['stops_today'] }}</div>
                            </div>
                            @if ($selectedForeman['phone'])
                                <div class="d-field">
                                    <div class="d-label">Phone</div>
                                    <a href="tel:{{ $selectedForeman['phone'] }}" class="d-link">{{ $selectedForeman['phone'] }}</a>
                                </div>
                            @endif
                            <div class="d-field" style="font-size:11px;color:var(--d-muted);padding-top:8px;border-top:1px solid var(--d-border);">
                                Position is a placeholder. Live GPS will appear here once the foreman app is installed with location services on.
                            </div>
                        </div>
                    @elseif ($selectedJob)
                        <div class="d-card">
                            <div class="d-row" style="justify-content: space-between;">
                                <div class="d-row">
                                    <span class="d-dot" style="background-color: #9ca3af"></span>
                                    <span class="d-card-title">Unrouted job</span>
                                </div>
                                <button type="button" wire:click="clearSelection" class="d-btn" style="height: 28px; padding: 0 8px; font-size: 12px;">Close</button>
                            </div>
                            <div class="d-field">
                                <div class="d-label">Customer</div>
                                <div class="d-field-val">{{ $selectedJob['customer_name'] }}</div>
                                @if ($selectedJob['customer_phone'])
                                    <a href="tel:{{ $selectedJob['customer_phone'] }}" class="d-link" style="font-size: 12px;">{{ $selectedJob['customer_phone'] }}</a>
                                @endif
                            </div>
                            <div class="d-field">
                                <div class="d-label">Address</div>
                                <div class="d-field-val">{{ $selectedJob['address'] }}{{ $selectedJob['city'] ? ', ' . $selectedJob['city'] : '' }}</div>
                            </div>
                            @if ($selectedJob['job_title'])
                                <div class="d-field">
                                    <div class="d-label">Job</div>
                                    <div class="d-field-val">{{ $selectedJob['job_title'] }}</div>
                                </div>
                            @endif
                            @if ($selectedJob['service_name'])
                                <div class="d-field">
                                    <div class="d-label">Service</div>
                                    <div class="d-field-val">{{ $selectedJob['service_name'] }}</div>
                                </div>
                            @endif
                            <div class="d-field">
                                <a href="{{ route('filament.admin.pages.scheduling', ['date' => $this->date]) }}" class="d-link">Assign on the Scheduling board →</a>
                            </div>
                        </div>
                    @elseif ($selected)
                        <div class="d-card">
                            <div class="d-row" style="justify-content: space-between;">
                                <div class="d-row">
                                    <span class="d-dot" style="background-color: {{ $selected['color'] }}"></span>
                                    <span class="d-card-title">Stop #{{ $selected['sort_order'] }}</span>
                                </div>
                                <button type="button" wire:click="clearSelection" class="d-btn" style="height: 28px; padding: 0 8px; font-size: 12px;">Close</button>
                            </div>
                            <div class="d-field">
                                <div class="d-label">Customer</div>
                                <div class="d-field-val">{{ $selected['customer_name'] }}</div>
                                @if ($selected['customer_phone'])
                                    <a href="tel:{{ $selected['customer_phone'] }}" class="d-link" style="font-size: 12px;">{{ $selected['customer_phone'] }}</a>
                                @endif
                            </div>
                            <div class="d-field">
                                <div class="d-label">Address</div>
                                <div class="d-field-val">{{ $selected['address'] }}{{ $selected['city'] ? ', ' . $selected['city'] : '' }}</div>
                            </div>
                            @if ($selected['service_name'])
                                <div class="d-field">
                                    <div class="d-label">Service</div>
                                    <div class="d-field-val">{{ $selected['service_name'] }}</div>
                                </div>
                            @endif
                            @if ($selected['route_name'])
                                <div class="d-field">
                                    <div class="d-label">Route</div>
                                    <a href="{{ route('filament.admin.resources.routes.edit', $selected['route_id']) }}" class="d-link">
                                        {{ $selected['route_name'] }} — {{ $selected['crew_name'] }}
                                    </a>
                                </div>
                            @endif
                            @if ($selected['notes'])
                                <div class="d-field">
                                    <div class="d-label">Notes</div>
                                    <div class="d-field-val" style="white-space: pre-wrap;">{{ $selected['notes'] }}</div>
                                </div>
                            @endif
                            <div class="d-field">
                                <div class="d-label">Status</div>
                                <div class="d-status-pills">
                                    @foreach (['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Done', 'skipped' => 'Skip'] as $key => $label)
                                        <button
                                            type="button"
                                            wire:click="markStopStatus({{ $selected['id'] }}, '{{ $key }}')"
                                            class="d-status-pill {{ $selected['status'] === $key ? 'is-active' : '' }}"
                                        >{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="d-card">
                            <div class="d-card-title">{{ \Carbon\Carbon::parse($this->date)->format('l, M j') }}</div>
                            <div class="d-card-sub">{{ $summary['total'] }} {{ \Illuminate\Support\Str::plural('stop', $summary['total']) }} on the map</div>
                            @if (! empty($summary['by_crew']))
                                <div style="margin-top: 16px;">
                                    @foreach ($summary['by_crew'] as $row)
                                        <div class="d-row" style="justify-content: space-between; padding: 4px 0;">
                                            <div class="d-row">
                                                <span class="d-dot" style="background-color: {{ $row['color'] }}"></span>
                                                <span>{{ $row['crew_name'] }}</span>
                                            </div>
                                            <span class="d-muted">{{ $row['count'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <div class="d-summary-grid">
                                <div class="d-summary-row"><span class="d-muted">Pending</span><span>{{ $summary['by_status']['pending'] ?? 0 }}</span></div>
                                <div class="d-summary-row"><span class="d-muted">In progress</span><span>{{ $summary['by_status']['in_progress'] ?? 0 }}</span></div>
                                <div class="d-summary-row"><span class="d-muted">Done</span><span>{{ $summary['by_status']['completed'] ?? 0 }}</span></div>
                                <div class="d-summary-row"><span class="d-muted">Skipped</span><span>{{ $summary['by_status']['skipped'] ?? 0 }}</span></div>
                            </div>
                        </div>
                    @endif

                    <div class="d-list">
                        <div class="d-list-header">
                            <div class="d-label">Pins on map ({{ count($pins) }})</div>
                        </div>
                        <div class="d-list-scroll">
                            @forelse ($stops as $stop)
                                <button
                                    type="button"
                                    wire:click="selectStop({{ $stop['id'] }})"
                                    class="d-list-item {{ $selected && $selected['id'] === $stop['id'] ? 'is-active' : '' }}"
                                >
                                    <span class="d-stop-num" style="background-color: {{ $stop['color'] }}">{{ $stop['sort_order'] }}</span>
                                    <div class="d-list-main">
                                        <div class="d-truncate">{{ $stop['customer_name'] }}</div>
                                        <div class="d-truncate d-muted" style="font-size: 12px;">{{ $stop['address'] }}</div>
                                    </div>
                                    @if ($stop['status'] === 'completed')
                                        <span class="d-status-dot completed">✓</span>
                                    @elseif ($stop['status'] === 'skipped')
                                        <span class="d-status-dot skipped">⊘</span>
                                    @elseif ($stop['status'] === 'in_progress')
                                        <span class="d-status-dot in_progress">●</span>
                                    @endif
                                </button>
                            @empty
                            @endforelse

                            @foreach ($unroutedJobs as $job)
                                <button
                                    type="button"
                                    wire:click="selectJob({{ $job['id'] }})"
                                    class="d-list-item {{ $selectedJob && $selectedJob['id'] === $job['id'] ? 'is-active' : '' }}"
                                >
                                    <span class="d-stop-num" style="background-color: {{ $job['color'] }}">?</span>
                                    <div class="d-list-main">
                                        <div class="d-truncate">{{ $job['customer_name'] }}</div>
                                        <div class="d-truncate d-muted" style="font-size: 12px;">{{ $job['address'] }} · unrouted</div>
                                    </div>
                                </button>
                            @endforeach

                            @if (empty($pins))
                                <div class="d-list-empty">No jobs or stops with coordinates on this day.</div>
                            @endif
                        </div>
                    </div>

                    @if (! empty($unmapped))
                        <div class="d-warning">
                            <div class="d-card-title">{{ count($unmapped) }} {{ \Illuminate\Support\Str::plural('stop', count($unmapped)) }} without coordinates</div>
                            <div class="d-warning-sub">Properties need a valid address to appear on the map. Run <code>php artisan properties:geocode --missing</code>.</div>
                            <ul>
                                @foreach (array_slice($unmapped, 0, 5) as $row)
                                    <li>• {{ $row['customer_name'] }} — {{ $row['address'] }}</li>
                                @endforeach
                                @if (count($unmapped) > 5)
                                    <li style="opacity: 0.7;">… and {{ count($unmapped) - 5 }} more</li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($mapsApiKey)
        @push('scripts')
            <script>
                function dispatchBoard($wire, initial) {
                    return {
                        $wire,
                        map: null,
                        markers: [],
                        bounds: null,
                        crewColors: initial.crewColors,

                        init() {
                            if (!initial.apiKey) return;

                            this.loadMapsApi(initial.apiKey).then(() => {
                                this.buildMap(initial.pins, initial.foremen || []);
                            });

                            window.addEventListener('dispatch:stops-updated', (e) => {
                                const { stops = [], unroutedJobs = [], foremen = [], crewColors } = e.detail || {};
                                if (crewColors) this.crewColors = crewColors;
                                this.refreshMarkers([...stops, ...unroutedJobs], foremen);
                            });
                        },

                        loadMapsApi(key) {
                            return new Promise((resolve) => {
                                if (window.google && window.google.maps) return resolve();
                                if (window.__dispatchMapsLoading) return window.__dispatchMapsLoading.then(resolve);
                                window.__dispatchMapsLoading = new Promise((res) => {
                                    window.__dispatchMapsReady = res;
                                    const s = document.createElement('script');
                                    s.async = true;
                                    s.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&loading=async&callback=__dispatchMapsReady`;
                                    document.head.appendChild(s);
                                });
                                window.__dispatchMapsLoading.then(resolve);
                            });
                        },

                        buildMap(pins, foremen) {
                            const el = document.getElementById('dispatch-map');
                            if (!el) return;
                            this.map = new google.maps.Map(el, {
                                center: { lat: 37.5407, lng: -77.4360 }, // Richmond, VA
                                zoom: 11,
                                mapTypeControl: false,
                                streetViewControl: false,
                                fullscreenControl: true,
                            });
                            this.refreshMarkers(pins, foremen);
                        },

                        foremanIcon(initials, color) {
                            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="44" height="50" viewBox="0 0 44 50"><circle cx="22" cy="22" r="20" fill="${color}" stroke="#ffffff" stroke-width="3"/><text x="22" y="28" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="14" font-weight="700" fill="#ffffff">${initials}</text><circle cx="22" cy="46" r="3" fill="${color}" stroke="#ffffff" stroke-width="1.5"/></svg>`;
                            return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
                        },

                        refreshMarkers(pins, foremen = []) {
                            if (!this.map) return;
                            this.markers.forEach((m) => m.setMap(null));
                            this.markers = [];
                            if (!pins.length && !foremen.length) return;

                            this.bounds = new google.maps.LatLngBounds();
                            pins.forEach((p) => {
                                const isJob = p.kind === 'job';
                                const opacity = p.status === 'completed' ? 0.5 : (p.status === 'skipped' ? 0.4 : 1);
                                const labelText = isJob ? '?' : String(p.sort_order ?? '');
                                const marker = new google.maps.Marker({
                                    position: { lat: p.lat, lng: p.lng },
                                    map: this.map,
                                    title: `${p.customer_name} — ${p.address}`,
                                    label: { text: labelText, color: '#fff', fontSize: '11px', fontWeight: '600' },
                                    icon: {
                                        path: 'M12 2C7.58 2 4 5.58 4 10c0 5.25 7 13 8 13s8-7.75 8-13c0-4.42-3.58-8-8-8z',
                                        fillColor: p.color,
                                        fillOpacity: opacity,
                                        strokeColor: '#fff',
                                        strokeWeight: 2,
                                        scale: isJob ? 1.4 : 1.7,
                                        anchor: new google.maps.Point(12, 23),
                                        labelOrigin: new google.maps.Point(12, 10),
                                    },
                                });
                                marker.addListener('click', () => {
                                    if (isJob) this.$wire.selectJob(p.id);
                                    else this.$wire.selectStop(p.id);
                                });
                                this.markers.push(marker);
                                this.bounds.extend({ lat: p.lat, lng: p.lng });
                            });

                            foremen.forEach((f) => {
                                const marker = new google.maps.Marker({
                                    position: { lat: f.lat, lng: f.lng },
                                    map: this.map,
                                    title: `${f.name} — ${f.crew_name}`,
                                    icon: {
                                        url: this.foremanIcon(f.initials, f.color),
                                        scaledSize: new google.maps.Size(44, 50),
                                        anchor: new google.maps.Point(22, 46),
                                    },
                                    zIndex: 1000,
                                });
                                marker.addListener('click', () => this.$wire.selectForeman(f.id));
                                this.markers.push(marker);
                                this.bounds.extend({ lat: f.lat, lng: f.lng });
                            });

                            if (this.markers.length === 1) {
                                this.map.setCenter(this.markers[0].getPosition());
                                this.map.setZoom(14);
                            } else if (this.markers.length > 1) {
                                this.map.fitBounds(this.bounds, 60);
                            }
                        },
                    };
                }
            </script>
        @endpush
    @endif
</x-filament-panels::page>
