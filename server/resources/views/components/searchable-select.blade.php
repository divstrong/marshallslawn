@props([
    // [value => label]
    'options' => [],
    // Currently selected value, or null.
    'value' => null,
    'placeholder' => 'Select…',
    // JS statement run on pick, with `value` (string) in scope.
    // e.g. "$wire.set('newJob.crew_id', value)"
    'onSelect' => '',
    // Below this many options a search box is more noise than help.
    'searchThreshold' => 6,
    'disabled' => false,
    'emptyLabel' => 'No options',
    'triggerStyle' => '',
])

@php
    $options = collect($options)->mapWithKeys(fn ($label, $key) => [(string) $key => (string) $label])->all();
    $selectedLabel = $value !== null && array_key_exists((string) $value, $options)
        ? $options[(string) $value]
        : null;
    $showSearch = count($options) >= (int) $searchThreshold;
@endphp

{{--
    Searchable replacement for a native <select>. A native select can't be typed
    into, which makes long service/crew lists slow to use inside a modal.
    Filtering is client-side: the options are already rendered, so narrowing them
    shouldn't cost a server round-trip.
--}}
<div
    x-data="{
        open: false,
        q: '',
        labels: @js(array_values(array_map(fn ($l) => mb_strtolower($l), $options))),
        get hasMatch() {
            return this.q === '' || this.labels.some(l => l.includes(this.q.toLowerCase()));
        },
        pick(value) { this.open = false; this.q = ''; {!! $onSelect !!} },
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    style="position:relative;"
>
    <button
        type="button"
        @click="open = ! open; if (open) $nextTick(() => $refs.q?.focus())"
        @disabled($disabled || empty($options))
        style="{{ $triggerStyle }}display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;text-align:left;cursor:{{ $disabled || empty($options) ? 'not-allowed' : 'pointer' }};opacity:{{ $disabled || empty($options) ? '0.6' : '1' }};"
    >
        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;{{ $selectedLabel ? '' : 'color:var(--d-muted, #6b7280);' }}">
            {{ empty($options) ? $emptyLabel : ($selectedLabel ?? $placeholder) }}
        </span>
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        style="position:absolute;z-index:60;top:calc(100% + 4px);left:0;right:0;min-width:200px;background:var(--d-card-bg, #fff);border:1px solid var(--d-border, #e5e7eb);border-radius:10px;box-shadow:0 10px 25px rgba(0,0,0,0.15);padding:6px;"
    >
        @if ($showSearch)
            <input
                x-ref="q"
                x-model="q"
                type="text"
                placeholder="Search…"
                style="width:100%;margin-bottom:6px;padding:6px 8px;font-size:12px;border:1px solid var(--d-border, #e5e7eb);border-radius:6px;background:var(--d-card-bg, #fff);color:inherit;box-sizing:border-box;"
            >
        @endif

        <div style="max-height:240px;overflow-y:auto;">
            @foreach ($options as $optValue => $optLabel)
                <button
                    type="button"
                    x-show="q === '' || @js(mb_strtolower($optLabel)).includes(q.toLowerCase())"
                    @click="pick(@js((string) $optValue))"
                    style="display:block;width:100%;text-align:left;padding:6px 8px;font-size:13px;border:none;border-radius:6px;background:{{ (string) $value === (string) $optValue ? 'var(--d-border, #f3f4f6)' : 'transparent' }};color:inherit;cursor:pointer;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                >{{ $optLabel }}</button>
            @endforeach

            <div
                x-show="! hasMatch"
                x-cloak
                style="padding:8px;font-size:12px;color:var(--d-muted, #6b7280);"
            >No matches.</div>
        </div>
    </div>
</div>
