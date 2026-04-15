<div>
    @if(session('settings-success'))
        <div style="background: #d1fae5; color: #065f46; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
            {{ session('settings-success') }}
        </div>
    @endif

    <div style="max-width: 640px;">
        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 20px;">
            <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Company Information</h3>

            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Company Name</label>
                    <input wire:model="companyName" type="text" placeholder="Marshall's Lawn & Landscape" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Email</label>
                        <input wire:model="companyEmail" type="email" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Phone</label>
                        <input wire:model="companyPhone" type="text" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Address</label>
                    <input wire:model="companyAddress" type="text" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                </div>
                <div style="display: grid; grid-template-columns: 1fr 80px 100px; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">City</label>
                        <input wire:model="companyCity" type="text" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">State</label>
                        <input wire:model="companyState" type="text" maxlength="2" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Zip</label>
                        <input wire:model="companyZip" type="text" style="width: 100%; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
                    </div>
                </div>
            </div>
        </div>

        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 20px;">
            <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Billing</h3>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px;">Default Tax Rate (%)</label>
                <input wire:model="taxRate" type="number" step="0.01" min="0" style="width: 120px; padding: 9px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;" />
            </div>
        </div>

        <button wire:click="save" type="button" style="padding: 10px 24px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">
            Save Settings
        </button>
    </div>
</div>
