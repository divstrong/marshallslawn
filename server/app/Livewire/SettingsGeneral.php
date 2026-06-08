<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Component;

class SettingsGeneral extends Component
{
    public string $companyName = '';
    public string $companyEmail = '';
    public string $companyPhone = '';
    public string $companyAddress = '';
    public string $companyCity = '';
    public string $companyState = '';
    public string $companyZip = '';
    public string $taxRate = '0';
    public bool $dispatchServiceIcons = false;

    public function mount(): void
    {
        $settings = Setting::where('group', 'general')->pluck('value', 'key');

        $this->companyName = $settings['company_name'] ?? '';
        $this->companyEmail = $settings['company_email'] ?? '';
        $this->companyPhone = $settings['company_phone'] ?? '';
        $this->companyAddress = $settings['company_address'] ?? '';
        $this->companyCity = $settings['company_city'] ?? '';
        $this->companyState = $settings['company_state'] ?? '';
        $this->companyZip = $settings['company_zip'] ?? '';
        $this->taxRate = $settings['tax_rate'] ?? '0';
        $this->dispatchServiceIcons = filter_var($settings['dispatch_service_icons'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function save(): void
    {
        $fields = [
            'company_name' => $this->companyName,
            'company_email' => $this->companyEmail,
            'company_phone' => $this->companyPhone,
            'company_address' => $this->companyAddress,
            'company_city' => $this->companyCity,
            'company_state' => $this->companyState,
            'company_zip' => $this->companyZip,
            'tax_rate' => $this->taxRate,
            'dispatch_service_icons' => $this->dispatchServiceIcons ? '1' : '0',
        ];

        foreach ($fields as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'general']
            );
        }

        session()->flash('settings-success', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.settings-general');
    }
}
