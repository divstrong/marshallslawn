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
