<?php

namespace App\Livewire\Mobile;

use App\Models\Customer;
use App\Models\Employee;
use Livewire\Component;

class MobileLogin extends Component
{
    public string $loginType = 'employee'; // 'employee' or 'customer'
    public string $searchQuery = '';
    public string $password = '';
    public ?int $selectedUserId = null;
    public bool $showDropdown = false;

    public function updatedLoginType()
    {
        $this->reset('searchQuery', 'password', 'selectedUserId', 'showDropdown');
    }

    public function updatedSearchQuery()
    {
        if (!$this->selectedUserId) {
            $this->showDropdown = strlen($this->searchQuery) >= 2;
        } else {
            $this->selectedUserId = null;
            $this->showDropdown = strlen($this->searchQuery) >= 2;
        }
    }

    public function getSearchResultsProperty()
    {
        if (strlen($this->searchQuery) < 2 || $this->selectedUserId) {
            return collect();
        }

        if ($this->loginType === 'employee') {
            return Employee::query()
                ->where(function ($query) {
                    $query->where('email', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('first_name', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('last_name', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('name', 'like', '%' . $this->searchQuery . '%');
                })
                ->where('status', 'active')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(10)
                ->get();
        }

        return Customer::query()
            ->where(function ($query) {
                $query->where('email', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('first_name', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('last_name', 'like', '%' . $this->searchQuery . '%')
                    ->orWhere('company_name', 'like', '%' . $this->searchQuery . '%');
            })
            ->where('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(10)
            ->get();
    }

    public function selectUser(int $userId)
    {
        if ($this->loginType === 'employee') {
            $user = Employee::find($userId);
            if ($user) {
                $this->selectedUserId = $userId;
                $this->showDropdown = false;
                $this->searchQuery = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name;
            }
        } else {
            $user = Customer::find($userId);
            if ($user) {
                $this->selectedUserId = $userId;
                $this->showDropdown = false;
                $this->searchQuery = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            }
        }
    }

    public function login()
    {
        if (!$this->selectedUserId) {
            session()->flash('error', 'Please select a user from the list.');
            return;
        }

        if ($this->password !== 'password') {
            session()->flash('error', 'Invalid password. Use "password" for testing.');
            return;
        }

        if ($this->loginType === 'employee') {
            $employee = Employee::find($this->selectedUserId);
            if (!$employee) {
                session()->flash('error', 'Employee not found.');
                return;
            }

            // Determine employee role based on division or default
            $role = 'field';
            $division = strtolower($employee->division ?? '');
            if (str_contains($division, 'spray') || str_contains($division, 'chemical') || str_contains($division, 'tech')) {
                $role = 'spray_tech';
            } elseif (str_contains($division, 'super') || str_contains($division, 'manage') || str_contains($division, 'admin')) {
                $role = 'supervisor';
            }

            // Check if employee is a foreman (has crews) - treat as supervisor
            if ($employee->crews()->exists()) {
                $role = 'supervisor';
            }

            session([
                'mobile_app_user_id' => $employee->id,
                'mobile_app_user_type' => 'employee',
                'mobile_app_user_name' => trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) ?: $employee->name,
                'mobile_app_employee_role' => $role,
            ]);

            $this->dispatch('user-logged-in');
        } else {
            $customer = Customer::find($this->selectedUserId);
            if (!$customer) {
                session()->flash('error', 'Customer not found.');
                return;
            }

            session([
                'mobile_app_user_id' => $customer->id,
                'mobile_app_user_type' => 'customer',
                'mobile_app_user_name' => trim($customer->first_name . ' ' . $customer->last_name),
                'mobile_app_employee_role' => null,
            ]);

            $this->dispatch('user-logged-in');
        }
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
        return view('livewire.mobile.mobile-login', [
            'searchResults' => $this->searchResults,
        ]);
    }
}
