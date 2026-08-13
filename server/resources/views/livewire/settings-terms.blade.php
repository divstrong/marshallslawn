@php
    $card = 'background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin-bottom: 20px;';
    $heading = 'font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 4px;';
    $hint = 'font-size: 12px; color: #6b7280; margin-bottom: 16px;';
    $textarea = 'width: 100%; padding: 12px 14px; font-size: 14px; line-height: 1.5; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; font-family: inherit; resize: vertical;';
    $saveBtn = 'padding: 10px 24px; font-size: 14px; font-weight: 600; color: #fff; background: #c9092f; border: none; border-radius: 8px; cursor: pointer;';
    $resetBtn = 'padding: 10px 16px; font-size: 13px; font-weight: 500; color: #6b7280; background: transparent; border: none; cursor: pointer;';
@endphp

<div>
    @if(session('settings-success'))
        <div style="background: #d1fae5; color: #065f46; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
            {{ session('settings-success') }}
        </div>
    @endif

    <div style="{{ $card }}">
        <h3 style="{{ $heading }}">Estimate Terms &amp; Conditions</h3>
        <p style="{{ $hint }}">
            This text appears in the Terms &amp; Conditions box on every estimate a customer reviews,
            and is what they confirm agreement to before signing.
        </p>

        <textarea
            wire:model="estimateTerms"
            rows="10"
            placeholder="Enter the terms &amp; conditions customers agree to when accepting an estimate…"
            style="{{ $textarea }}"
        ></textarea>

        <div style="display: flex; align-items: center; gap: 12px; margin-top: 16px;">
            <button wire:click="save" type="button" style="{{ $saveBtn }}">Save Estimate Terms</button>
            <button wire:click="resetToDefault" type="button" style="{{ $resetBtn }}">Reset to default</button>
        </div>
    </div>

    <div style="{{ $card }}">
        <h3 style="{{ $heading }}">Invoice Terms</h3>
        <p style="{{ $hint }}">
            Shown at the bottom of every invoice a customer views, directly above the payment form —
            payment windows, late charges, where to mail a check, and who to call about a charge.
        </p>

        <textarea
            wire:model="invoiceTerms"
            rows="10"
            placeholder="Enter the payment terms shown on invoices…"
            style="{{ $textarea }}"
        ></textarea>

        <div style="display: flex; align-items: center; gap: 12px; margin-top: 16px;">
            <button wire:click="saveInvoiceTerms" type="button" style="{{ $saveBtn }}">Save Invoice Terms</button>
            <button wire:click="resetInvoiceToDefault" type="button" style="{{ $resetBtn }}">Reset to default</button>
        </div>
    </div>
</div>
