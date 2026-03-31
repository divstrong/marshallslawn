<p align="center">
  <img src="https://app.marshallslawninc.com/img/logo.png" alt="Marshall's Lawn & Landscape" width="300" />
</p>

# Marshall's Lawn & Landscape

A comprehensive business management platform for Marshall's Lawn & Landscape, built to streamline operations from scheduling and dispatch to customer management and compliance tracking.

## Repository Structure

```
marshallslawn/
└── app/          # Laravel 12 application (see app/README.md for details)
```

The entire application lives inside the `app/` directory as a Laravel 12 monolith. This root repository serves as the top-level container and Git boundary.

## Tech Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Backend     | Laravel 12, PHP                     |
| Admin Panel | Filament 5                          |
| Reactive UI | Livewire 4                          |
| Styling     | Tailwind CSS 4                      |
| Build Tool  | Vite 7                              |
| Database    | SQLite (dev) / MySQL (production)   |

## Getting Started

```bash
cd app

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up the database
php artisan migrate

# Start all dev services (PHP server, queue worker, Vite)
composer run dev
```

The app will be available at `http://localhost:8000`.

## Key Modules

- **Dashboard** -- KPIs, revenue charts, and job analytics
- **Operations** -- Dispatch, scheduling, crew management, and chemical application logs
- **Customers** -- Account and property management, estimates
- **Communication** -- Messaging, notifications, and email marketing
- **Administration** -- Employees, time tracking, services, and system settings
- **Mobile Interface** -- Livewire-powered mobile UI at `/mobile`
