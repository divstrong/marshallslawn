# Marshall's Lawn & Landscape

A business management platform built for Marshall's Lawn & Landscape using the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire) with Filament v5 as the admin panel framework.

## Overview

This application provides Marshall's Lawn & Landscape with a centralized system to manage day-to-day operations, customer relationships, crew scheduling, job tracking, and business analytics — replacing manual processes and spreadsheets with a modern web-based tool.

## Tech Stack

- **Laravel 12** — PHP framework
- **Livewire 4** — Reactive UI components
- **Filament 5** — Admin panel, forms, tables, and widgets
- **Tailwind CSS** — Utility-first styling
- **MySQL** — Database

## Modules

### Dashboard
Executive summary with stats cards (customers, jobs, estimates), a monthly revenue chart, and job type mix breakdown.

### Operations
- **Dispatch** — Interactive map and communication tools for responding to customer issues and directing field crews
- **Scheduling** — Route mapping for all crews and jobs each day
- **Jobs** — Track all approved jobs with crew assignments, status, and priority
- **Crews** — Manage crew rosters and foreman assignments
- **Chemical Logs** — Record chemical applications by spray technicians (chemical name, EPA registration, target pest, area treated, weather conditions)

### Customers
- **Customers** — Full customer account management with contact info, status, and lead source tracking
- **Properties** — Property records linked to customers with lot size, lawn size, and address details
- **Estimates** — Create, send, and track estimates with line items and payment acceptance

### Communication
- **Messages** — Internal and customer-facing job communication via email, SMS, or in-app
- **Notifications** — Automated reminders and alerts sent to customers and staff at configurable intervals
- **Marketing** — Create and send HTML email campaigns to customer lists

### Administration
- **Users** — Admin user accounts with roles and permissions
- **Settings** — Organization contact information and general configuration
- **Services** — Manage service offerings with categories, default pricing, and billing units
- **Employees** — Employee records with contact info, hire dates, pay rates, and emergency contacts
- **Time Logs** — Employee clock in/out tracking with break time and approval workflow

## Getting Started

### Requirements
- PHP 8.2+
- Composer
- Node.js & npm
- MySQL

### Installation

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed default data
php artisan db:seed

# Build frontend assets
npm run build

# Start the dev server
php artisan serve
```

### Default Login
After seeding, log in with:
- **Email:** test@example.com
- **Password:** password
