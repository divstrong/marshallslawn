<p align="center">
  <img src="https://app.marshallslawninc.com/img/logo.png" alt="Marshall's Lawn & Landscape" width="300" />
</p>

# Marshall's Lawn & Landscape -- App

This is the core Laravel 12 application powering the Marshall's Lawn & Landscape management platform.

## Architecture

The app is a Laravel monolith with two primary interfaces:

1. **Admin Panel** (Filament 5) -- Full-featured back-office at `/admin` for managing all aspects of the business
2. **Mobile App** (Livewire 4) -- Field crew interface at `/mobile` for on-the-go job management

### Directory Overview

```
app/
├── app/
│   ├── Filament/         # Admin panel (4 pages, 13 resources)
│   ├── Livewire/         # Mobile app components (~13 components)
│   ├── Models/           # 16 Eloquent models
│   └── Http/Controllers/ # Base controller
├── config/               # Laravel configuration
├── database/
│   ├── migrations/       # 16 migration files
│   ├── factories/        # Model factories
│   └── seeders/          # Database seeders
├── resources/
│   ├── css/              # Tailwind CSS entry point
│   ├── js/               # JavaScript entry point
│   └── views/            # Blade templates (Filament, Livewire, layouts)
├── routes/               # web.php, console.php
├── public/               # Compiled assets, images, fonts
├── storage/              # Sessions, cache, logs
└── tests/                # PHPUnit tests
```

### Data Models

The platform manages: Customers, Employees, Jobs, Crews, Services, Estimates, Properties, Chemical Logs, Time Logs, Messages, Notifications, and more.

## Modules

### Dashboard
Executive summary with stats cards (customers, jobs, estimates), a monthly revenue chart, and job type mix breakdown.

### Operations
- **Dispatch** -- Interactive map and communication tools for responding to customer issues and directing field crews
- **Scheduling** -- Route mapping for all crews and jobs each day
- **Jobs** -- Track all approved jobs with crew assignments, status, and priority
- **Crews** -- Manage crew rosters and foreman assignments
- **Chemical Logs** -- Record chemical applications by spray technicians (chemical name, EPA registration, target pest, area treated, weather conditions)

### Customers
- **Customers** -- Full customer account management with contact info, status, and lead source tracking
- **Properties** -- Property records linked to customers with lot size, lawn size, and address details
- **Estimates** -- Create, send, and track estimates with line items and payment acceptance

### Communication
- **Messages** -- Internal and customer-facing job communication via email, SMS, or in-app
- **Notifications** -- Automated reminders and alerts sent to customers and staff at configurable intervals
- **Marketing** -- Create and send HTML email campaigns to customer lists

### Administration
- **Users** -- Admin user accounts with roles and permissions
- **Settings** -- Organization contact information and general configuration
- **Services** -- Manage service offerings with categories, default pricing, and billing units
- **Employees** -- Employee records with contact info, hire dates, pay rates, and emergency contacts
- **Time Logs** -- Employee clock in/out tracking with break time and approval workflow

## Development

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm
- MySQL

### Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Run

```bash
# Start all services concurrently (recommended)
composer run dev
```

This launches the PHP dev server, queue worker, log watcher, and Vite dev server together.

Individual commands:

```bash
php artisan serve        # Laravel dev server
npm run dev              # Vite dev server (hot reload)
npm run build            # Production asset build
```

### Seed Default Data

```bash
php artisan db:seed
```

### Default Login
After seeding, log in with:
- **Email:** test@example.com
- **Password:** password

### Testing

```bash
composer run test
```

## Environment

Key `.env` settings:

| Variable          | Default    | Notes                       |
|-------------------|------------|-----------------------------|
| `DB_CONNECTION`   | `sqlite`   | Switch to `mysql` for prod  |
| `SESSION_DRIVER`  | `database` |                             |
| `QUEUE_CONNECTION`| `database` |                             |
| `CACHE_STORE`     | `database` |                             |

## Deployment

1. Run `npm run build` to compile production assets into `public/build/`
2. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
3. Run `php artisan migrate --force` for database updates
4. Configure your web server to point to the `public/` directory
