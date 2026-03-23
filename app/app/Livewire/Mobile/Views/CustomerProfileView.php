<?php

namespace App\Livewire\Mobile\Views;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Customer;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class CustomerProfileView extends Component
{
    use HasMobileTranslations;

    #[Reactive]
    public string $deviceMode = 'phone';

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $city = '';
    public string $state = '';
    public string $zip = '';

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
        $this->loadCustomer();
    }

    public function loadCustomer()
    {
        $customer = Customer::find(session('mobile_app_user_id'));
        if ($customer) {
            $this->first_name = $customer->first_name ?? '';
            $this->last_name = $customer->last_name ?? '';
            $this->email = $customer->email ?? '';
            $this->phone = $customer->phone ?? '';
            $this->address = $customer->address ?? '';
            $this->city = $customer->city ?? '';
            $this->state = $customer->state ?? '';
            $this->zip = $customer->zip ?? '';
        }
    }

    public function getCustomerProperty(): ?Customer
    {
        return Customer::with('properties')->find(session('mobile_app_user_id'));
    }

    public function updateProfile()
    {
        $customer = Customer::find(session('mobile_app_user_id'));
        if (!$customer) return;

        $customer->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
        ]);

        session(['mobile_app_user_name' => trim($this->first_name . ' ' . $this->last_name)]);
        session()->flash('success', 'Profile updated successfully!');
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
        return view('livewire.mobile.views.customer-profile', [
            't' => $this->translations,
        ]);
    }
}
