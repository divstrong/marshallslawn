<div>
    @if(session('settings-success'))
        <div style="background: #d1fae5; color: #065f46; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
            {{ session('settings-success') }}
        </div>
    @endif

    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 20px;">
        <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 4px;">Estimate Terms &amp; Conditions</h3>
        <p style="font-size: 12px; color: #6b7280; margin-bottom: 16px;">
            This text appears in the Terms &amp; Conditions box on every estimate a customer reviews before accepting.
        </p>

        <textarea
            wire:model="estimateTerms"
            rows="10"
            placeholder="Enter the terms &amp; conditions customers agree to when accepting an estimate…"
            style="width: 100%; padding: 12px 14px; font-size: 14px; line-height: 1.5; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; font-family: inherit; resize: vertical;"
        ></textarea>

        <div style="display: flex; align-items: center; gap: 12px; margin-top: 16px;">
            <button wire:click="save" type="button" style="padding: 10px 24px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;">
                Save Terms
            </button>
            <button wire:click="resetToDefault" type="button" style="padding: 10px 16px; font-size: 13px; font-weight: 500; color: #6b7280; background: transparent; border: none; cursor: pointer;">
                Reset to default
            </button>
        </div>
    </div>
</div>
