{{--
    Self-contained styling for the customer overview partials. The admin panel runs
    on Filament's precompiled CSS (no custom Vite theme), so arbitrary Tailwind
    utilities aren't guaranteed to exist here — these rules carry their own light
    and dark treatments instead. @once keeps it to a single copy per page.
--}}
@once
    <style>
        .cov-tiles {
            display: grid; gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        }
        .cov-tile {
            border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px 18px;
            background: #fff; display: flex; flex-direction: column; gap: 3px;
            border-left: 3px solid #d1d5db;
        }
        .cov-tile.is-accent { border-left-color: #e00a35; }
        .cov-tile.is-good { border-left-color: #16a34a; }
        .cov-tile.is-warn { border-left-color: #d97706; }
        .cov-tile-label {
            font-size: 11px; font-weight: 600; letter-spacing: .04em;
            text-transform: uppercase; color: #6b7280;
        }
        .cov-tile-value { font-size: 22px; font-weight: 700; color: #0f172a; line-height: 1.2; }
        .cov-tile-sub { font-size: 12px; color: #6b7280; }

        .cov-list { display: flex; flex-direction: column; gap: 10px; }
        .cov-row {
            display: flex; align-items: center; gap: 12px;
            border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px;
            background: #fff;
        }
        .cov-row-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
        .cov-thumb {
            flex-shrink: 0; width: 68px; height: 52px; border-radius: 8px;
            object-fit: cover; border: 1px solid #e5e7eb; background: #f3f4f6;
        }
        {{-- The placeholder is line art on a light plate; cover-cropping it would
             clip the badge, so it sits inside its box instead. --}}
        .cov-thumb.is-empty { object-fit: contain; padding: 2px; }
        {{-- Titles wrap rather than truncate: a full street address matters more here
             than keeping every row exactly one line tall. --}}
        .cov-row-title { font-size: 14px; font-weight: 600; color: #0f172a; line-height: 1.35; }
        .cov-row-sub { font-size: 12.5px; color: #6b7280; line-height: 1.4; }
        .cov-row-amount { font-size: 14px; font-weight: 700; color: #0f172a; white-space: nowrap; }

        {{-- Stacked month/day chip that fronts each dated row. --}}
        .cov-date {
            flex-shrink: 0; width: 46px; text-align: center; border-radius: 8px;
            padding: 6px 0; background: #f3f4f6; border: 1px solid #e5e7eb;
        }
        .cov-date-m {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .04em; color: #e00a35;
        }
        .cov-date-d { font-size: 16px; font-weight: 700; color: #0f172a; line-height: 1.15; }

        .cov-pill {
            display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;
            font-size: 11px; font-weight: 600; border-radius: 999px;
            padding: 2px 8px; border: 1px solid #e5e7eb; color: #374151; background: #f9fafb;
        }
        .cov-pill.is-primary { color: #a80828; border-color: #fbc8d1; background: #fef1f3; }
        .cov-pill.is-warn { color: #92400e; border-color: #fde68a; background: #fffbeb; }
        .cov-empty { font-size: 13px; color: #6b7280; }

        .cov-split {
            display: flex; justify-content: space-between; align-items: baseline;
            gap: 12px; padding: 9px 0; border-bottom: 1px dashed #e5e7eb;
        }
        .cov-split:last-child { border-bottom: 0; }
        .cov-split-label { font-size: 12.5px; color: #6b7280; }
        .cov-split-value { font-size: 15px; font-weight: 700; color: #0f172a; }
        .cov-split-value.is-warn { color: #b45309; }

        .dark .cov-tile,
        .dark .cov-row { background: rgba(255,255,255,.02); border-color: rgba(255,255,255,.1); }
        .dark .cov-tile { border-left-color: rgba(255,255,255,.2); }
        .dark .cov-tile.is-accent { border-left-color: #f4657f; }
        .dark .cov-tile.is-good { border-left-color: #4ade80; }
        .dark .cov-tile.is-warn { border-left-color: #fbbf24; }
        .dark .cov-tile-value,
        .dark .cov-row-title,
        .dark .cov-row-amount,
        .dark .cov-date-d,
        .dark .cov-split-value { color: #f3f4f6; }
        .dark .cov-tile-label,
        .dark .cov-tile-sub,
        .dark .cov-row-sub,
        .dark .cov-split-label,
        .dark .cov-empty { color: #9ca3af; }
        .dark .cov-date { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.1); }
        .dark .cov-thumb { border-color: rgba(255,255,255,.12); background: rgba(255,255,255,.04); }
        .dark .cov-date-m { color: #f4657f; }
        .dark .cov-pill { background: rgba(255,255,255,.04); border-color: rgba(255,255,255,.12); color: #d1d5db; }
        .dark .cov-pill.is-primary { color: #f8a0af; border-color: rgba(224,10,53,.4); background: rgba(224,10,53,.12); }
        .dark .cov-pill.is-warn { color: #fcd34d; border-color: rgba(217,119,6,.4); background: rgba(217,119,6,.12); }
        .dark .cov-split { border-bottom-color: rgba(255,255,255,.1); }
        .dark .cov-split-value.is-warn { color: #fbbf24; }
    </style>
@endonce
