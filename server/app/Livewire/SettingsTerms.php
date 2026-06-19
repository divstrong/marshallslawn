<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;

class SettingsTerms extends Component
{
    /** The setting key the estimate terms text is stored under. */
    public const SETTING_KEY = 'estimate_terms';

    /**
     * Default terms shown on estimates until the office edits them. Kept here as the
     * single source of truth so the public estimate view can fall back to it.
     */
    public const DEFAULT_ESTIMATE_TERMS = "By accepting this estimate, you authorize Marshall's Lawn & Landscape to perform the selected services at the prices listed above. Payment is due upon completion unless other arrangements have been made. Pricing is valid through the date shown above. Marshall's Lawn & Landscape reserves the right to adjust scheduling based on weather conditions. Cancellations must be made at least 24 hours in advance. The customer is responsible for ensuring access to the property on scheduled service dates.";

    public string $estimateTerms = '';

    public function mount(): void
    {
        $this->estimateTerms = Setting::get(self::SETTING_KEY, self::DEFAULT_ESTIMATE_TERMS);
    }

    public function save(): void
    {
        Setting::updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => $this->estimateTerms, 'group' => 'terms']
        );

        session()->flash('settings-success', 'Terms & Conditions saved.');
    }

    public function resetToDefault(): void
    {
        $this->estimateTerms = self::DEFAULT_ESTIMATE_TERMS;
    }

    public function render()
    {
        return view('livewire.settings-terms');
    }
}
