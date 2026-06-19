<div class="settings-general">
    <style>
        /* Fill the full tab width, then split into two columns on wider screens. */
        .settings-general .sg-grid { display: grid; grid-template-columns: 1fr; gap: 20px; align-items: start; }
        @media (min-width: 1024px) {
            .settings-general .sg-grid { grid-template-columns: 2fr 1fr; }
        }
        .settings-general .sg-card {
            background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
            padding: 24px; margin-bottom: 20px;
        }
        .settings-general .sg-card:last-child { margin-bottom: 0; }
        .settings-general .sg-h3 { font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px; }
        .settings-general .sg-label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .settings-general .sg-input {
            width: 100%; padding: 9px 12px; font-size: 14px;
            border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;
        }
    </style>

    @if(session('settings-success'))
        <div style="background: #d1fae5; color: #065f46; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
            {{ session('settings-success') }}
        </div>
    @endif

    <div class="sg-grid">
        {{-- Left column --}}
        <div>
            <div class="sg-card">
                <h3 class="sg-h3">Company Information</h3>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <label class="sg-label">Company Name</label>
                        <input wire:model="companyName" type="text" placeholder="Marshall's Lawn & Landscape" class="sg-input" />
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label class="sg-label">Email</label>
                            <input wire:model="companyEmail" type="email" class="sg-input" />
                        </div>
                        <div>
                            <label class="sg-label">Phone</label>
                            <input wire:model="companyPhone" type="text" class="sg-input" />
                        </div>
                    </div>
                    <div>
                        <label class="sg-label">Address</label>
                        <input wire:model="companyAddress" type="text" class="sg-input" />
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 80px 100px; gap: 12px;">
                        <div>
                            <label class="sg-label">City</label>
                            <input wire:model="companyCity" type="text" class="sg-input" />
                        </div>
                        <div>
                            <label class="sg-label">State</label>
                            <input wire:model="companyState" type="text" maxlength="2" class="sg-input" />
                        </div>
                        <div>
                            <label class="sg-label">Zip</label>
                            <input wire:model="companyZip" type="text" class="sg-input" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right column --}}
        <div>
            <div class="sg-card">
                <h3 class="sg-h3">Billing</h3>
                <div>
                    <label class="sg-label">Default Tax Rate (%)</label>
                    <input wire:model="taxRate" type="number" step="0.01" min="0" style="width: 120px; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                </div>
            </div>

            <div class="sg-card">
                <h3 class="sg-h3">Dispatch</h3>
                <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                    <input wire:model="dispatchServiceIcons" type="checkbox" style="margin-top: 3px; width: 16px; height: 16px;" />
                    <span>
                        <span style="display: block; font-size: 13px; font-weight: 600; color: #111827;">Service Icons</span>
                        <span style="display: block; font-size: 12px; color: #6b7280; margin-top: 2px;">Show each service's icon on Dispatch job cards and map pins (where an icon has been uploaded).</span>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <button wire:click="save" type="button" style="margin-top: 20px; padding: 10px 24px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">
        Save Settings
    </button>
</div>
