@php
    $crews = $this->crews;
    $selectedCrew = $this->selectedCrew;
    $stops = $this->routeStops;
    $jobs = $this->unassignedJobs;
    $route = $this->route;
@endphp

<x-filament-panels::page>
    <style>
        .sch-page {
            --d-card-bg: #fff;
            --d-border: rgb(229, 231, 235);
            --d-text: rgb(17, 24, 39);
            --d-muted: rgb(107, 114, 128);
            --d-hover: rgb(249, 250, 251);
            --d-accent: #e00a35;
            --d-drag-bg: rgb(254, 241, 243);
            --d-drag-border: rgb(248, 160, 175);
            font-size: 14px;
            color: var(--d-text);
        }
        .dark .sch-page {
            --d-card-bg: rgb(17, 24, 39);
            --d-border: rgba(255, 255, 255, 0.1);
            --d-text: rgb(243, 244, 246);
            --d-muted: rgb(156, 163, 175);
            --d-hover: rgba(255, 255, 255, 0.04);
            --d-accent: #f4657f;
            --d-drag-bg: rgba(224, 10, 53, 0.1);
            --d-drag-border: rgba(244, 101, 127, 0.4);
        }
        .sch-page .d-stack > * + * { margin-top: 16px; }
        .sch-page .d-row { display: flex; align-items: center; gap: 8px; }
        .sch-page .d-row-wrap { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
        .sch-page .d-spacer { margin-left: auto; }
        .sch-page .d-bar {
            background: var(--d-card-bg); border: 1px solid var(--d-border);
            border-radius: 12px; padding: 16px;
        }
        .sch-page .d-divider { width: 1px; height: 24px; background: var(--d-border); }
        .sch-page .d-icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            height: 36px; width: 36px; border-radius: 8px;
            border: 1px solid var(--d-border); background: var(--d-card-bg);
            color: var(--d-text); cursor: pointer;
        }
        .sch-page .d-icon-btn:hover { background: var(--d-hover); }
        .sch-page .d-icon-btn svg { height: 16px; width: 16px; }
        .sch-page .d-input {
            height: 36px; border-radius: 8px; border: 1px solid var(--d-border);
            background: var(--d-card-bg); color: var(--d-text);
            padding: 0 12px; font-size: 14px; font-family: inherit;
        }
        .sch-page .d-btn {
            height: 36px; padding: 0 12px; border-radius: 8px;
            border: 1px solid var(--d-border); background: var(--d-card-bg);
            color: var(--d-text); font-size: 14px; cursor: pointer;
        }
        .sch-page .d-btn:hover { background: var(--d-hover); }
        .sch-page .d-chip {
            display: inline-flex; align-items: center;
            height: 32px; padding: 0 14px; border-radius: 9999px;
            border: 1px solid var(--d-border); background: var(--d-card-bg);
            color: var(--d-text); font-size: 13px; font-weight: 500; cursor: pointer;
        }
        .sch-page .d-chip:hover { background: var(--d-hover); }
        .sch-page .d-chip.is-active {
            background: var(--d-accent); color: #fff; border-color: var(--d-accent);
        }
        .sch-page .d-label {
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.05em; color: var(--d-muted);
        }
        .sch-page .d-muted { color: var(--d-muted); }
        .sch-page .d-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .sch-page .sch-grid {
            display: grid; grid-template-columns: 1fr; gap: 16px;
        }
        @media (min-width: 1024px) {
            .sch-page .sch-grid { grid-template-columns: 1fr 1.5fr; }
        }

        .sch-page .sch-col {
            background: var(--d-card-bg); border: 1px solid var(--d-border);
            border-radius: 12px; overflow: hidden;
            display: flex; flex-direction: column;
            min-height: 500px;
        }
        .sch-page .sch-col-header {
            padding: 12px 16px; border-bottom: 1px solid var(--d-border);
            background: var(--d-hover);
        }
        .sch-page .sch-col-title { font-size: 14px; font-weight: 600; }
        .sch-page .sch-col-sub { font-size: 12px; color: var(--d-muted); margin-top: 2px; }

        .sch-page .sch-list {
            flex: 1; padding: 12px;
            min-height: 200px;
            display: flex; flex-direction: column; gap: 8px;
            overflow-y: auto;
        }
        .sch-page .sch-list-empty {
            text-align: center; padding: 32px 16px;
            color: var(--d-muted); font-size: 13px;
            border: 2px dashed var(--d-border); border-radius: 8px;
            margin: 8px;
        }

        .sch-page .sch-card {
            background: var(--d-card-bg);
            border: 1px solid var(--d-border);
            border-radius: 8px;
            padding: 10px 12px;
            cursor: grab;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: box-shadow 120ms, border-color 120ms;
        }
        .sch-page .sch-card:hover {
            border-color: var(--d-accent);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .sch-page .sch-card.is-ghost { opacity: 0.4; }
        .sch-page .sch-card.is-chosen { cursor: grabbing; }
        .sch-page .sch-card-num {
            display: inline-flex; align-items: center; justify-content: center;
            height: 24px; width: 24px; border-radius: 9999px;
            background: var(--d-accent); color: #fff;
            font-size: 11px; font-weight: 600; flex-shrink: 0;
        }
        .sch-page .sch-card-handle {
            color: var(--d-muted); flex-shrink: 0;
            display: inline-flex; align-items: center;
        }
        .sch-page .sch-card-body { flex: 1; min-width: 0; }
        .sch-page .sch-card-title { font-size: 13px; font-weight: 500; }
        .sch-page .sch-card-meta { font-size: 12px; color: var(--d-muted); margin-top: 2px; }
        .sch-page .sch-tag {
            display: inline-block; padding: 1px 6px; border-radius: 4px;
            font-size: 11px; font-weight: 500;
            background: var(--d-hover); color: var(--d-muted); margin-right: 4px;
        }
        .sch-page .sch-tag.no-coords {
            background: rgba(245, 158, 11, 0.15); color: rgb(180, 83, 9);
        }

        .sch-page .sch-hint {
            padding: 8px 16px;
            border-top: 1px solid var(--d-border);
            background: var(--d-hover);
            font-size: 12px; color: var(--d-muted);
        }

        .sch-page .sch-empty-state {
            text-align: center; padding: 64px 24px;
            color: var(--d-muted);
        }
    </style>

    <div
        class="sch-page"
        x-data="schedulingBoard($wire)"
        x-init="init()"
    >
        <div class="d-stack">
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
                        <button type="button" wire:click="$set('date', '{{ now()->toDateString() }}')" class="d-btn">Today</button>
                    </div>

                    <div class="d-divider"></div>

                    <div class="d-row-wrap">
                        @forelse ($crews as $crew)
                            <button
                                type="button"
                                wire:click="selectCrew({{ $crew['id'] }})"
                                class="d-chip {{ (int) $this->crewId === $crew['id'] ? 'is-active' : '' }}"
                            >
                                {{ $crew['name'] }}
                            </button>
                        @empty
                            <span class="d-muted">No crews yet — create one first.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            @if (! $selectedCrew)
                <div class="sch-empty-state">
                    Select a crew above to begin building today's route.
                </div>
            @else
                <div class="sch-grid">
                    <div class="sch-col">
                        <div class="sch-col-header">
                            <div class="sch-col-title">Unassigned Jobs</div>
                            <div class="sch-col-sub">
                                @if (count($jobs) === 0)
                                    No jobs scheduled for {{ \Carbon\Carbon::parse($this->date)->format('D, M j') }}
                                @else
                                    {{ count($jobs) }} {{ \Illuminate\Support\Str::plural('job', count($jobs)) }} on {{ \Carbon\Carbon::parse($this->date)->format('D, M j') }}
                                @endif
                            </div>
                        </div>
                        <div wire:ignore.self id="sch-unassigned" class="sch-list" data-list="unassigned">
                            @forelse ($jobs as $job)
                                <div class="sch-card" data-job-id="{{ $job['id'] }}">
                                    <span class="sch-card-handle">
                                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                    </span>
                                    <div class="sch-card-body">
                                        <div class="sch-card-title d-truncate">{{ $job['title'] ?: $job['customer_name'] }}</div>
                                        <div class="sch-card-meta d-truncate">
                                            {{ $job['customer_name'] }} · {{ $job['address'] }}
                                        </div>
                                        <div style="margin-top: 4px;">
                                            @if ($job['service_name'])
                                                <span class="sch-tag">{{ $job['service_name'] }}</span>
                                            @endif
                                            @if (! $job['has_coords'])
                                                <span class="sch-tag no-coords">no GPS</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="sch-list-empty">
                                    Drop stops here to remove them from the route.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="sch-col">
                        <div class="sch-col-header">
                            <div class="sch-col-title">{{ $selectedCrew['name'] }} — {{ \Carbon\Carbon::parse($this->date)->format('l, M j') }}</div>
                            <div class="sch-col-sub">
                                @if ($route)
                                    Route #{{ $route->id }} · {{ count($stops) }} {{ \Illuminate\Support\Str::plural('stop', count($stops)) }} · {{ ucfirst($route->status) }}
                                @else
                                    No route yet — drop a job here to create one.
                                @endif
                            </div>
                        </div>
                        <div wire:ignore.self id="sch-assigned" class="sch-list" data-list="assigned">
                            @forelse ($stops as $stop)
                                <div class="sch-card" data-stop-id="{{ $stop['id'] }}">
                                    <span class="sch-card-num">{{ $stop['sort_order'] }}</span>
                                    <div class="sch-card-body">
                                        <div class="sch-card-title d-truncate">{{ $stop['title'] ?: $stop['customer_name'] }}</div>
                                        <div class="sch-card-meta d-truncate">
                                            {{ $stop['customer_name'] }} · {{ $stop['address'] }}{{ $stop['city'] ? ', ' . $stop['city'] : '' }}
                                        </div>
                                        <div style="margin-top: 4px;">
                                            @if ($stop['service_name'])
                                                <span class="sch-tag">{{ $stop['service_name'] }}</span>
                                            @endif
                                            @if (! $stop['has_coords'])
                                                <span class="sch-tag no-coords">no GPS</span>
                                            @endif
                                            @if ($stop['status'] !== 'pending')
                                                <span class="sch-tag">{{ str_replace('_', ' ', $stop['status']) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="sch-list-empty">
                                    Drag jobs from the left to build this route.
                                </div>
                            @endforelse
                        </div>
                        <div class="sch-hint">
                            Drag to reorder · drop back left to remove
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        <script>
            function schedulingBoard($wire) {
                return {
                    $wire,
                    sortables: [],

                    init() {
                        this.bindSortables();
                        if (typeof Livewire !== 'undefined') {
                            Livewire.hook('morph.updated', () => {
                                this.$nextTick(() => this.bindSortables());
                            });
                        }
                    },

                    bindSortables() {
                        this.sortables.forEach((s) => { try { s.destroy(); } catch (e) {} });
                        this.sortables = [];

                        const assigned = document.getElementById('sch-assigned');
                        const unassigned = document.getElementById('sch-unassigned');

                        if (assigned && typeof Sortable !== 'undefined') {
                            this.sortables.push(new Sortable(assigned, {
                                group: 'sch-jobs',
                                animation: 150,
                                ghostClass: 'is-ghost',
                                chosenClass: 'is-chosen',
                                dragClass: 'is-chosen',
                                onAdd: (evt) => {
                                    const jobId = evt.item.dataset.jobId;
                                    if (jobId) {
                                        this.$wire.addJobToRoute(parseInt(jobId, 10), evt.newIndex);
                                    }
                                },
                                onUpdate: () => {
                                    const ids = Array.from(assigned.querySelectorAll('[data-stop-id]'))
                                        .map((n) => parseInt(n.dataset.stopId, 10))
                                        .filter((n) => !Number.isNaN(n));
                                    if (ids.length) this.$wire.reorderStops(ids);
                                },
                            }));
                        }

                        if (unassigned && typeof Sortable !== 'undefined') {
                            this.sortables.push(new Sortable(unassigned, {
                                group: 'sch-jobs',
                                animation: 150,
                                sort: false,
                                ghostClass: 'is-ghost',
                                chosenClass: 'is-chosen',
                                onAdd: (evt) => {
                                    const stopId = evt.item.dataset.stopId;
                                    if (stopId) {
                                        this.$wire.removeStop(parseInt(stopId, 10));
                                    }
                                },
                            }));
                        }
                    },
                };
            }
        </script>
    @endpush
</x-filament-panels::page>
