<?php

namespace App\Livewire\Mobile;

use App\Livewire\Mobile\Traits\HasMobileTranslations;
use App\Models\Role;
use Livewire\Attributes\On;
use Livewire\Component;

class MobileApp extends Component
{
    use HasMobileTranslations;

    public string $currentView = 'login';
    public string $deviceMode = 'phone'; // 'phone' or 'tablet'
    public bool $menuOpen = false;
    public ?string $userType = null; // 'customer' or 'employee'
    public ?string $employeeRole = null; // role name slug from roles table

    protected ?Role $cachedRole = null;

    protected $queryString = ['currentView', 'deviceMode'];

    public function mount()
    {
        $this->language = session('mobile_app_language', 'en');
        $this->userType = session('mobile_app_user_type');
        $this->employeeRole = session('mobile_app_employee_role');

        if (!session('mobile_app_user_id')) {
            $this->currentView = 'login';
            return;
        }

        // Set default view based on user type / role
        if ($this->currentView === 'login') {
            $this->currentView = $this->homeView;
        }
    }

    public function getIsCustomerProperty(): bool
    {
        return $this->userType === 'customer';
    }

    public function getIsEmployeeProperty(): bool
    {
        return $this->userType === 'employee';
    }

    protected function role(): ?Role
    {
        if ($this->employeeRole === null) {
            return null;
        }
        if ($this->cachedRole === null || $this->cachedRole->name !== $this->employeeRole) {
            $this->cachedRole = Role::where('name', $this->employeeRole)->first();
        }
        return $this->cachedRole;
    }

    public function getCanSeeRoutesProperty(): bool
    {
        return (bool) $this->role()?->can_see_routes;
    }

    public function getCanSeeChemicalsProperty(): bool
    {
        return (bool) $this->role()?->can_see_chemicals;
    }

    public function getCanSeeEstimatesProperty(): bool
    {
        return (bool) $this->role()?->can_see_estimates;
    }

    public function getRoleLabelProperty(): ?string
    {
        return $this->role()?->label ?? ($this->employeeRole ? ucfirst($this->employeeRole) : null);
    }

    public function getHomeViewProperty(): string
    {
        if ($this->userType === 'customer') {
            return 'customer_home';
        }
        return 'employee_jobs';
    }

    #[On('language-changed')]
    public function handleLanguageChanged(string $language): void
    {
        $this->language = $language;
        session(['mobile_app_language' => $language]);
    }

    public function setView(string $view): void
    {
        if (!session('mobile_app_user_id') && $view !== 'login') {
            $this->currentView = 'login';
            return;
        }

        // Enforce role-based access
        if ($this->userType === 'customer' && str_starts_with($view, 'employee_')) {
            $view = 'customer_home';
        }
        if ($this->userType === 'employee' && str_starts_with($view, 'customer_')) {
            $view = 'employee_jobs';
        }

        if ($view === 'employee_chemicals' && !$this->canSeeChemicals) {
            $view = 'employee_jobs';
        }

        if ($view === 'employee_routes' && !$this->canSeeRoutes) {
            $view = 'employee_jobs';
        }

        if ($view === 'employee_estimates' && !$this->canSeeEstimates) {
            $view = 'employee_jobs';
        }

        $this->currentView = $view;
    }

    public function setDeviceMode(string $mode): void
    {
        $this->deviceMode = $mode;
        $this->menuOpen = false;
    }

    public function toggleMenu(): void
    {
        $this->menuOpen = !$this->menuOpen;
    }

    public function setViewAndCloseMenu(string $view): void
    {
        $this->setView($view);
        $this->menuOpen = false;
    }

    #[On('navigate-to-view')]
    public function navigateToView(string $view): void
    {
        $this->userType = session('mobile_app_user_type');
        $this->employeeRole = session('mobile_app_employee_role');
        $this->setView($view);
        $this->menuOpen = false;
    }

    #[On('user-logged-in')]
    public function handleUserLoggedIn(): void
    {
        $this->userType = session('mobile_app_user_type');
        $this->employeeRole = session('mobile_app_employee_role');
        $this->currentView = $this->homeView;
    }

    #[On('user-logged-out')]
    public function handleUserLoggedOut(): void
    {
        $this->userType = null;
        $this->employeeRole = null;
        $this->currentView = 'login';
    }

    public function render()
    {
        return view('livewire.mobile.mobile-app', [
            't' => $this->translations,
        ])->layout('layouts.mobile');
    }
}
