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
    $serviceIcons = $this->serviceIconsEnabled;
    $serviceGroups = \App\Models\Service::GROUPS;
@endphp

<div>
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
            .dispatch-page .d-grid { grid-template-columns: 3fr 1fr; }
            /* List view (issue #24): no map — job cards fill the full width. */
            .dispatch-page .d-grid.is-list { grid-template-columns: 1fr; }
        }
        [x-cloak] { display: none !important; }
        /* Roomier, more detailed cards in List view. */
        .dispatch-page .d-grid.is-list .d-list-scroll { max-height: none; }
        .dispatch-page .d-grid.is-list .d-list-item { padding: 14px 16px; font-size: 15px; }
        .dispatch-page .d-grid.is-list .d-list-item .d-truncate { white-space: normal; }
        .dispatch-page .d-list-extra { font-size: 13px; color: var(--d-muted); margin-top: 4px; display: flex; flex-wrap: wrap; gap: 6px; }
        .dispatch-page .d-list-extra span { background: var(--d-hover); padding: 1px 8px; border-radius: 9999px; }
        /* List view agenda (Day / Week) — vertically stacked days with per-crew cards. */
        .dispatch-page .d-agenda { display: flex; flex-direction: column; }
        .dispatch-page .d-agenda-head {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap;
            font-size: 16px; font-weight: 700; padding: 2px 2px 10px;
        }
        .dispatch-page .d-agenda-search {
            display: inline-flex; align-items: center; gap: 6px;
            height: 36px; padding: 0 10px; border-radius: 8px;
            border: 1px solid var(--d-border); background: var(--d-card-bg);
            min-width: 240px; max-width: 360px; flex: 1 1 240px;
        }
        .dispatch-page .d-agenda-search svg { width: 15px; height: 15px; color: var(--d-muted); flex-shrink: 0; }
        .dispatch-page .d-agenda-search-input {
            border: 0; outline: 0; background: transparent; color: var(--d-text);
            font: inherit; font-weight: 400; font-size: 14px; width: 100%; min-width: 0;
        }
        .dispatch-page .d-agenda-search-clear {
            border: 0; background: transparent; color: var(--d-muted);
            font-size: 18px; line-height: 1; cursor: pointer; padding: 0 2px; flex-shrink: 0;
        }
        .dispatch-page .d-agenda-search-clear:hover { color: var(--d-text); }
        .dispatch-page .d-agenda-day {
            display: flex; flex-direction: column; gap: 12px;
            padding: 16px 0; border-top: 1px solid var(--d-border);
        }
        .dispatch-page .d-agenda-day:first-of-type { border-top: 0; }
        .dispatch-page .d-agenda-dayhead { display: flex; align-items: baseline; gap: 8px; }
        .dispatch-page .d-agenda-weekday {
            font-size: 11px; font-weight: 600; letter-spacing: 0.05em; color: var(--d-muted);
        }
        .dispatch-page .d-agenda-daynum { font-size: 18px; font-weight: 700; line-height: 1.1; }
        .dispatch-page .d-agenda-day.is-today .d-agenda-daynum,
        .dispatch-page .d-agenda-day.is-today .d-agenda-weekday { color: var(--d-accent); }
        .dispatch-page .d-agenda-cards { display: flex; flex-wrap: wrap; gap: 12px; min-width: 0; }
        .dispatch-page .d-job-card {
            position: relative;
            width: 250px; max-width: 100%;
            background: var(--d-card-bg); border: 1px solid var(--d-border);
            border-radius: 12px; padding: 14px; color: var(--d-text);
            transition: box-shadow 120ms, border-color 120ms;
        }
        .dispatch-page .d-job-card:hover { border-color: var(--d-muted); box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .dispatch-page .d-job-card-open {
            display: block; width: 100%; text-align: left; font: inherit;
            background: none; border: 0; padding: 0; cursor: pointer; color: inherit;
        }
        /* Per-card 3-dot actions menu (top-right). */
        .dispatch-page .d-job-menu { position: absolute; top: 8px; right: 8px; }
        .dispatch-page .d-job-gear { position: absolute; top: 8px; right: 40px; }
        .dispatch-page .d-job-menu-head {
            font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;
            color: var(--d-muted); padding: 6px 10px 4px;
        }
        .dispatch-page .d-job-menu-item.is-current { color: var(--d-muted); cursor: default; }
        .dispatch-page .d-job-menu-item.is-current:hover { background: transparent; }
        .dispatch-page .d-crew-dot { display: inline-block; width: 9px; height: 9px; border-radius: 9999px; margin-right: 7px; vertical-align: middle; }
        .dispatch-page .d-job-menu-check { float: right; color: var(--d-muted); }
        .dispatch-page .d-job-menu-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 6px; cursor: pointer;
            border: 1px solid transparent; background: transparent; color: var(--d-muted);
        }
        .dispatch-page .d-job-menu-btn:hover { background: var(--d-hover); color: var(--d-text); border-color: var(--d-border); }
        .dispatch-page .d-job-menu-btn svg { width: 18px; height: 18px; }
        .dispatch-page .d-job-menu-list {
            position: absolute; top: 34px; right: 0; z-index: 30; min-width: 150px;
            background: var(--d-card-bg); border: 1px solid var(--d-border); border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15); padding: 4px;
            display: flex; flex-direction: column;
        }
        .dispatch-page .d-job-menu-item {
            font: inherit; font-size: 13px; font-weight: 500; text-align: left; cursor: pointer;
            padding: 7px 10px; border-radius: 6px; border: 0; background: transparent; color: var(--d-text);
        }
        .dispatch-page .d-job-menu-item:hover { background: var(--d-hover); }
        .dispatch-page .d-job-menu-item.danger { color: #dc2626; }
        .dispatch-page .d-job-menu-item.danger:hover { background: #dc2626; color: #fff; }

        /* Month view calendar grid. */
        .dispatch-page .d-month-head {
            display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 12px;
        }
        .dispatch-page .d-month-label { font-size: 16px; font-weight: 700; min-width: 160px; text-align: center; }
        .dispatch-page .d-month-grid {
            display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px;
        }
        .dispatch-page .d-month-dow {
            text-align: center; font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.05em; color: var(--d-muted); padding-bottom: 4px;
        }
        .dispatch-page .d-month-cell {
            font: inherit; text-align: left; min-height: 78px;
            background: var(--d-card-bg); border: 1px solid var(--d-border); border-radius: 10px;
            padding: 8px; display: flex; flex-direction: column; gap: 6px; color: var(--d-text);
            transition: border-color 120ms, box-shadow 120ms;
        }
        .dispatch-page .d-month-cell:hover { border-color: var(--d-accent); box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .dispatch-page .d-month-cell.is-out { opacity: 0.45; }
        .dispatch-page .d-month-cell.is-today { border-color: var(--d-accent); }
        .dispatch-page .d-month-cell.is-selected { box-shadow: 0 0 0 2px var(--d-accent); }
        .dispatch-page .d-month-daynum-btn {
            font: inherit; border: 0; background: transparent; padding: 0; cursor: pointer;
            align-self: flex-start; color: inherit;
        }
        .dispatch-page .d-month-daynum { font-size: 13px; font-weight: 600; }
        .dispatch-page .d-month-count {
            font-size: 11px; font-weight: 600; align-self: flex-start;
            background: color-mix(in srgb, var(--d-accent) 15%, transparent); color: var(--d-accent);
            padding: 1px 8px; border-radius: 9999px;
        }
        /* One crew per row, full-width and clickable → that day's filtered map. */
        .dispatch-page .d-month-crews { display: flex; flex-direction: column; gap: 4px; align-self: stretch; }
        .dispatch-page .d-month-crew {
            font: inherit; cursor: pointer; text-align: left; width: 100%;
            display: flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 600; line-height: 1.2;
            padding: 4px 8px; border-radius: 6px; border: 1px solid transparent;
            background: color-mix(in srgb, var(--cc) 12%, transparent); color: var(--cc);
            transition: border-color 120ms, background 120ms;
        }
        .dispatch-page .d-month-crew:hover { border-color: var(--cc); background: color-mix(in srgb, var(--cc) 22%, transparent); }
        .dispatch-page .d-month-crew-dot { width: 7px; height: 7px; border-radius: 9999px; background: var(--cc); flex-shrink: 0; }
        .dispatch-page .d-month-crew-name { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dispatch-page .d-month-crew-count { flex-shrink: 0; opacity: 0.85; font-weight: 700; }
        .dispatch-page .d-crew-badge {
            display: inline-block; font-size: 11px; font-weight: 700;
            padding: 2px 10px; border-radius: 9999px; margin-bottom: 10px;
        }
        .dispatch-page .d-dnm-badge {
            display: inline-flex; align-items: center; gap: 4px; vertical-align: top;
            font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em;
            padding: 2px 8px; border-radius: 9999px; margin: 0 0 10px 6px;
            background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;
        }
        .dispatch-page .d-dnm-badge svg { width: 11px; height: 11px; }
        .dark .dispatch-page .d-dnm-badge { background: rgba(220,38,38,0.18); color: #fca5a5; border-color: rgba(220,38,38,0.4); }
        .dispatch-page .d-job-card-title { font-size: 14px; font-weight: 600; line-height: 1.35; }
        .dispatch-page .d-job-card-sub { font-size: 13px; color: var(--d-muted); margin-top: 4px; }
        .dispatch-page .d-job-card-status {
            display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--d-muted);
            margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--d-border);
        }
        .dispatch-page .d-agenda-empty { color: var(--d-muted); font-size: 13px; padding: 4px 0; }
        .dispatch-page .d-dot.scheduled { background: #2563eb; }
        .dispatch-page .d-dot.complete { background: #16a34a; }
        .dispatch-page .d-dot.in_progress { background: #d97706; }
        .dispatch-page .d-map-wrap {
            position: relative; min-height: calc(100vh - 200px); border-radius: 12px;
            border: 1px solid var(--d-border); background: rgb(243, 244, 246); overflow: hidden;
        }
        .dark .dispatch-page .d-map-wrap { background: rgb(31, 41, 55); }
        .dispatch-page .d-map-host { width: 100%; height: 100%; min-height: calc(100vh - 200px); }
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
        .dispatch-page .d-btn-fix {
            flex-shrink: 0; height: 24px; padding: 0 10px; font-size: 11px; font-weight: 600;
            border-radius: 6px; cursor: pointer;
            background: rgb(120, 53, 15); color: #fff; border: 1px solid transparent;
        }
        .dispatch-page .d-btn-fix:hover { opacity: 0.9; }
        .dispatch-page .d-btn-fix:disabled { opacity: 0.6; cursor: default; }
        .dark .dispatch-page .d-btn-fix { background: rgb(245, 158, 11); color: rgb(30, 20, 5); }
        .dispatch-page .d-warning code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 11px; padding: 1px 4px; border-radius: 4px;
            background: rgba(0,0,0,0.06);
        }
        .dispatch-page .d-status-dot { font-size: 12px; line-height: 1; }
        .dispatch-page .d-status-dot.completed { color: rgb(22, 163, 74); }
        .dispatch-page .d-status-dot.skipped { color: rgb(220, 38, 38); }
        .dispatch-page .d-status-dot.in_progress { color: rgb(217, 119, 6); }
        .dispatch-page .d-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600;
            border: 1px solid var(--d-border); background: var(--d-card-bg); color: var(--d-text);
        }
        .dispatch-page .d-badge.pending { background: #f1f5f9; color: rgb(71, 85, 105); border-color: rgb(203, 213, 225); }
        .dispatch-page .d-badge.in_progress { background: #fef3c7; color: rgb(120, 53, 15); border-color: rgb(252, 211, 77); }
        .dispatch-page .d-badge.completed { background: #dcfce7; color: rgb(22, 101, 52); border-color: rgb(134, 239, 172); }
        .dispatch-page .d-badge.skipped { background: #fee2e2; color: rgb(153, 27, 27); border-color: rgb(252, 165, 165); }
        .dispatch-page .d-btn-skip {
            border-color: rgb(252, 165, 165); color: rgb(153, 27, 27); background: #fff;
        }
        .dispatch-page .d-btn-skip:hover { background: #fee2e2; }
        .dispatch-page .d-modal-backdrop {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 1000;
            display: flex; align-items: center; justify-content: center; padding: 16px;
        }
        .dispatch-page .d-modal {
            background: var(--d-card-bg); border-radius: 12px; padding: 24px;
            max-width: 440px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .dispatch-page .d-modal-title { font-size: 16px; font-weight: 600; }
        .dispatch-page .d-modal-body { font-size: 14px; color: var(--d-muted); margin-top: 8px; line-height: 1.5; }
        .dispatch-page .d-modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
        .dispatch-page .d-btn-danger {
            background: #dc2626; color: #fff; border-color: #dc2626;
        }
        .dispatch-page .d-btn-danger:hover { background: #b91c1c; border-color: #b91c1c; }
        .dispatch-page .d-btn-chat {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--d-accent); color: #fff; border-color: var(--d-accent);
        }
        .dispatch-page .d-btn-chat:hover { filter: brightness(0.95); }
        .dispatch-page .d-btn-chat .d-unread-pill {
            background: #fff; color: var(--d-accent);
            font-size: 10px; font-weight: 700; line-height: 1;
            padding: 2px 6px; border-radius: 9999px;
        }
        .dispatch-page .d-chat-panel {
            position: fixed; top: 0; right: 0; height: 100vh; width: 100%;
            max-width: 420px; background: var(--d-card-bg);
            border-left: 1px solid var(--d-border);
            box-shadow: -8px 0 24px rgba(0,0,0,0.08);
            z-index: 900; display: flex; flex-direction: column;
            transform: translateX(100%); transition: transform 220ms ease;
        }
        .dispatch-page .d-chat-panel.is-open { transform: translateX(0); }
        .dispatch-page .d-chat-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 16px; border-bottom: 1px solid var(--d-border);
        }
        .dispatch-page .d-chat-header-main {
            display: flex; align-items: center; gap: 10px; min-width: 0;
        }
        .dispatch-page .d-chat-name { font-weight: 600; font-size: 14px; }
        .dispatch-page .d-chat-sub { font-size: 12px; color: var(--d-muted); }
        .dispatch-page .d-chat-body {
            flex: 1 1 auto; overflow-y: auto; padding: 14px 16px;
            display: flex; flex-direction: column; gap: 8px;
            background: rgb(249, 250, 251);
        }
        .dark .dispatch-page .d-chat-body { background: rgb(15, 23, 42); }
        .dispatch-page .d-chat-footer {
            padding: 12px 16px; border-top: 1px solid var(--d-border);
            background: var(--d-card-bg);
        }
        .dispatch-page .d-chat-bubble {
            max-width: 82%; padding: 8px 11px; border-radius: 12px;
            font-size: 13px; line-height: 1.45; word-wrap: break-word;
        }
        .dispatch-page .d-chat-bubble.office {
            background: var(--d-accent); color: #fff; align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        .dispatch-page .d-chat-bubble.foreman {
            background: #fff; color: rgb(15, 23, 42);
            border: 1px solid var(--d-border); align-self: flex-start;
            border-bottom-left-radius: 4px;
        }
        .dark .dispatch-page .d-chat-bubble.foreman { background: rgb(30, 41, 59); color: #f1f5f9; }
        .dispatch-page .d-chat-meta {
            font-size: 10px; opacity: 0.7; margin-top: 4px;
        }
        .dispatch-page .d-chat-empty {
            margin: auto; text-align: center; color: var(--d-muted); font-size: 13px; padding: 32px;
        }
        .dispatch-page .d-chat-backdrop {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.35);
            z-index: 899; opacity: 0; pointer-events: none; transition: opacity 180ms ease;
        }
        .dispatch-page .d-chat-backdrop.is-open { opacity: 1; pointer-events: auto; }
    </style>

    <div
        class="dispatch-page"
        x-data="dispatchBoard($wire, {
            pins: @js($pins),
            foremen: @js($foremen),
            crewColors: @js($crewColors),
            apiKey: @js($mapsApiKey ?? ''),
            serviceIcons: @js($serviceIcons),
        })"
        x-init="init()"
    >
        <div class="d-stack">
            {{-- Filter bar --}}
            <div class="d-bar">
                <div class="d-row-wrap">
                    <div class="d-row">
                        @php
                            $dayGranularity = $this->viewMode === 'map'
                                || ($this->viewMode === 'list' && $this->listRange === 'day');
                            $unit = $this->viewMode === 'month'
                                ? 'month'
                                : (($this->viewMode === 'list' && $this->listRange === 'week') ? 'week' : 'day');
                            $today = now()->toDateString();
                            $tomorrow = now()->addDay()->toDateString();
                            $yesterday = now()->subDay()->toDateString();
                            $activeStyle = 'background: var(--d-accent); color: #fff; border-color: var(--d-accent);';
                        @endphp
                        <button type="button" wire:click="shiftPeriod(-1)" class="d-icon-btn" title="Previous {{ $unit }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        {{-- The single date control adapts to the view: a month picker in Month view, a day picker otherwise. --}}
                        @if ($this->viewMode === 'month')
                            <input type="month" value="{{ \Carbon\Carbon::parse($this->date)->format('Y-m') }}" wire:change="goToMonth($event.target.value)" class="d-input">
                        @else
                            <input type="date" wire:model.live="date" class="d-input">
                        @endif
                        <button type="button" wire:click="shiftPeriod(1)" class="d-icon-btn" title="Next {{ $unit }}">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>

                        @if ($dayGranularity)
                            <button type="button" wire:click="$set('date', '{{ $yesterday }}')" class="d-btn" @if ($this->date === $yesterday) style="{{ $activeStyle }}" @endif>Yesterday</button>
                        @endif
                        <button type="button" wire:click="$set('date', '{{ $today }}')" class="d-btn" @if ($this->date === $today) style="{{ $activeStyle }}" @endif>Today</button>
                        @if ($dayGranularity)
                            <button type="button" wire:click="$set('date', '{{ $tomorrow }}')" class="d-btn" @if ($this->date === $tomorrow) style="{{ $activeStyle }}" @endif>Tomorrow</button>
                        @endif

                        {{-- View-aware period label — single source of truth for every view. --}}
                        <div class="d-weekday" style="margin-left:8px; line-height:1.1;">
                            @if ($this->viewMode === 'month')
                                <div style="font-size:15px; font-weight:700;">{{ $this->monthLabel }}</div>
                            @elseif ($this->viewMode === 'list' && $this->listRange === 'week')
                                <div style="font-size:15px; font-weight:700;">{{ $this->listRangeLabel }}</div>
                                <div style="font-size:12px; color:var(--d-muted);">Week view</div>
                            @else
                                <div style="font-size:15px; font-weight:700;">{{ \Carbon\Carbon::parse($this->date)->format('l') }}</div>
                                <div style="font-size:12px; color:var(--d-muted);">{{ \Carbon\Carbon::parse($this->date)->format('M j, Y') }}</div>
                            @endif
                        </div>

                        {{-- Create a job on the fly (issue #55). Sits right by the date so
                             it's easy to reach; sized to match the Today button. --}}
                        <button type="button" wire:click="openNewJobModal" class="d-btn" style="margin-left:8px;height:32px;background:var(--d-accent);color:#fff;border-color:var(--d-accent);font-weight:600;">
                            + New Job
                        </button>
                    </div>

                    <div class="d-spacer"></div>

                    {{-- View control: Map / Day / Week / Month. Day + Week are both the
                         list layout, so "List" isn't a separate choice (issue #24). --}}
                    @php
                        $onDay = $this->viewMode === 'list' && $this->listRange === 'day';
                        $onWeek = $this->viewMode === 'list' && $this->listRange === 'week';
                        $segActive = 'background: var(--d-accent); color:#fff;';
                    @endphp
                    <div class="d-row" style="gap:0;border:1px solid var(--d-border);border-radius:8px;overflow:hidden;">
                        <button
                            type="button"
                            wire:click="setDispatchView('map')"
                            class="d-btn"
                            style="height:32px;border:none;border-radius:0;{{ $this->viewMode === 'map' ? $segActive : '' }}"
                            title="Map view"
                        >Map</button>
                        <button
                            type="button"
                            wire:click="setDispatchView('day')"
                            class="d-btn"
                            style="height:32px;border:none;border-radius:0;{{ $onDay ? $segActive : '' }}"
                            title="Show the selected day"
                        >Day</button>
                        <button
                            type="button"
                            wire:click="setDispatchView('week')"
                            class="d-btn"
                            style="height:32px;border:none;border-radius:0;{{ $onWeek ? $segActive : '' }}"
                            title="Show the whole week (Mon–Sat)"
                        >Week</button>
                        <button
                            type="button"
                            wire:click="setDispatchView('month')"
                            class="d-btn"
                            style="height:32px;border:none;border-radius:0;{{ $this->viewMode === 'month' ? $segActive : '' }}"
                            title="Month view"
                        >Month</button>
                    </div>

                    <button
                        type="button"
                        wire:click="toggleGps"
                        class="d-chip"
                        @if ($this->showGps) style="background: var(--d-accent); color: #fff; border-color: var(--d-accent);" @endif
                        title="Show or hide live crew foreman GPS pins"
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

                {{-- Crews on their own row beneath the date selection. The "Crews" button is
                     pinned at the left so it never shifts as crew chips are toggled (issue #24). --}}
                <div class="d-row-wrap" style="margin-top: 8px;">
                    {{-- Crew visibility manager (issue #24): show/hide which crew chips appear. --}}
                    <div x-data="{ open: false }" style="position: relative;">
                        <button type="button" @click="open = !open" class="d-btn" style="height:28px;padding:0 10px;font-size:12px;display:inline-flex;align-items:center;gap:4px;" title="Choose which crews are visible">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            Crews
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false" style="position:absolute;z-index:40;top:calc(100% + 4px);left:0;min-width:200px;background:var(--d-card-bg);border:1px solid var(--d-border);border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,0.15);padding:8px;">
                            <div class="d-label" style="padding:4px 8px;">Visible crews</div>
                            @foreach ($crewColors as $cId => $c)
                                <label style="display:flex;align-items:center;gap:8px;padding:6px 8px;cursor:pointer;font-size:13px;border-radius:6px;">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleCrewVisibility({{ $cId }})"
                                        @checked(! in_array((int) $cId, array_map('intval', $this->hiddenCrewIds), true))
                                        style="width:16px;height:16px;accent-color:var(--d-accent);"
                                    >
                                    <span class="d-dot" style="background-color: {{ $c['color'] }}"></span>
                                    {{ $c['name'] }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    @foreach ($this->visibleCrews as $crewId => $crew)
                        @php
                            $crewIds = array_map('intval', $this->crewIds);
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

                {{-- Service-group filters (issue #15): show only jobs offering these services. --}}
                <div class="d-row-wrap" style="margin-top: 8px;">
                    <span class="d-label" style="align-self:center;">Services</span>
                    @foreach (['spraying', 'mowing', 'mulching'] as $group)
                        @php $isGroupActive = in_array($group, $this->serviceGroups, true); @endphp
                        <button
                            type="button"
                            wire:click="toggleServiceGroup('{{ $group }}')"
                            class="d-chip {{ $isGroupActive ? 'is-active' : '' }}"
                            @if ($isGroupActive) style="background: var(--d-accent); color: #fff; border-color: var(--d-accent);" @endif
                        >{{ $serviceGroups[$group] }}</button>
                    @endforeach
                    @if (! empty($this->serviceGroups))
                        <button type="button" wire:click="$set('serviceGroups', [])" class="d-btn" style="height:28px;padding:0 10px;font-size:12px;">Clear</button>
                    @endif
                </div>
            </div>

            @if ($this->viewMode === 'list')
            {{-- List view: day-by-day agenda of per-crew cards (Day / Week range). --}}
            <div class="d-bar">
                <div class="d-agenda">
                    <div class="d-agenda-head">
                        {{-- Range label lives in the shared top date control now; keep search right-aligned. --}}
                        <span></span>
                        <label class="d-agenda-search">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                            <input
                                type="search"
                                wire:model.live.debounce.300ms="listSearch"
                                placeholder="Search customer, property, or service…"
                                class="d-agenda-search-input"
                                autocomplete="off"
                            >
                            @if (trim($this->listSearch) !== '')
                                <button type="button" wire:click="$set('listSearch', '')" class="d-agenda-search-clear" title="Clear search" aria-label="Clear search">&times;</button>
                            @endif
                        </label>
                    </div>
                    @forelse ($this->listDays as $day)
                        <div class="d-agenda-day {{ $day['is_today'] ? 'is-today' : '' }}">
                            @if ($this->listRange === 'week')
                                <div class="d-agenda-dayhead">
                                    <span class="d-agenda-weekday">{{ $day['weekday'] }}</span>
                                    <span class="d-agenda-daynum">{{ $day['day_num'] }}</span>
                                </div>
                            @endif
                            <div class="d-agenda-cards">
                                @forelse ($day['jobs'] as $card)
                                    <div wire:key="job-card-{{ $card['id'] }}" class="d-job-card" x-data="{ menu: false }">
                                        <button
                                            type="button"
                                            wire:click="openStopDetails({{ $card['id'] }})"
                                            class="d-job-card-open"
                                            title="View job details"
                                        >
                                            <span class="d-crew-badge" style="background: {{ $card['color'] }}1a; color: {{ $card['color'] }};">{{ $card['crew_name'] }}</span>
                                            @if ($card['do_not_move'])
                                                <span class="d-dnm-badge" title="Do Not Move — keep this job's scheduled date">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                                    Do Not Move
                                                </span>
                                            @endif
                                            <div class="d-job-card-title">{{ $card['customer_name'] }}</div>
                                            <div class="d-job-card-sub">{{ $card['address'] }}</div>
                                            @if ($card['service_summary'])
                                                <div class="d-job-card-sub">{{ $card['service_summary'] }}</div>
                                            @endif
                                            <div class="d-job-card-status">
                                                <span class="d-dot {{ $card['status_kind'] }}"></span>
                                                {{ $card['status_label'] }}
                                            </div>
                                        </button>

                                        {{-- Reassign to another crew on the fly (issue #55). --}}
                                        <div class="d-job-gear" x-data="{ gear: false }" @click.outside="gear = false">
                                            <button type="button" class="d-job-menu-btn" @click="gear = !gear" title="Reassign crew" aria-label="Reassign crew" aria-haspopup="true" :aria-expanded="gear">
                                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                            </button>
                                            <div class="d-job-menu-list" x-show="gear" x-cloak @click="gear = false">
                                                <div class="d-job-menu-head">Reassign to crew</div>
                                                @foreach ($this->crewColorMap() as $crew)
                                                    <button type="button"
                                                        class="d-job-menu-item {{ (int) $crew['id'] === (int) $card['crew_id'] ? 'is-current' : '' }}"
                                                        @if ((int) $crew['id'] !== (int) $card['crew_id']) wire:click="reassignStopToCrew({{ $card['id'] }}, {{ $crew['id'] }})" @endif>
                                                        <span class="d-crew-dot" style="background: {{ $crew['color'] }};"></span>
                                                        {{ $crew['name'] }}
                                                        @if ((int) $crew['id'] === (int) $card['crew_id'])<span class="d-job-menu-check">✓</span>@endif
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>

                                        {{-- Quick actions collapsed into a top-right 3-dot menu. --}}
                                        <div class="d-job-menu" @click.outside="menu = false">
                                            <button type="button" class="d-job-menu-btn" @click="menu = !menu" title="Job actions" aria-label="Job actions" aria-haspopup="true" :aria-expanded="menu">
                                                <svg fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                                            </button>
                                            <div class="d-job-menu-list" x-show="menu" x-cloak @click="menu = false">
                                                <button type="button" class="d-job-menu-item" wire:click="markStopStatus({{ $card['id'] }}, 'completed')">Complete</button>
                                                <button type="button" class="d-job-menu-item" wire:click="markStopStatus({{ $card['id'] }}, 'in_progress')">Start</button>
                                                <button type="button" class="d-job-menu-item" wire:click="requestSkip({{ $card['id'] }})">Skip</button>
                                                <button type="button" class="d-job-menu-item danger" wire:click="cancelStop({{ $card['id'] }})" wire:confirm="Cancel this job? The crew's field staff will be notified.">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="d-agenda-empty">No jobs scheduled.</div>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        @if (trim($this->listSearch) !== '')
                            <div class="d-list-empty">No jobs match “{{ $this->listSearch }}” this {{ $this->listRange === 'week' ? 'week' : 'day' }}.</div>
                        @else
                            <div class="d-list-empty">No jobs scheduled for this {{ $this->listRange === 'week' ? 'week' : 'day' }}.</div>
                        @endif
                    @endforelse
                </div>
            </div>
            @elseif ($this->viewMode === 'month')
            {{-- Month view: calendar of scheduled workload; click a day to drill into it.
                 Month navigation lives in the shared top date control (no second nav). --}}
            <div class="d-bar">
                <div class="d-month-grid">
                    @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)
                        <div class="d-month-dow">{{ $dow }}</div>
                    @endforeach
                    @foreach ($this->monthDays as $day)
                        <div
                            wire:key="month-{{ $day['date'] }}"
                            class="d-month-cell {{ $day['in_month'] ? '' : 'is-out' }} {{ $day['is_today'] ? 'is-today' : '' }} {{ $day['is_selected'] ? 'is-selected' : '' }}"
                        >
                            {{-- The day number opens the day's list view. --}}
                            <button
                                type="button"
                                wire:click="goToDay('{{ $day['date'] }}')"
                                class="d-month-daynum-btn"
                                title="View {{ \Carbon\Carbon::parse($day['date'])->format('l, M j') }}"
                            >
                                <span class="d-month-daynum">{{ $day['day_num'] }}</span>
                            </button>
                            @if (! empty($day['crews']))
                                {{-- One row per crew: "Crew — N jobs", clickable to that day's
                                     map filtered to the crew. --}}
                                <div class="d-month-crews">
                                    @foreach ($day['crews'] as $c)
                                        <button
                                            type="button"
                                            wire:click="goToDayCrewMap('{{ $day['date'] }}', {{ $c['crew_id'] }})"
                                            class="d-month-crew"
                                            style="--cc: {{ $c['color'] }};"
                                            title="View {{ $c['name'] }} on {{ \Carbon\Carbon::parse($day['date'])->format('M j') }} on the map"
                                        >
                                            <span class="d-month-crew-dot"></span>
                                            <span class="d-month-crew-name">{{ $c['name'] }}</span>
                                            <span class="d-month-crew-count">{{ $c['count'] }} {{ \Illuminate\Support\Str::plural('job', $c['count']) }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @else
            {{-- Map + side panel --}}
            <div class="d-grid">
                @if ($this->viewMode === 'map')
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
                @endif

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
                            <div class="d-field">
                                <div class="d-label">Location</div>
                                @if ($selectedForeman['has_location'])
                                    <div class="d-field-val" style="display:flex;align-items:center;gap:6px;">
                                        <span style="width:8px;height:8px;border-radius:9999px;display:inline-block;background:{{ $selectedForeman['is_live'] ? '#16a34a' : '#f59e0b' }};"></span>
                                        {{ $selectedForeman['is_live'] ? 'Live GPS' : 'Last known position' }}
                                        <span class="d-muted" style="font-size:12px;">· {{ $selectedForeman['last_seen'] }}</span>
                                    </div>
                                @else
                                    <div class="d-field-val d-muted" style="font-size:12px;">
                                        Estimated position — foreman app not reporting GPS yet.
                                    </div>
                                @endif
                            </div>

                            {{-- Open the chat side panel --}}
                            <div class="d-field">
                                <button
                                    type="button"
                                    wire:click="openChatPanel"
                                    class="d-btn d-btn-chat"
                                    style="width:100%; justify-content:center;"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    </svg>
                                    Chat
                                    @if (! empty($selectedForeman['unread_chat']))
                                        <span class="d-unread-pill">{{ $selectedForeman['unread_chat'] > 9 ? '9+' : $selectedForeman['unread_chat'] }}</span>
                                    @endif
                                </button>
                            </div>
                        </div>
                    @elseif ($selectedJob)
                        <div class="d-card">
                            <div class="d-row" style="justify-content: space-between;">
                                <div class="d-row">
                                    <span class="d-dot" style="background-color: #9ca3af"></span>
                                    <span class="d-card-title">Unrouted job</span>
                                </div>
                                <div class="d-row" style="gap:6px;">
                                    <button type="button" wire:click="openServicesModal" class="d-btn" style="height: 28px; padding: 0 10px; font-size: 12px;" title="Services, notes &amp; details for this job">Services</button>
                                    <button type="button" wire:click="clearSelection" class="d-btn" style="height: 28px; padding: 0 8px; font-size: 12px;">Close</button>
                                </div>
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
                                <div class="d-row" style="gap:6px;">
                                    <button type="button" wire:click="openServicesModal" class="d-btn" style="height: 28px; padding: 0 10px; font-size: 12px;" title="Services, notes &amp; details for this job">Services</button>
                                    <button
                                        type="button"
                                        wire:click="requestSkip({{ $selected['id'] }})"
                                        class="d-btn d-btn-skip"
                                        style="height: 28px; padding: 0 10px; font-size: 12px;"
                                        title="Skip this job for today and send back to unscheduled"
                                    >Skip</button>
                                    <button type="button" wire:click="clearSelection" class="d-btn" style="height: 28px; padding: 0 8px; font-size: 12px;">Close</button>
                                </div>
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
                                @php
                                    $statusLabels = [
                                        'pending' => 'Pending',
                                        'in_progress' => 'In Progress',
                                        'completed' => 'Completed',
                                        'skipped' => 'Skipped',
                                    ];
                                    $statusKey = $selected['status'] ?? 'pending';
                                    $statusLabel = $statusLabels[$statusKey] ?? ucfirst($statusKey);
                                @endphp
                                <div style="margin-top:6px;">
                                    <span class="d-badge {{ $statusKey }}">{{ $statusLabel }}</span>
                                </div>
                                <div class="d-muted" style="font-size: 11px; margin-top: 6px;">
                                    Status is updated by the crew foreman as they clock in/out via the app.
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
                                    @if ($serviceIcons && ! empty($stop['icon_url']))
                                        <img src="{{ $stop['icon_url'] }}" alt="" style="width:22px;height:22px;border-radius:4px;object-fit:contain;flex-shrink:0;">
                                    @endif
                                    <div class="d-list-main">
                                        <div class="d-truncate">{{ $stop['customer_name'] }}</div>
                                        <div class="d-truncate d-muted" style="font-size: 12px;">{{ $stop['address'] }}{{ $stop['service_name'] ? ' · ' . $stop['service_name'] : '' }}</div>
                                        @if ($this->viewMode === 'list')
                                            <div class="d-list-extra">
                                                @if ($stop['crew_name'])<span>{{ $stop['crew_name'] }}</span>@endif
                                                @if ($stop['city'])<span>{{ $stop['city'] }}</span>@endif
                                                <span>{{ ucfirst(str_replace('_', ' ', $stop['status'])) }}</span>
                                                @if ($stop['customer_phone'])<span>{{ $stop['customer_phone'] }}</span>@endif
                                                @if (! empty($stop['job_title']))<span>{{ $stop['job_title'] }}</span>@endif
                                            </div>
                                        @endif
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
                                    @if ($serviceIcons && ! empty($job['icon_url']))
                                        <img src="{{ $job['icon_url'] }}" alt="" style="width:22px;height:22px;border-radius:4px;object-fit:contain;flex-shrink:0;">
                                    @endif
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
                            <div class="d-row" style="justify-content: space-between; align-items: flex-start; gap: 8px;">
                                <div class="d-card-title">{{ count($unmapped) }} {{ \Illuminate\Support\Str::plural('stop', count($unmapped)) }} without coordinates</div>
                                <button type="button"
                                    wire:click="geocodeAllUnmapped"
                                    wire:loading.attr="disabled"
                                    wire:target="geocodeAllUnmapped"
                                    class="d-btn d-btn-fix">
                                    <span wire:loading.remove wire:target="geocodeAllUnmapped">Fix all</span>
                                    <span wire:loading wire:target="geocodeAllUnmapped">Locating…</span>
                                </button>
                            </div>
                            <div class="d-warning-sub">These addresses aren't on the map yet. Click <strong>Fix</strong> to look up coordinates from the address on file.</div>
                            @if ($geocodeNotice)
                                <div class="d-warning-sub" style="margin-top: 8px; font-weight: 600;">{{ $geocodeNotice }}</div>
                            @endif
                            <ul>
                                @foreach (array_slice($unmapped, 0, 5) as $row)
                                    <li class="d-row" style="justify-content: space-between; gap: 8px;">
                                        <span class="d-truncate">• {{ $row['customer_name'] }} — {{ $row['address'] }}</span>
                                        @if ($row['property_id'])
                                            <button type="button"
                                                wire:click="geocodeStopProperty({{ $row['id'] }})"
                                                wire:loading.attr="disabled"
                                                wire:target="geocodeStopProperty({{ $row['id'] }})"
                                                class="d-btn d-btn-fix">
                                                <span wire:loading.remove wire:target="geocodeStopProperty({{ $row['id'] }})">Fix</span>
                                                <span wire:loading wire:target="geocodeStopProperty({{ $row['id'] }})">…</span>
                                            </button>
                                        @endif
                                    </li>
                                @endforeach
                                @if (count($unmapped) > 5)
                                    <li style="opacity: 0.7;">… and {{ count($unmapped) - 5 }} more (use “Fix all”)</li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Right-side chat panel + backdrop. Rendered always so CSS transitions can play. --}}
        <div
            class="d-chat-backdrop {{ $chatPanelOpen ? 'is-open' : '' }}"
            wire:click="closeChatPanel"
        ></div>
        <aside
            class="d-chat-panel {{ $chatPanelOpen ? 'is-open' : '' }}"
            aria-hidden="{{ $chatPanelOpen ? 'false' : 'true' }}"
            wire:poll.8s
        >
            @if ($selectedForeman)
                <div class="d-chat-header">
                    <div class="d-chat-header-main">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:9999px;background:{{ $selectedForeman['color'] }};color:#fff;font-weight:700;font-size:13px;flex-shrink:0;">
                            {{ $selectedForeman['initials'] }}
                        </span>
                        <div style="min-width:0;">
                            <div class="d-chat-name d-truncate">{{ $selectedForeman['name'] }}</div>
                            <div class="d-chat-sub d-truncate">{{ $selectedForeman['crew_name'] }} foreman</div>
                        </div>
                    </div>
                    <button type="button" wire:click="closeChatPanel" class="d-btn" style="height:30px;padding:0 10px;font-size:12px;">Close</button>
                </div>

                <div
                    class="d-chat-body"
                    x-data
                    x-ref="body"
                    x-init="$nextTick(() => $refs.body.scrollTop = $refs.body.scrollHeight)"
                    @dispatch:chat-updated.window="$nextTick(() => $refs.body.scrollTop = $refs.body.scrollHeight)"
                    wire:key="chat-body-{{ $selectedForeman['id'] }}"
                >
                    @forelse ($this->chatMessages as $msg)
                        <div class="d-chat-bubble {{ $msg['sender'] === 'office' ? 'office' : 'foreman' }}">
                            @if ($msg['attachment_url'])
                                @if ($msg['attachment_type'] === 'video')
                                    <video src="{{ $msg['attachment_url'] }}" controls style="max-width:240px;border-radius:8px;display:block;margin-bottom:6px;"></video>
                                @elseif ($msg['attachment_type'] === 'file')
                                    <a href="{{ $msg['attachment_url'] }}" target="_blank" rel="noopener" download
                                       style="display:flex;align-items:center;gap:8px;padding:8px 10px;margin-bottom:6px;border-radius:8px;background:rgba(15,23,42,0.06);text-decoration:none;color:inherit;max-width:240px;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                        <span class="d-truncate" style="font-size:12px;font-weight:600;">{{ $msg['attachment_name'] ?: 'Download file' }}</span>
                                    </a>
                                @else
                                    <a href="{{ $msg['attachment_url'] }}" target="_blank" rel="noopener">
                                        <img src="{{ $msg['attachment_url'] }}" alt="attachment" style="max-width:240px;border-radius:8px;display:block;margin-bottom:6px;">
                                    </a>
                                @endif
                            @endif
                            @if ($msg['body'])
                                <div>{{ $msg['body'] }}</div>
                            @endif
                            <div class="d-chat-meta">{{ $msg['sender_name'] }} · {{ $msg['time'] }}</div>
                        </div>
                    @empty
                        <div class="d-chat-empty">No messages yet — start the conversation.</div>
                    @endforelse
                </div>

                <div class="d-chat-footer">
                    <div wire:loading wire:target="chatAttachment" class="d-muted" style="font-size:11px;margin-bottom:6px;">Uploading attachment…</div>
                    <form wire:submit="sendChat" style="display:flex;gap:6px;align-items:center;">
                        <label title="Attach a photo, video, or file" style="height:36px;width:36px;flex-shrink:0;display:flex;align-items:center;justify-content:center;border:1px solid var(--d-border);border-radius:8px;cursor:pointer;color:var(--d-muted);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            <input type="file" wire:model="chatAttachment" style="display:none;">
                        </label>
                        <input
                            type="text"
                            wire:model="chatBody"
                            placeholder="Message {{ $selectedForeman['name'] }}…"
                            autocomplete="off"
                            style="flex:1;height:36px;border:1px solid var(--d-border);border-radius:8px;padding:0 12px;font-size:13px;background:#fff;color:#0f172a;"
                        >
                        <button type="submit" class="d-btn" style="height:36px;padding:0 14px;font-size:13px;background:var(--d-accent);color:#fff;border-color:var(--d-accent);">Send</button>
                    </form>
                </div>
            @endif
        </aside>

        {{-- New Job modal (issue #55): create a job on the fly from the board. --}}
        @if ($showNewJobModal)
            <div class="d-modal-backdrop" wire:click.self="closeNewJobModal">
                <form wire:submit="createNewJob" class="d-modal" role="dialog" aria-modal="true" aria-labelledby="newjob-modal-title" style="max-width:520px;">
                    <div class="d-row" style="justify-content:space-between; align-items:flex-start;">
                        <div class="d-modal-title" id="newjob-modal-title">New job</div>
                        <button type="button" wire:click="closeNewJobModal" class="d-btn" style="height:28px; padding:0 10px; font-size:12px;">Close</button>
                    </div>
                    <div class="d-modal-body" style="display:flex; flex-direction:column; gap:12px;">
                        {{-- Customer search --}}
                        <div style="position:relative;">
                            <div class="d-label">Customer</div>
                            <input type="text" wire:model.live.debounce.300ms="newJobCustomerSearch" placeholder="Search customers…" autocomplete="off"
                                style="width:100%; height:38px; border:1px solid var(--d-border); border-radius:8px; padding:0 12px; font-size:13px; background:#fff; color:#0f172a; box-sizing:border-box;">
                            @if (! $newJob['customer_id'] && count($this->newJobCustomerResults) > 0)
                                <div style="position:absolute; z-index:40; left:0; right:0; margin-top:4px; background:var(--d-card-bg); border:1px solid var(--d-border); border-radius:10px; box-shadow:0 10px 25px rgba(0,0,0,0.15); max-height:220px; overflow-y:auto;">
                                    @foreach ($this->newJobCustomerResults as $c)
                                        <button type="button" wire:click="selectNewJobCustomer({{ $c['id'] }})"
                                            style="display:block; width:100%; text-align:left; padding:9px 12px; border:0; background:transparent; cursor:pointer; font-size:13px; color:var(--d-text);"
                                            onmouseover="this.style.background='var(--d-hover)'" onmouseout="this.style.background='transparent'">{{ $c['label'] }}</button>
                                    @endforeach
                                </div>
                            @endif
                            @error('newJob.customer_id') <div style="color:#dc2626; font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
                        </div>

                        {{-- Property --}}
                        <div>
                            <div class="d-label">Property</div>
                            <select wire:model="newJob.property_id" @disabled(! $newJob['customer_id'])
                                style="width:100%; height:38px; border:1px solid var(--d-border); border-radius:8px; padding:0 10px; font-size:13px; background:#fff; color:#0f172a;">
                                <option value="">{{ $newJob['customer_id'] ? 'Select a property' : 'Pick a customer first' }}</option>
                                @foreach ($this->newJobProperties as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Title --}}
                        <div>
                            <div class="d-label">Job title</div>
                            <input type="text" wire:model="newJob.title" placeholder="Mowing, Mulch install, Spring cleanup…" autocomplete="off"
                                style="width:100%; height:38px; border:1px solid var(--d-border); border-radius:8px; padding:0 12px; font-size:13px; background:#fff; color:#0f172a; box-sizing:border-box;">
                            @error('newJob.title') <div style="color:#dc2626; font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror
                        </div>

                        {{-- Services (added as TBD; priced later on the job) --}}
                        <div>
                            <div class="d-label">Services <span class="d-muted" style="font-weight:400;">(optional — priced later)</span></div>
                            <select wire:model="newJob.service_ids" multiple size="4"
                                style="width:100%; border:1px solid var(--d-border); border-radius:8px; padding:6px; font-size:13px; background:#fff; color:#0f172a;">
                                @foreach ($this->newJobServiceOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-row" style="gap:12px;">
                            <div style="flex:1;">
                                <div class="d-label">Scheduled date</div>
                                <input type="date" wire:model="newJob.scheduled_date"
                                    style="width:100%; height:38px; border:1px solid var(--d-border); border-radius:8px; padding:0 10px; font-size:13px; background:#fff; color:#0f172a; box-sizing:border-box;">
                            </div>
                            <div style="flex:1;">
                                <div class="d-label">Crew</div>
                                <select wire:model="newJob.crew_id"
                                    style="width:100%; height:38px; border:1px solid var(--d-border); border-radius:8px; padding:0 10px; font-size:13px; background:#fff; color:#0f172a;">
                                    <option value="">Unassigned</option>
                                    @foreach ($this->crewColorMap() as $crew)
                                        <option value="{{ $crew['id'] }}">{{ $crew['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div style="flex:1;">
                            <div class="d-label">Priority</div>
                            <select wire:model="newJob.priority"
                                style="width:100%; height:38px; border:1px solid var(--d-border); border-radius:8px; padding:0 10px; font-size:13px; background:#fff; color:#0f172a;">
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <p class="d-muted" style="font-size:12px; margin:0;">
                            A date and crew place the job straight onto that crew's route. Leave them blank to drop it into the Unassigned pile.
                        </p>
                    </div>
                    <div class="d-row" style="justify-content:flex-end; gap:8px; margin-top:6px;">
                        <button type="button" wire:click="closeNewJobModal" class="d-btn" style="height:36px; padding:0 14px; font-size:13px;">Cancel</button>
                        <button type="submit" class="d-btn" style="height:36px; padding:0 18px; font-size:13px; font-weight:600; background:var(--d-accent); color:#fff; border-color:var(--d-accent);">Create job</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Job services / notes modal --}}
        @if ($showServicesModal)
            @php $job = $this->activeJob; @endphp
            <div class="d-modal-backdrop" wire:click.self="closeServicesModal">
                <div class="d-modal" role="dialog" aria-modal="true" aria-labelledby="services-modal-title" style="max-width:520px;">
                    <div class="d-row" style="justify-content:space-between; align-items:flex-start;">
                        <div class="d-modal-title" id="services-modal-title">Job details</div>
                        <button type="button" wire:click="closeServicesModal" class="d-btn" style="height:28px; padding:0 10px; font-size:12px;">Close</button>
                    </div>

                    @if (! $job)
                        <div class="d-modal-body">Select a job on the map or list to see its services, notes, and customer details.</div>
                    @else
                        <div class="d-modal-body" style="display:flex; flex-direction:column; gap:14px;">
                            @if ($job['title'])
                                <div style="font-weight:700; font-size:15px;">{{ $job['title'] }}</div>
                            @endif

                            <div>
                                <div class="d-label">Customer</div>
                                <div class="d-field-val">{{ $job['customer_name'] }}</div>
                                @if ($job['customer_phone'])
                                    <a href="tel:{{ $job['customer_phone'] }}" class="d-link" style="font-size:12px;">{{ $job['customer_phone'] }}</a>
                                @endif
                            </div>

                            <div>
                                <div class="d-label">Property</div>
                                <div class="d-field-val">{{ $job['address'] }}{{ $job['city'] ? ', ' . $job['city'] : '' }}</div>
                            </div>

                            <div>
                                <div class="d-label">Services</div>
                                @if (! empty($job['services']))
                                    <ul style="margin:6px 0 0; padding-left:0; list-style:none; display:flex; flex-direction:column; gap:6px;">
                                        @foreach ($job['services'] as $svc)
                                            <li style="display:flex; align-items:flex-start; gap:8px;">
                                                <span style="flex-shrink:0; color:{{ $svc['completed'] ? '#16a34a' : '#9ca3af' }};">{{ $svc['completed'] ? '✓' : '○' }}</span>
                                                <span>
                                                    <span style="font-weight:600;{{ $svc['completed'] ? 'text-decoration:line-through; opacity:.7;' : '' }}">{{ $svc['name'] }}</span>
                                                    @if ($svc['description'])
                                                        <span class="d-muted" style="display:block; font-size:12px;">{{ $svc['description'] }}</span>
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="d-muted" style="font-size:13px;">No services listed for this job.</div>
                                @endif
                            </div>

                            @if ($job['time_started'])
                                <div>
                                    <div class="d-label">Foreman time</div>
                                    <div class="d-field-val" style="display:flex; flex-direction:column; gap:2px;">
                                        <span><span class="d-muted">Started:</span> {{ $job['time_started'] }}</span>
                                        @if ($job['time_running'])
                                            <span><span class="d-muted">Finished:</span> <span style="color:#d97706;">In progress</span></span>
                                        @else
                                            <span><span class="d-muted">Finished:</span> {{ $job['time_finished'] }}</span>
                                            @if ($job['time_duration'])
                                                <span><span class="d-muted">Duration:</span> {{ $job['time_duration'] }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div>
                                <div class="d-label">Notes</div>
                                <div class="d-field-val" style="white-space:pre-wrap;">{{ $job['notes'] ?: '—' }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Skip confirmation modal --}}
        @if ($this->confirmSkipStopId && $selected)
            <div class="d-modal-backdrop" wire:click.self="cancelSkip">
                <div class="d-modal" role="dialog" aria-modal="true" aria-labelledby="skip-modal-title">
                    <div class="d-modal-title" id="skip-modal-title">Skip this job for today?</div>
                    <div class="d-modal-body">
                        <strong>{{ $selected['customer_name'] }}</strong> — {{ $selected['address'] }}
                        @if ($selected['service_name'])
                            <br><span style="opacity:0.75;">{{ $selected['service_name'] }}</span>
                        @endif
                        <br><br>
                        The job will be marked <strong>Skipped</strong> and moved back to the unscheduled pile (no date) so you can re-assign it later.
                    </div>
                    <div class="d-modal-actions">
                        <button type="button" wire:click="cancelSkip" class="d-btn">Cancel</button>
                        <button type="button" wire:click="confirmSkip" class="d-btn d-btn-danger">Yes, skip job</button>
                    </div>
                </div>
            </div>
        @endif
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
                        serviceIcons: !!initial.serviceIcons,
                        lastPins: initial.pins || [],
                        lastForemen: initial.foremen || [],

                        init() {
                            if (!initial.apiKey) return;

                            this.loadMapsApi(initial.apiKey).then(() => {
                                // Only build now if the map element is present (map view).
                                this.buildMap(this.lastPins, this.lastForemen);
                            });

                            window.addEventListener('dispatch:stops-updated', (e) => {
                                const { stops = [], unroutedJobs = [], foremen = [], crewColors, serviceIcons } = e.detail || {};
                                if (crewColors) this.crewColors = crewColors;
                                if (typeof serviceIcons !== 'undefined') this.serviceIcons = !!serviceIcons;
                                this.lastPins = [...stops, ...unroutedJobs];
                                this.lastForemen = foremen;
                                this.refreshMarkers(this.lastPins, this.lastForemen);
                            });

                            // Build (or resize) the map when switching back to Map view (issue #24).
                            window.addEventListener('dispatch:view-changed', (e) => {
                                if ((e.detail || {}).mode !== 'map') return;
                                setTimeout(() => {
                                    if (!window.google || !window.google.maps) return;
                                    const el = document.getElementById('dispatch-map');
                                    if (!el) return;
                                    // Switching away from Map (e.g. Month view) destroys and
                                    // recreates #dispatch-map. Resizing the old, now-detached map
                                    // instance renders it at the default world view — so rebuild
                                    // whenever the container element has been replaced.
                                    if (!this.map || this.map.getDiv() !== el) {
                                        this.map = null;
                                        this.buildMap(this.lastPins, this.lastForemen);
                                    } else {
                                        google.maps.event.trigger(this.map, 'resize');
                                    }
                                }, 80);
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

                        foremanIcon(initials, color, opts = {}) {
                            const ring = opts.estimated ? '#9ca3af' : (opts.live ? '#16a34a' : '#f59e0b');
                            const groupOpacity = opts.estimated ? '0.55' : '1';
                            const unread = opts.unread > 0
                                ? `<circle cx="35" cy="9" r="7.5" fill="#dc2626" stroke="#ffffff" stroke-width="2"/><text x="35" y="12.5" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="9" font-weight="700" fill="#ffffff">${opts.unread > 9 ? '9+' : opts.unread}</text>`
                                : '';
                            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="44" height="50" viewBox="0 0 44 50"><g opacity="${groupOpacity}"><circle cx="22" cy="22" r="20" fill="${color}" stroke="#ffffff" stroke-width="3"/><text x="22" y="28" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="14" font-weight="700" fill="#ffffff">${initials}</text><circle cx="22" cy="45" r="5" fill="#ffffff"/><circle cx="22" cy="45" r="3" fill="${ring}"/></g>${unread}</svg>`;
                            return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
                        },

                        refreshMarkers(pins, foremen = []) {
                            if (!this.map) return;
                            this.markers.forEach((m) => m.setMap(null));
                            this.markers = [];
                            if (!pins.length && !foremen.length) return;

                            // A finite, non-zero coordinate. Guards against (0,0)/null pins
                            // that would otherwise stretch the bounds off Africa and zoom the
                            // map out to nation/world level.
                            const validCoord = (v) => typeof v === 'number' && isFinite(v) && v !== 0;

                            this.bounds = new google.maps.LatLngBounds();
                            let plotted = 0;
                            pins.forEach((p) => {
                                if (!validCoord(p.lat) || !validCoord(p.lng)) return;
                                const isJob = p.kind === 'job';
                                const opacity = p.status === 'completed' ? 0.5 : (p.status === 'skipped' ? 0.4 : 1);
                                const labelText = isJob ? '?' : String(p.sort_order ?? '');

                                // When "Service Icons" is enabled and this job's service has an
                                // uploaded icon, drop the service icon as the map pin (issue #8/#15).
                                const markerOpts = {
                                    position: { lat: p.lat, lng: p.lng },
                                    map: this.map,
                                    title: `${p.customer_name} — ${p.address}`,
                                };

                                if (this.serviceIcons && p.icon_url) {
                                    markerOpts.icon = {
                                        url: p.icon_url,
                                        scaledSize: new google.maps.Size(34, 34),
                                        anchor: new google.maps.Point(17, 34),
                                    };
                                    markerOpts.opacity = opacity;
                                } else {
                                    markerOpts.label = { text: labelText, color: '#fff', fontSize: '11px', fontWeight: '600' };
                                    markerOpts.icon = {
                                        path: 'M12 2C7.58 2 4 5.58 4 10c0 5.25 7 13 8 13s8-7.75 8-13c0-4.42-3.58-8-8-8z',
                                        fillColor: p.color,
                                        fillOpacity: opacity,
                                        strokeColor: '#fff',
                                        strokeWeight: 2,
                                        scale: isJob ? 1.4 : 1.7,
                                        anchor: new google.maps.Point(12, 23),
                                        labelOrigin: new google.maps.Point(12, 10),
                                    };
                                }

                                const marker = new google.maps.Marker(markerOpts);
                                marker.addListener('click', () => {
                                    if (isJob) this.$wire.selectJob(p.id);
                                    else this.$wire.selectStop(p.id);
                                });
                                this.markers.push(marker);
                                this.bounds.extend({ lat: p.lat, lng: p.lng });
                                plotted++;
                            });

                            foremen.forEach((f) => {
                                if (!validCoord(f.lat) || !validCoord(f.lng)) return;
                                const marker = new google.maps.Marker({
                                    position: { lat: f.lat, lng: f.lng },
                                    map: this.map,
                                    title: `${f.name} — ${f.crew_name}` + (
                                        f.has_location
                                            ? (f.is_live ? ' (live GPS)' : ` (last seen ${f.last_seen})`)
                                            : ' (estimated position)'
                                    ),
                                    icon: {
                                        url: this.foremanIcon(f.initials, f.color, {
                                            live: f.is_live,
                                            estimated: !f.has_location,
                                            unread: f.unread_chat,
                                        }),
                                        scaledSize: new google.maps.Size(44, 50),
                                        anchor: new google.maps.Point(22, 46),
                                    },
                                    zIndex: 1000,
                                });
                                marker.addListener('click', () => this.$wire.selectForeman(f.id));
                                this.markers.push(marker);
                                this.bounds.extend({ lat: f.lat, lng: f.lng });
                                plotted++;
                            });

                            if (plotted === 0) {
                                // Nothing to show for this day/crew — hold the Richmond region
                                // rather than drifting to a world view.
                                this.map.setCenter({ lat: 37.5407, lng: -77.4360 });
                                this.map.setZoom(11);
                            } else if (plotted === 1) {
                                this.map.setCenter(this.bounds.getCenter());
                                this.map.setZoom(14);
                            } else {
                                this.map.fitBounds(this.bounds, 60);
                                // Keep a sensible floor/ceiling so a lone outlier or a tight
                                // cluster still reads as the Richmond area.
                                google.maps.event.addListenerOnce(this.map, 'idle', () => {
                                    const z = this.map.getZoom();
                                    if (z > 15) this.map.setZoom(15);
                                    if (z < 9) { this.map.setCenter({ lat: 37.5407, lng: -77.4360 }); this.map.setZoom(11); }
                                });
                            }
                        },
                    };
                }
            </script>
        @endpush
    @endif
</div>
