# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**NinjaBring / Packiyo** — an enterprise Warehouse Management System (WMS) built on Laravel 8 with a dual-stack frontend (legacy Vue.js 2 + a modern Remix/React app). It covers inventory, order fulfillment, picking/packing, shipping carriers, billing, automations, and multi-warehouse operations.

## Environment Setup

The project runs inside Docker (Laradock). All `php artisan`, `composer`, and backend commands must be run inside the workspace container.

```bash
# Start containers
cd docker && docker-compose up -d

# Enter workspace container
docker-compose exec -u laradock workspace bash

# First-time setup (inside workspace)
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
chmod 600 storage/oauth*
```

## Common Commands

```bash
# Frontend (run inside workspace or host with Node)
npm run dev       # one-time dev build
npm run watch     # watch mode
npm run prod      # production build

# Remix frontend (in /remix directory)
npm run dev       # Vite dev server
npm run build     # production build
npx cypress run   # integration tests

# Database
php artisan migrate --seed
php artisan db:seed --class DemoSeeder
php artisan geo:download && php artisan geo:seed --chunk=100000

# Queue workers
php artisan queue:work --queue=allocation,picking
php artisan recalculate-ready-to-ship

# Testing
./vendor/bin/phpunit                           # all tests
./vendor/bin/phpunit --filter TestClassName    # single test class
./vendor/bin/phpunit tests/Unit/SomeTest.php   # single file
```

## Architecture

### Backend (Laravel 8)

- **`app/Models/`** — 200+ Eloquent models. Core entities include `Order`, `Product`, `Warehouse`, `Location`, `PickingJob`, `Shipment`, `Bill`, `Subscription`.
- **`app/Http/Controllers/`** — 130+ controllers split into web UI controllers and `Api/` subdirectory for API endpoints.
- **`app/JsonApi/`** — JSON:API spec layer (schemas + controllers). All public API routes go through here.
- **`app/Providers/`** — 70+ service providers. Each feature area (billing, shipping, inventory, automations) has its own provider.
- **`app/Components/`** — Self-contained feature modules (e.g., `OrderComponent`, `ShippingComponent`).
- **`app/Jobs/`** — Async jobs for heavy operations (allocation, picking, bulk shipping). Queue: `database` driver, queues named `allocation` and `picking`.
- **`app/Models/Automations/`** — Rule engine for warehouse automation workflows.
- **`app/Models/CacheDocuments/`** — MongoDB document models used for caching/denormalization.

### Routing

- `routes/web.php` — Blade-rendered UI routes
- `routes/api.php` — JSON API routes (Sanctum auth)
- `routes/channels.php` — Ably WebSocket broadcast channels

### Frontend

Two separate frontends coexist:

| Stack | Location | Purpose |
|-------|----------|---------|
| Vue 2 + Blade | `resources/js/`, `resources/views/` | Legacy UI (most existing features) |
| Remix + React + TypeScript | `remix/` | Modern UI for newer features |

The Remix app uses Vite, TailwindCSS, Radix UI, and React Bootstrap. Cypress is used for integration tests in Remix.

### Data Layer

- **MySQL** — primary relational store (2000+ migrations)
- **MongoDB** — document storage via `jenssegers/mongodb` driver, used for cache documents and audit-heavy entities
- **Database queue** — all async jobs stored in MySQL `jobs` table
- **Ably** — WebSocket broadcasting (print job status, real-time UI updates)

### Key Integrations

- **Shipping**: EasyPost, Pathao, Webshipper, Tribird
- **Billing**: Stripe (Laravel Cashier)
- **Mail**: Mailgun
- **Audit trail**: `owen-it/laravel-auditing`
- **PDF generation**: DomPDF
- **Geo data**: `propaganistas/laravel-geo-ip`

## Testing

- **PHPUnit** config in `phpunit.xml` — two suites: `Unit` (`tests/Unit/`) and `Feature` (`tests/Feature/`)
- **Behat** BDD specs in `features/` — 21 domain areas (billing, picking, automation, etc.)
- **Cypress** integration tests in `remix/cypress/`
- Test env variables are set directly in `phpunit.xml` (no separate `.env.testing` needed for unit/feature tests)

## CI/CD

GitLab CI defined in `.gitlab-ci.yml`. Deploy target branches: `main`, `master`, `release-1.0`.
