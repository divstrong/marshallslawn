<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;

class SettingsTerms extends Component
{
    /** The setting key the estimate terms text is stored under. */
    public const SETTING_KEY = 'estimate_terms';

    /** The setting key the invoice terms text is stored under. */
    public const INVOICE_SETTING_KEY = 'invoice_terms';

    /**
     * Default terms shown on estimates until the office edits them. Kept here as the
     * single source of truth so the public estimate view can fall back to it.
     */
    public const DEFAULT_ESTIMATE_TERMS = "By accepting this estimate, you authorize Marshall's Lawn & Landscape to perform the selected services at the prices listed above. Payment is due upon completion unless other arrangements have been made. Pricing is valid through the date shown above. Marshall's Lawn & Landscape reserves the right to adjust scheduling based on weather conditions. Cancellations must be made at least 24 hours in advance. The customer is responsible for ensuring access to the property on scheduled service dates.";

    /**
     * Default terms shown on invoices, above the payment form, until the office edits
     * them. The public invoice view falls back to this the same way estimates do.
     */
    public const DEFAULT_INVOICE_TERMS = "Payment is due within 30 days of the invoice date unless other arrangements have been made in writing. Accounts more than 30 days past due may be subject to a late charge and suspension of service. We accept card and ACH payments through the secure form below; checks may be mailed to the address shown above. Please include your invoice number with any payment. Questions about a charge? Contact our office before the due date and we'll be glad to review it with you.";

    public string $estimateTerms = '';

    public string $invoiceTerms = '';

    public function mount(): void
    {
        $this->estimateTerms = Setting::get(self::SETTING_KEY, self::DEFAULT_ESTIMATE_TERMS);
        $this->invoiceTerms = Setting::get(self::INVOICE_SETTING_KEY, self::DEFAULT_INVOICE_TERMS);
    }

    public function save(): void
    {
        Setting::updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => $this->estimateTerms, 'group' => 'terms']
        );

        session()->flash('settings-success', 'Estimate Terms & Conditions saved.');
    }

    public function saveInvoiceTerms(): void
    {
        Setting::updateOrCreate(
            ['key' => self::INVOICE_SETTING_KEY],
            ['value' => $this->invoiceTerms, 'group' => 'terms']
        );

        session()->flash('settings-success', 'Invoice Terms saved.');
    }

    public function resetToDefault(): void
    {
        $this->estimateTerms = self::DEFAULT_ESTIMATE_TERMS;
    }

    public function resetInvoiceToDefault(): void
    {
        $this->invoiceTerms = self::DEFAULT_INVOICE_TERMS;
    }

    /** The estimate terms in force, for the public estimate view. */
    public static function estimateTerms(): string
    {
        return Setting::get(self::SETTING_KEY, self::DEFAULT_ESTIMATE_TERMS);
    }

    /** The invoice terms in force, for the public invoice view. */
    public static function invoiceTerms(): string
    {
        return Setting::get(self::INVOICE_SETTING_KEY, self::DEFAULT_INVOICE_TERMS);
    }

    public function render()
    {
        return view('livewire.settings-terms');
    }
}
