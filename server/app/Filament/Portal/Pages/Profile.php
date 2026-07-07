<?php

namespace App\Filament\Portal\Pages;

use App\Models\Customer;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Profile';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'My Profile';

    protected string $view = 'filament.portal.pages.profile';

    /** @var array<string, mixed> */
    public ?array $data = [];

    protected function customer(): Customer
    {
        return Filament::auth()->user();
    }

    public function mount(): void
    {
        $this->form->fill($this->customer()->only([
            'first_name', 'last_name', 'email', 'phone',
            'address', 'city', 'state', 'zip',
            'company_name', 'account_number',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Tabs::make('Profile')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Contact')
                            ->icon('heroicon-o-user')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')->required()->maxLength(255),
                                Forms\Components\TextInput::make('last_name')->required()->maxLength(255),
                                Forms\Components\TextInput::make('email')->email()->required()->maxLength(255),
                                Forms\Components\TextInput::make('phone')->tel()->maxLength(255),
                                Forms\Components\TextInput::make('address')->maxLength(255)->columnSpanFull(),
                                Forms\Components\TextInput::make('city')->maxLength(255),
                                Forms\Components\TextInput::make('state')->maxLength(255),
                                Forms\Components\TextInput::make('zip')->label('ZIP')->maxLength(255),
                            ]),
                        Tab::make('Billing')
                            ->icon('heroicon-o-credit-card')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Company / Billing name')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('account_number')
                                    ->label('Account #')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $this->customer()->update($this->form->getState());

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
    }
}
