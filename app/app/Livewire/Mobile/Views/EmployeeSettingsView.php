<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Employee;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class EmployeeSettingsView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public bool $notificationsEnabled = true;
    public bool $gpsEnabled = true;

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
    }

    public function getEmployeeProperty(): ?Employee
    {
        return Employee::find(session('mobile_app_user_id'));
    }

    public function setLanguage(string $lang)
    {
        $this->language = $lang;
        session(['mobile_app_language' => $lang]);
        $this->dispatch('language-changed', language: $lang);
    }

    public function logout()
    {
        session()->forget([
            'mobile_app_user_id',
            'mobile_app_user_type',
            'mobile_app_user_name',
            'mobile_app_employee_role',
            'mobile_app_language',
        ]);
        $this->dispatch('user-logged-out');
    }

    public function render()
    {
        return view('livewire.mobile.views.employee-settings', [
            't' => $this->translations,
        ]);
    }
}
