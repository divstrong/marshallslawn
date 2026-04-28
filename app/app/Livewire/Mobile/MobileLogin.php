<?php

namespace App\Livewire\Mobile;

use App\Models\Customer;
use App\Models\Employee;
use Livewire\Component;

class MobileLogin extends Component
{
    public string $searchQuery = '';
    public string $password = '';
    public ?int $selectedUserId = null;
    public ?string $selectedUserType = null; // 'employee' or 'customer'
    public bool $showDropdown = false;

    public function updatedSearchQuery()
    {
        if ($this->selectedUserId) {
            $this->selectedUserId = null;
            $this->selectedUserType = null;
        }
        $this->showDropdown = strlen($this->searchQuery) >= 2;
    }

    public function getSearchResultsProperty()
    {
        if (strlen($this->searchQuery) < 2 || $this->selectedUserId) {
            return collect();
        }

        $employees = Employee::query()
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
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'type' => 'employee',
                'first_name' => $e->first_name,
                'last_name' => $e->last_name,
                'name' => $e->name,
                'email' => $e->email,
                'company_name' => null,
            ]);

        $customers = Customer::query()
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
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'type' => 'customer',
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'name' => null,
                'email' => $c->email,
                'company_name' => $c->company_name,
            ]);

        return $employees->concat($customers)->values();
    }

    public function selectUser(int $userId, string $userType)
    {
        if ($userType === 'employee') {
            $user = Employee::find($userId);
            if ($user) {
                $this->selectedUserId = $userId;
                $this->selectedUserType = 'employee';
                $this->showDropdown = false;
                $this->searchQuery = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->name;
            }
        } else {
            $user = Customer::find($userId);
            if ($user) {
                $this->selectedUserId = $userId;
                $this->selectedUserType = 'customer';
                $this->showDropdown = false;
                $this->searchQuery = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            }
        }
    }

    public function login()
    {
        if (!$this->selectedUserId || !$this->selectedUserType) {
            session()->flash('error', 'Please select a user from the list.');
            return;
        }

        if ($this->password !== 'password') {
            session()->flash('error', 'Invalid password. Use "password" for testing.');
            return;
        }

        if ($this->selectedUserType === 'employee') {
            $employee = Employee::find($this->selectedUserId);
            if (!$employee) {
                session()->flash('error', 'Employee not found.');
                return;
            }

            // Determine employee role based on role/division or default
            $role = 'field';
            $division = strtolower($employee->division ?? '');
            $empRole = strtolower($employee->role ?? '');
            if ($empRole === 'estimator' || str_contains($division, 'estim') || str_contains($division, 'sales')) {
                $role = 'estimator';
            } elseif (str_contains($division, 'spray') || str_contains($division, 'chemical') || str_contains($division, 'tech')) {
                $role = 'spray_tech';
            } elseif (str_contains($division, 'super') || str_contains($division, 'manage') || str_contains($division, 'admin')) {
                $role = 'supervisor';
            }

            // Check if employee is a foreman (has crews) - treat as supervisor
            // (overrides estimator/field, but not spray_tech since foreman+spray_tech is unusual)
            if ($role !== 'estimator' && $employee->crews()->exists()) {
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
