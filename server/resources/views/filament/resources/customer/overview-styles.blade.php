{{--
    Styling for the customer overview partials. The admin panel runs on Filament's
    precompiled CSS (no custom Vite theme), so arbitrary Tailwind utilities aren't
    guaranteed to exist here — these rules carry their own light and dark treatments.
    @once keeps it to a single copy per page.

    House rules, so the page reads as one system:
      · money and counts use tabular figures so columns line up
      · one accent (the brand red) earns attention; everything else is neutral
      · micro-labels are 11px/600/uppercase; values are 15–28px/700
--}}
@once
    <style>
        .cov-scope {
            --cov-ink: #0b1220;
            --cov-ink-soft: #475569;
            --cov-ink-mute: #6b7280;
            --cov-line: #e5e7eb;
            --cov-line-soft: #f1f5f9;
            --cov-surface: #fff;
            --cov-raised: #f8fafc;
            --cov-accent: #e00a35;
            --cov-accent-soft: #fef1f3;
            --cov-good: #16a34a;
            --cov-warn: #b45309;
        }

        .cov-num { font-variant-numeric: tabular-nums; font-feature-settings: 'tnum' 1; }

        .cov-micro {
            font-size: 11px; font-weight: 600; letter-spacing: .06em;
            text-transform: uppercase; color: var(--cov-ink-mute);
        }

        /* ---------- Identity header ---------- */
        .cov-hero {
            display: flex; flex-wrap: wrap; gap: 20px; align-items: center;
            justify-content: space-between;
            border: 1px solid var(--cov-line); border-radius: 14px;
            background:
                linear-gradient(180deg, rgba(224,10,53,.045) 0%, rgba(224,10,53,0) 78px),
                var(--cov-surface);
            padding: 20px 22px;
        }
        .cov-hero-id { display: flex; align-items: center; gap: 16px; min-width: 0; }
        .cov-monogram {
            flex-shrink: 0; width: 56px; height: 56px; border-radius: 14px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 19px; font-weight: 700; letter-spacing: .02em; color: #fff;
            background: linear-gradient(140deg, #e00a35 0%, #8b0721 100%);
            box-shadow: 0 6px 16px -6px rgba(224,10,53,.55);
        }
        .cov-hero-name {
            font-size: 22px; font-weight: 700; line-height: 1.15; color: var(--cov-ink);
            letter-spacing: -.01em;
        }
        .cov-hero-company { font-size: 13px; color: var(--cov-ink-soft); margin-top: 2px; }
        .cov-hero-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }

        /* Quick facts, right of the identity block. */
        .cov-facts { display: flex; flex-wrap: wrap; gap: 10px 26px; }
        .cov-fact { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .cov-fact-value {
            font-size: 13px; font-weight: 600; color: var(--cov-ink);
            display: inline-flex; align-items: center; gap: 6px;
        }
        .cov-fact-value a { color: inherit; text-decoration: none; }
        .cov-fact-value a:hover { text-decoration: underline; }

        /* ---------- KPI band ---------- */
        .cov-tiles {
            display: grid; gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(214px, 1fr));
        }
        .cov-tile {
            position: relative; overflow: hidden;
            border: 1px solid var(--cov-line); border-radius: 14px;
            padding: 16px 18px; background: var(--cov-surface);
            display: flex; flex-direction: column; gap: 4px;
        }
        /* A hairline of colour along the top rather than a heavy left border. */
        .cov-tile::before {
            content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
            background: var(--cov-line);
        }
        .cov-tile.is-accent::before { background: linear-gradient(90deg, #e00a35, #f4657f); }
        .cov-tile.is-good::before { background: linear-gradient(90deg, #16a34a, #4ade80); }
        .cov-tile.is-warn::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
        .cov-tile-value {
            font-size: 28px; font-weight: 700; line-height: 1.1;
            color: var(--cov-ink); letter-spacing: -.02em;
        }
        .cov-tile-sub { font-size: 12px; color: var(--cov-ink-mute); line-height: 1.45; }

        /* ---------- Rows shared by the list cards ---------- */
        .cov-list { display: flex; flex-direction: column; gap: 8px; }
        .cov-row {
            display: flex; align-items: center; gap: 12px;
            border: 1px solid var(--cov-line); border-radius: 12px;
            padding: 11px 13px; background: var(--cov-surface);
            transition: border-color 120ms ease, background 120ms ease;
        }
        .cov-row:hover { border-color: #cbd5e1; background: var(--cov-raised); }
        .cov-row-main { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
        .cov-row-title { font-size: 14px; font-weight: 600; color: var(--cov-ink); line-height: 1.35; }
        .cov-row-sub { font-size: 12.5px; color: var(--cov-ink-mute); line-height: 1.45; }
        .cov-row-amount {
            font-size: 14px; font-weight: 700; color: var(--cov-ink); white-space: nowrap;
        }

        .cov-thumb {
            flex-shrink: 0; width: 64px; height: 50px; border-radius: 10px;
            object-fit: cover; border: 1px solid var(--cov-line); background: var(--cov-raised);
        }
        /* The placeholder is line art on a light plate; cropping would clip its badge. */
        .cov-thumb.is-empty { object-fit: contain; padding: 2px; }

        /* Stacked month/day chip fronting each dated row. */
        .cov-date {
            flex-shrink: 0; width: 46px; text-align: center; border-radius: 10px;
            padding: 5px 0; background: var(--cov-raised); border: 1px solid var(--cov-line);
        }
        .cov-date-m {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: var(--cov-accent);
        }
        .cov-date-d { font-size: 16px; font-weight: 700; color: var(--cov-ink); line-height: 1.15; }

        .cov-pill {
            display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;
            font-size: 11px; font-weight: 600; border-radius: 999px;
            padding: 3px 9px; border: 1px solid var(--cov-line);
            color: #334155; background: var(--cov-raised);
        }
        .cov-pill.is-primary { color: #a80828; border-color: #fbc8d1; background: var(--cov-accent-soft); }
        .cov-pill.is-good { color: #15803d; border-color: #bbf7d0; background: #f0fdf4; }
        .cov-pill.is-warn { color: #92400e; border-color: #fde68a; background: #fffbeb; }
        .cov-empty { font-size: 13px; color: var(--cov-ink-mute); }

        /* ---------- Billing ---------- */
        .cov-balance {
            border: 1px solid var(--cov-line); border-radius: 12px;
            padding: 14px 16px; background: var(--cov-raised);
        }
        .cov-balance-value {
            font-size: 24px; font-weight: 700; letter-spacing: -.02em;
            color: var(--cov-ink); line-height: 1.15;
        }
        .cov-balance-value.is-warn { color: var(--cov-warn); }
        /* Paid-versus-owed at a glance; the bar is the fastest read on this card. */
        .cov-meter {
            height: 6px; border-radius: 999px; background: #e2e8f0;
            overflow: hidden; margin-top: 12px;
        }
        .cov-meter-fill { height: 100%; border-radius: 999px; background: var(--cov-good); }
        .cov-split {
            display: flex; justify-content: space-between; align-items: baseline;
            gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--cov-line-soft);
        }
        .cov-split:last-child { border-bottom: 0; }
        .cov-split-label { font-size: 12.5px; color: var(--cov-ink-mute); }
        .cov-split-value { font-size: 14px; font-weight: 700; color: var(--cov-ink); }
        .cov-split-value.is-warn { color: var(--cov-warn); }

        /* ---------- Dark ---------- */
        .dark .cov-scope {
            --cov-ink: #f1f5f9;
            --cov-ink-soft: #cbd5e1;
            --cov-ink-mute: #94a3b8;
            --cov-line: rgba(255,255,255,.12);
            --cov-line-soft: rgba(255,255,255,.08);
            --cov-surface: rgba(255,255,255,.03);
            --cov-raised: rgba(255,255,255,.05);
            --cov-accent: #f4657f;
            --cov-accent-soft: rgba(224,10,53,.14);
            --cov-good: #4ade80;
            --cov-warn: #fbbf24;
        }
        .dark .cov-hero {
            background:
                linear-gradient(180deg, rgba(224,10,53,.14) 0%, rgba(224,10,53,0) 78px),
                var(--cov-surface);
        }
        .dark .cov-row:hover { border-color: rgba(255,255,255,.22); }
        .dark .cov-pill { color: #e2e8f0; }
        .dark .cov-pill.is-primary { color: #f8a0af; border-color: rgba(224,10,53,.42); }
        .dark .cov-pill.is-good { color: #86efac; border-color: rgba(22,163,74,.42); background: rgba(22,163,74,.14); }
        .dark .cov-pill.is-warn { color: #fcd34d; border-color: rgba(217,119,6,.42); background: rgba(217,119,6,.14); }
        .dark .cov-meter { background: rgba(255,255,255,.12); }
    </style>
@endonce
