<div>
    @php
        $stops = $this->stops;
        $jobs = $this->unassignedJobs;
    @endphp

    <style>
        .rsm-page {
            --d-card-bg: #fff;
            --d-border: rgb(229, 231, 235);
            --d-text: rgb(17, 24, 39);
            --d-muted: rgb(107, 114, 128);
            --d-hover: rgb(249, 250, 251);
            --d-accent: #e00a35;
            font-size: 14px;
            color: var(--d-text);
        }
        .dark .rsm-page {
            --d-card-bg: rgb(17, 24, 39);
            --d-border: rgba(255, 255, 255, 0.1);
            --d-text: rgb(243, 244, 246);
            --d-muted: rgb(156, 163, 175);
            --d-hover: rgba(255, 255, 255, 0.04);
            --d-accent: #f4657f;
        }
        .rsm-page .rsm-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .rsm-page .rsm-grid { display: grid; grid-template-columns: 1fr; gap: 16px; }
        @media (min-width: 1024px) {
            .rsm-page .rsm-grid { grid-template-columns: 1fr 1.5fr; }
        }
        .rsm-page .rsm-col {
            background: var(--d-card-bg); border: 1px solid var(--d-border);
            border-radius: 12px; overflow: hidden;
            display: flex; flex-direction: column;
            min-height: 500px;
        }
        .rsm-page .rsm-col-header {
            padding: 12px 16px; border-bottom: 1px solid var(--d-border);
            background: var(--d-hover);
        }
        .rsm-page .rsm-col-title { font-size: 14px; font-weight: 600; }
        .rsm-page .rsm-col-sub { font-size: 12px; color: var(--d-muted); margin-top: 2px; }
        .rsm-page .rsm-list {
            flex: 1; padding: 12px;
            min-height: 200px;
            display: flex; flex-direction: column; gap: 8px;
            overflow-y: auto;
        }
        .rsm-page .rsm-list-empty {
            text-align: center; padding: 32px 16px;
            color: var(--d-muted); font-size: 13px;
            border: 2px dashed var(--d-border); border-radius: 8px;
            margin: 8px;
        }
        .rsm-page .rsm-card {
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
        .rsm-page .rsm-card:hover {
            border-color: var(--d-accent);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .rsm-page .rsm-card.is-ghost { opacity: 0.4; }
        .rsm-page .rsm-card.is-chosen { cursor: grabbing; }
        .rsm-page .rsm-card-num {
            display: inline-flex; align-items: center; justify-content: center;
            height: 24px; width: 24px; border-radius: 9999px;
            background: var(--d-accent); color: #fff;
            font-size: 11px; font-weight: 600; flex-shrink: 0;
        }
        .rsm-page .rsm-card-handle {
            color: var(--d-muted); flex-shrink: 0;
            display: inline-flex; align-items: center;
        }
        .rsm-page .rsm-card-body { flex: 1; min-width: 0; }
        .rsm-page .rsm-card-title { font-size: 13px; font-weight: 500; }
        .rsm-page .rsm-card-meta { font-size: 12px; color: var(--d-muted); margin-top: 2px; }
        .rsm-page .rsm-tag {
            display: inline-block; padding: 1px 6px; border-radius: 4px;
            font-size: 11px; font-weight: 500;
            background: var(--d-hover); color: var(--d-muted); margin-right: 4px;
        }
        .rsm-page .rsm-tag.no-coords {
            background: rgba(245, 158, 11, 0.15); color: rgb(180, 83, 9);
        }
        .rsm-page .rsm-hint {
            padding: 8px 16px;
            border-top: 1px solid var(--d-border);
            background: var(--d-hover);
            font-size: 12px; color: var(--d-muted);
        }
    </style>

    <div
        class="rsm-page"
        x-data="routeStopsManager($wire)"
        x-init="init()"
    >
        <div class="rsm-grid">
            <div class="rsm-col">
                <div class="rsm-col-header">
                    <div class="rsm-col-title">Unassigned Jobs</div>
                    <div class="rsm-col-sub">
                        @if (count($jobs) === 0)
                            No jobs scheduled for {{ \Carbon\Carbon::parse($this->route->route_date)->format('D, M j') }}
                        @else
                            {{ count($jobs) }} {{ \Illuminate\Support\Str::plural('job', count($jobs)) }} on {{ \Carbon\Carbon::parse($this->route->route_date)->format('D, M j') }}
                        @endif
                    </div>
                </div>
                <div wire:ignore.self id="rsm-unassigned" class="rsm-list" data-list="unassigned">
                    @forelse ($jobs as $job)
                        <div class="rsm-card" data-job-id="{{ $job['id'] }}">
                            <span class="rsm-card-handle">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                            </span>
                            <div class="rsm-card-body">
                                <div class="rsm-card-title rsm-truncate">{{ $job['title'] ?: $job['customer_name'] }}</div>
                                <div class="rsm-card-meta rsm-truncate">{{ $job['customer_name'] }} · {{ $job['address'] }}</div>
                                <div style="margin-top: 4px;">
                                    @if ($job['service_name'])
                                        <span class="rsm-tag">{{ $job['service_name'] }}</span>
                                    @endif
                                    @if (! $job['has_coords'])
                                        <span class="rsm-tag no-coords">no GPS</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rsm-list-empty">
                            Drop stops here to remove them from the route.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rsm-col">
                <div class="rsm-col-header">
                    <div class="rsm-col-title">{{ $this->route->crew?->name ?? 'Route' }} — {{ \Carbon\Carbon::parse($this->route->route_date)->format('l, M j') }}</div>
                    <div class="rsm-col-sub">
                        Route #{{ $this->route->id }} · {{ count($stops) }} {{ \Illuminate\Support\Str::plural('stop', count($stops)) }} · {{ ucfirst($this->route->status) }}
                    </div>
                </div>
                <div wire:ignore.self id="rsm-assigned" class="rsm-list" data-list="assigned">
                    @forelse ($stops as $stop)
                        <div class="rsm-card" data-stop-id="{{ $stop['id'] }}">
                            <span class="rsm-card-num">{{ $stop['sort_order'] }}</span>
                            <div class="rsm-card-body">
                                <div class="rsm-card-title rsm-truncate">{{ $stop['title'] ?: $stop['customer_name'] }}</div>
                                <div class="rsm-card-meta rsm-truncate">{{ $stop['customer_name'] }} · {{ $stop['address'] }}{{ $stop['city'] ? ', ' . $stop['city'] : '' }}</div>
                                <div style="margin-top: 4px;">
                                    @if ($stop['service_name'])
                                        <span class="rsm-tag">{{ $stop['service_name'] }}</span>
                                    @endif
                                    @if (! $stop['has_coords'])
                                        <span class="rsm-tag no-coords">no GPS</span>
                                    @endif
                                    @if ($stop['status'] !== 'pending')
                                        <span class="rsm-tag">{{ str_replace('_', ' ', $stop['status']) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rsm-list-empty">
                            Drag jobs from the left to build this route.
                        </div>
                    @endforelse
                </div>
                <div class="rsm-hint">
                    Drag to reorder · drop back left to remove
                </div>
            </div>
        </div>
    </div>

    @once
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
        @endpush
    @endonce

    @push('scripts')
        <script>
            function routeStopsManager($wire) {
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

                        const assigned = document.getElementById('rsm-assigned');
                        const unassigned = document.getElementById('rsm-unassigned');

                        if (assigned && typeof Sortable !== 'undefined') {
                            this.sortables.push(new Sortable(assigned, {
                                group: 'rsm-jobs',
                                animation: 150,
                                ghostClass: 'is-ghost',
                                chosenClass: 'is-chosen',
                                dragClass: 'is-chosen',
                                onAdd: (evt) => {
                                    const jobId = evt.item.dataset.jobId;
                                    if (jobId) this.$wire.addJobToRoute(parseInt(jobId, 10), evt.newIndex);
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
                                group: 'rsm-jobs',
                                animation: 150,
                                sort: false,
                                ghostClass: 'is-ghost',
                                chosenClass: 'is-chosen',
                                onAdd: (evt) => {
                                    const stopId = evt.item.dataset.stopId;
                                    if (stopId) this.$wire.removeStop(parseInt(stopId, 10));
                                },
                            }));
                        }
                    },
                };
            }
        </script>
    @endpush
</div>
