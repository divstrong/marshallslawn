<div class="stm">
    <style>
        .stm { --stm-accent:#c9092f; display:flex; flex-direction:column; gap:16px; }
        .stm-note { font-size:13px; padding:12px 14px; border-radius:10px; border:1px solid #e5e7eb; background:#f9fafb; color:#374151; }
        .stm-note.warn { background:#fffbeb; border-color:#fde68a; color:#92400e; }
        .stm-card { border:1px solid #e5e7eb; border-radius:12px; padding:16px; background:#fff; }
        .stm-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:10px; }
        .stm-title { font-weight:600; font-size:14px; color:#111827; }
        .stm-key { font-size:11px; color:#9ca3af; font-family:ui-monospace, monospace; }
        .stm-textarea { width:100%; min-height:70px; padding:10px 12px; font-size:13px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box; resize:vertical; color:#111827; background:#fff; }
        .stm-actions { display:flex; align-items:center; justify-content:space-between; margin-top:8px; }
        .stm-save { font-size:13px; font-weight:600; padding:7px 14px; border-radius:8px; border:1px solid var(--stm-accent); background:var(--stm-accent); color:#fff; cursor:pointer; }
        .stm-toggle { display:inline-flex; align-items:center; gap:8px; font-size:13px; cursor:pointer; user-select:none; }
        .stm-switch { width:38px; height:22px; border-radius:9999px; background:#d1d5db; position:relative; transition:background 150ms; flex-shrink:0; }
        .stm-switch.on { background:var(--stm-accent); }
        .stm-knob { position:absolute; top:2px; left:2px; width:18px; height:18px; border-radius:9999px; background:#fff; transition:left 150ms; }
        .stm-switch.on .stm-knob { left:18px; }
        .stm-tokens { font-size:11px; color:#6b7280; margin-top:8px; line-height:1.7; }
        .stm-tokens code { background:#f3f4f6; padding:1px 5px; border-radius:4px; font-family:ui-monospace, monospace; }
        .stm-preview { margin-top:10px; padding:10px 12px; border-radius:8px; background:#f9fafb; border:1px solid #e5e7eb; }
        .stm-preview-label { font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#9ca3af; margin-bottom:4px; }
        .stm-preview-body { font-size:13px; color:#111827; white-space:pre-wrap; word-break:break-word; }
        .stm-count { font-size:11px; color:#6b7280; margin-top:6px; }
        .stm-count.warn { color:#b45309; font-weight:600; }
        .stm-test { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:12px; padding-top:12px; border-top:1px dashed #e5e7eb; }
        .stm-test-input { flex:1; min-width:170px; padding:7px 10px; font-size:13px; border:1px solid #d1d5db; border-radius:8px; color:#111827; background:#fff; }
        .stm-test-btn { font-size:13px; font-weight:600; padding:7px 14px; border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#374151; cursor:pointer; }
        .stm-test-btn:disabled { opacity:.5; cursor:not-allowed; }
        .stm-test-result { font-size:12px; width:100%; }
        .stm-test-result.ok { color:#065f46; }
        .stm-test-result.bad { color:#b91c1c; }
        .dark .stm-preview { background:#111827; border-color:#374151; }
        .dark .stm-preview-body { color:#f9fafb; }
        .dark .stm-test-input { background:#111827; color:#f9fafb; border-color:#374151; }
        .dark .stm-test-btn { background:#1f2937; color:#e5e7eb; border-color:#374151; }
        .dark .stm-test { border-top-color:#374151; }
        .dark .stm-note { background:#111827; border-color:#374151; color:#d1d5db; }
        .dark .stm-card { background:#1f2937; border-color:#374151; }
        .dark .stm-title { color:#f9fafb; }
        .dark .stm-textarea { background:#111827; color:#f9fafb; border-color:#374151; }
        .dark .stm-tokens code { background:#374151; color:#e5e7eb; }
    </style>

    @if (! $this->channelEnabled)
        <div class="stm-note warn">
            The SMS channel is currently <strong>off</strong>. Set <code>TWILIO_NOTIFICATIONS_ENABLED=true</code> (and
            your Twilio credentials) in the server environment to start sending. Toggles below still control which
            messages are active once the channel is on.
        </div>
    @else
        <div class="stm-note">
            The SMS channel is <strong>on</strong>. Only customers who have confirmed opt-in receive messages. Use the
            toggles to control which events send.
        </div>
    @endif

    @unless ($this->twilioConfigured)
        <div class="stm-note warn">
            Twilio credentials are missing on this server, so test sends are unavailable. Set
            <code>TWILIO_ACCOUNT_SID</code>, <code>TWILIO_AUTH_TOKEN</code> and a sending number
            (<code>TWILIO_MESSAGING_SERVICE_SID</code> or <code>TWILIO_FROM_NUMBER</code>).
        </div>
    @else
        <div class="stm-note">
            <strong>Test sends go out even when the channel above is off</strong>, so you can verify the Twilio setup
            before arming customer messages. A test uses sample data and goes only to the number you type — never to a
            customer. Every send is recorded under Administration → Message Log.
        </div>
    @endunless

    @foreach ($this->templates as $template)
        <div class="stm-card" wire:key="sms-tpl-{{ $template->id }}">
            <div class="stm-head">
                <div>
                    <div class="stm-title">{{ $template->name }}</div>
                    <div class="stm-key">{{ $template->key }}</div>
                </div>
                <div class="stm-toggle" wire:click="toggle({{ $template->id }})" role="switch" aria-checked="{{ ($rows[$template->id]['is_active'] ?? false) ? 'true' : 'false' }}">
                    <span>{{ ($rows[$template->id]['is_active'] ?? false) ? 'Active' : 'Off' }}</span>
                    <span class="stm-switch {{ ($rows[$template->id]['is_active'] ?? false) ? 'on' : '' }}"><span class="stm-knob"></span></span>
                </div>
            </div>

            <textarea class="stm-textarea" wire:model="rows.{{ $template->id }}.body"></textarea>
            @error("rows.{$template->id}.body") <div style="color:#dc2626; font-size:12px; margin-top:4px;">{{ $message }}</div> @enderror

            <div class="stm-actions">
                <span style="font-size:12px; color:#9ca3af;" wire:loading.remove wire:target="save({{ $template->id }})">Edit the message, then save.</span>
                <span style="font-size:12px; color:#9ca3af;" wire:loading wire:target="save({{ $template->id }})">Saving…</span>
                <button type="button" class="stm-save" wire:click="save({{ $template->id }})">Save message</button>
            </div>

            @php($preview = $this->previewFor($template->id))
            <div class="stm-preview">
                <div class="stm-preview-label">Preview with sample data</div>
                <div class="stm-preview-body">{{ $preview['body'] }}</div>
                <div class="stm-count {{ $preview['segments'] > 1 ? 'warn' : '' }}">
                    {{ $preview['chars'] }} characters ·
                    {{ $preview['segments'] }} {{ Str::plural('segment', $preview['segments']) }} ·
                    {{ $preview['encoding'] }}
                    @if ($preview['encoding'] === 'UCS-2')
                        — a non-standard character (curly quote or emoji) is cutting the segment size to 70.
                    @elseif ($preview['segments'] > 1)
                        — billed as {{ $preview['segments'] }} messages.
                    @endif
                </div>
            </div>

            <div class="stm-test">
                <input
                    type="tel"
                    class="stm-test-input"
                    placeholder="Send a test to… (804) 555-1234"
                    wire:model="testNumbers.{{ $template->id }}"
                    @disabled(! $this->twilioConfigured)
                >
                <button
                    type="button"
                    class="stm-test-btn"
                    wire:click="sendTest({{ $template->id }})"
                    wire:loading.attr="disabled"
                    wire:target="sendTest({{ $template->id }})"
                    @disabled(! $this->twilioConfigured)
                >
                    <span wire:loading.remove wire:target="sendTest({{ $template->id }})">Send test</span>
                    <span wire:loading wire:target="sendTest({{ $template->id }})">Sending…</span>
                </button>

                @if (isset($testResults[$template->id]))
                    <div class="stm-test-result {{ $testResults[$template->id]['ok'] ? 'ok' : 'bad' }}">
                        {{ $testResults[$template->id]['message'] }}
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    <div class="stm-tokens">
        <strong>Placeholders you can use:</strong><br>
        @foreach (\App\Models\SmsTemplate::PLACEHOLDERS as $token => $desc)
            <code>{{ $token }}</code> — {{ $desc }}<br>
        @endforeach
    </div>

    <div x-data="{ show:false }" x-on:saved.window="show=true; setTimeout(()=>show=false, 1800)" x-show="show" x-cloak
        style="position:fixed; bottom:20px; right:20px; background:#065f46; color:#fff; padding:10px 16px; border-radius:8px; font-size:13px; box-shadow:0 6px 20px rgba(0,0,0,0.2);">
        Saved
    </div>
</div>
