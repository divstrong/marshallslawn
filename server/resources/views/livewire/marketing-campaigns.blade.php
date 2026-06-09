@php $brand = '#c9092f'; @endphp
<div>
    <style>
        .mc-btn { padding:8px 16px; font-size:13px; font-weight:600; border-radius:8px; cursor:pointer; border:1px solid #d1d5db; background:#fff; color:#111827; }
        .mc-btn.primary { background: {{ $brand }}; color:#fff; border-color: {{ $brand }}; }
        .mc-btn.danger { color:#b91c1c; border-color:#fecaca; }
        .mc-btn:disabled { opacity:.5; cursor:not-allowed; }
        .mc-input { width:100%; padding:9px 12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box; background:#fff; color:#111827; }
        .mc-label { display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:4px; }
        .mc-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; }
        .mc-badge { display:inline-block; padding:2px 8px; border-radius:9999px; font-size:11px; font-weight:600; }
        .mc-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:9999px; background:#f3f4f6; font-size:12px; margin:0 6px 6px 0; }
        .mc-tag { padding:5px 12px; border-radius:9999px; border:1px solid #d1d5db; font-size:12px; font-weight:600; cursor:pointer; background:#fff; }
        .mc-tag.on { background: {{ $brand }}; color:#fff; border-color: {{ $brand }}; }
        .mc-tpl { flex:1; border:2px solid #e5e7eb; border-radius:10px; padding:14px; cursor:pointer; text-align:center; background:#fff; }
        .mc-tpl.on { border-color: {{ $brand }}; box-shadow:0 0 0 3px rgba(201,9,47,.12); }
    </style>

    @if ($flash)
        <div style="background:#d1fae5; color:#065f46; padding:10px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">{{ $flash }}</div>
    @endif

    {{-- ===================== LIST ===================== --}}
    @if ($mode === 'list')
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <div>
                <h2 style="font-size:18px; font-weight:700; color:#111827; margin:0;">Email Campaigns</h2>
                <p style="font-size:13px; color:#6b7280; margin:4px 0 0;">Build HTML campaigns and send to customers or tag groups.</p>
            </div>
            <button class="mc-btn primary" wire:click="newCampaign">+ New Campaign</button>
        </div>

        <div class="mc-card" style="padding:0; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="background:#f9fafb; text-align:left; color:#6b7280; font-size:12px; text-transform:uppercase;">
                        <th style="padding:12px 16px;">Name</th>
                        <th style="padding:12px 16px;">Template</th>
                        <th style="padding:12px 16px;">Status</th>
                        <th style="padding:12px 16px;">Recipients</th>
                        <th style="padding:12px 16px;">Sent</th>
                        <th style="padding:12px 16px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->campaigns as $c)
                        <tr style="border-top:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-weight:600;">{{ $c->name }}<div style="font-weight:400; color:#9ca3af; font-size:12px;">{{ $c->subject }}</div></td>
                            <td style="padding:12px 16px;"><span class="mc-badge" style="background:#eef2ff; color:#4338ca;">{{ \App\Models\MarketingCampaign::TEMPLATES[$c->template] ?? ucfirst((string) $c->template) }}</span></td>
                            <td style="padding:12px 16px;">
                                @if ($c->status === 'sent')
                                    <span class="mc-badge" style="background:#dcfce7; color:#166534;">Sent</span>
                                @else
                                    <span class="mc-badge" style="background:#fef3c7; color:#92400e;">Draft</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px;">{{ $c->recipient_count ?: '—' }}</td>
                            <td style="padding:12px 16px; color:#6b7280;">{{ $c->sent_at?->format('M j, Y') ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:right; white-space:nowrap;">
                                <button class="mc-btn" wire:click="editCampaign({{ $c->id }})">Edit</button>
                                <button class="mc-btn danger" wire:click="deleteCampaign({{ $c->id }})" wire:confirm="Delete this campaign?">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:32px; text-align:center; color:#9ca3af;">No campaigns yet. Click “New Campaign” to start.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        {{-- ===================== EDITOR ===================== --}}
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <button class="mc-btn" wire:click="backToList">← Back</button>
            <div style="display:flex; gap:8px;">
                <button class="mc-btn" wire:click="saveDraft">Save draft</button>
                <button class="mc-btn primary" wire:click="sendCampaign" wire:confirm="Send this campaign to {{ $this->recipientCount }} recipient(s)?">Send to {{ $this->recipientCount }}</button>
            </div>
        </div>

        @error('recipients')
            <div style="background:#fee2e2; color:#991b1b; padding:10px 16px; border-radius:8px; margin-bottom:16px; font-size:13px;">{{ $message }}</div>
        @enderror

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; align-items:start;">
            {{-- Left: form --}}
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div class="mc-card" style="display:flex; flex-direction:column; gap:12px;">
                    <div>
                        <label class="mc-label">Campaign name</label>
                        <input class="mc-input" wire:model.blur="name" placeholder="Spring 2026 Kickoff">
                        @error('name') <span style="color:#b91c1c; font-size:12px;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mc-label">Email subject</label>
                        <input class="mc-input" wire:model.blur="subject" placeholder="Your lawn's spring tune-up is here 🌱">
                        @error('subject') <span style="color:#b91c1c; font-size:12px;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mc-label">Template</label>
                        <div style="display:flex; gap:10px;">
                            @foreach (\App\Models\MarketingCampaign::TEMPLATES as $key => $label)
                                <div class="mc-tpl {{ $template === $key ? 'on' : '' }}" wire:click="setTemplate('{{ $key }}')">
                                    <div style="font-weight:700; font-size:13px;">{{ $label }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mc-card" style="display:flex; flex-direction:column; gap:12px;">
                    <div style="font-size:13px; font-weight:700; color:#111827;">Content</div>
                    <div>
                        <label class="mc-label">Headline</label>
                        <input class="mc-input" wire:model.blur="headline">
                        @error('headline') <span style="color:#b91c1c; font-size:12px;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="mc-label">Body</label>
                        <textarea class="mc-input" rows="5" wire:model.blur="body"></textarea>
                        <span style="font-size:11px; color:#9ca3af;">Tokens: {first_name}, {last_name}, {company}</span>
                    </div>
                    <div>
                        <label class="mc-label">Image URL (optional)</label>
                        <input class="mc-input" wire:model.blur="imageUrl" placeholder="https://…/banner.jpg">
                        @error('imageUrl') <span style="color:#b91c1c; font-size:12px;">{{ $message }}</span> @enderror
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div>
                            <label class="mc-label">Button label</label>
                            <input class="mc-input" wire:model.blur="buttonLabel" placeholder="Book now">
                        </div>
                        <div>
                            <label class="mc-label">Button link</label>
                            <input class="mc-input" wire:model.blur="buttonUrl" placeholder="https://…">
                            @error('buttonUrl') <span style="color:#b91c1c; font-size:12px;">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mc-label">Footer note (optional)</label>
                        <input class="mc-input" wire:model.blur="footerNote">
                    </div>
                </div>

                <div class="mc-card" style="display:flex; flex-direction:column; gap:12px;">
                    <div style="font-size:13px; font-weight:700; color:#111827;">Recipients <span style="color:#6b7280; font-weight:500;">· {{ $this->recipientCount }} will receive this</span></div>

                    <div>
                        <label class="mc-label">Tag groups</label>
                        @if (count($this->availableTags) === 0)
                            <p style="font-size:12px; color:#9ca3af; margin:0;">No customer tags yet. Add tags on customer records to target groups.</p>
                        @else
                            <div style="display:flex; flex-wrap:wrap; gap:8px;">
                                @foreach ($this->availableTags as $tag)
                                    <span class="mc-tag {{ in_array($tag, $recipientTags, true) ? 'on' : '' }}" wire:click="toggleTag(@js($tag))">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="mc-label">Individual customers</label>
                        <input class="mc-input" wire:model.live.debounce.300ms="customerSearch" placeholder="Search name, company, or email…">
                        @if (trim($customerSearch) !== '' && count($this->customerResults) > 0)
                            <div style="border:1px solid #e5e7eb; border-radius:8px; margin-top:6px; max-height:180px; overflow:auto;">
                                @foreach ($this->customerResults as $cust)
                                    @php $cn = trim(($cust->first_name ?? '').' '.($cust->last_name ?? '')) ?: ($cust->company_name ?? '—'); @endphp
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 12px; border-top:1px solid #f3f4f6;">
                                        <span style="font-size:13px;">{{ $cn }} <span style="color:#9ca3af;">· {{ $cust->email }}</span></span>
                                        <button class="mc-btn" style="padding:4px 10px;" wire:click="addCustomer({{ $cust->id }})">Add</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (count($this->selectedCustomers) > 0)
                            <div style="margin-top:10px;">
                                @foreach ($this->selectedCustomers as $cust)
                                    @php $cn = trim(($cust->first_name ?? '').' '.($cust->last_name ?? '')) ?: ($cust->company_name ?? $cust->email); @endphp
                                    <span class="mc-chip">{{ $cn }} <span style="cursor:pointer; color:#b91c1c;" wire:click="removeCustomer({{ $cust->id }})">✕</span></span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right: live preview --}}
            <div class="mc-card" style="position:sticky; top:16px;">
                <div style="font-size:13px; font-weight:700; color:#111827; margin-bottom:10px;">Live preview</div>
                <iframe
                    title="Email preview"
                    style="width:100%; height:680px; border:1px solid #e5e7eb; border-radius:8px; background:#fff;"
                    srcdoc="{{ $this->previewHtml }}"
                ></iframe>
            </div>
        </div>
    @endif
</div>
