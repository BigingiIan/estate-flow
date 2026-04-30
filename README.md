# EstateFlow

> Property management, architecturally refined.

A modern, single-landlord property management system built for the Kenyan market. EstateFlow replaces Excel spreadsheets with a clean, fast web application that manages properties, units, tenants, leases, and transactions — with automated rent reminders via SMS and a real-time analytics dashboard.

---

## Tech stack

| Layer    | Technology                             |
| -------- | -------------------------------------- |
| Backend  | Laravel 13 (PHP 8.4)                   |
| Frontend | Livewire Volt (Functional API) + Blade |
| Styling  | Tailwind CSS + Alpine.js               |
| Database | MySQL 8.0                              |
| SMS      | Africa's Talking SDK                   |
| Build    | Vite                                   |
| Testing  | Pest                                   |
| CI/CD    | GitHub Actions                         |

---

## Features

### Core

- **Multi-property management** — manage unlimited properties and units under one account
- **Tenant records** — full tenant profiles with ID verification and emergency contacts
- **Lease lifecycle** — create, renew, and terminate leases with automatic unit status updates
- **Transaction recording** — log payments by type (rent, deposit, penalty, refund) and method (M-Pesa, bank transfer, cash, cheque)
- **Printable receipts** — auto-generated reference codes and printable payment receipts

### Dashboard

- Real-time KPI cards — rent collected, pending arrears, occupancy rate
- **Vacancy cost tracker** — shows daily revenue lost from vacant units
- **Tenant reliability score** — 0–100 score based on last 6 months of payment history
- Priority arrears list with WhatsApp nudge
- Recent payments feed
- Quick action buttons

### Automation

- Nightly lease expiry via Laravel scheduler
- Monthly SMS rent reminders via Africa's Talking (3rd of every month, 9 AM)
- Bulk mark-all-paid per property
- Batch unit creation (pattern mode and manual mode)
- Batch lease creation per property

### Commercial support

- Office, retail, warehouse, and studio unit types
- Square footage pricing (rate per sqft × size)
- Floor tracking, service charge, furnished flag
- Escalation rate on leases for annual rent increases
- Business name field for commercial tenants

### Reports

- Monthly revenue bar chart (3/6/12 month periods)
- Payment method breakdown with doughnut chart
- Collection rate progress bar
- Per-property revenue comparison
- Top paying tenants ranking
- Expiring leases (next 30 days)
- Printable report view

### Settings

- Currency switcher (KES, USD, GBP, EUR) — applies globally
- Date format preference
- Compact dashboard mode
- Demo SMS mode — logs to file instead of sending live
- Sandbox reset — drops and reseeds demo data

---

## Getting started

### Requirements

- PHP 8.4+
- MySQL 8.0+
- Node.js 20+
- Composer 2+

### Installation

```bash
# Clone the repository
git clone https://github.com/your-username/estate-flow.git
cd estate-flow

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=estate_flow
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations and seed demo data
php artisan migrate --seed

# Build assets
npm run build

# Start the development server
php artisan serve
```

Visit `http://localhost:8000` and log in with:

| Field    | Value                  |
| -------- | ---------------------- |
| Email    | `ian@estateflow.co.ke` |
| Password | `password`             |

---

## Africa's Talking SMS setup

1. Sign up at [account.africastalking.com](https://account.africastalking.com)
2. Get your sandbox API key
3. Add to `.env`:

```env
AT_USERNAME=sandbox
AT_API_KEY=your_api_key_here
```

4. In Settings, enable **Demo SMS mode** to log messages to file during development without consuming credits.
5. For production, set `AT_USERNAME` to your live username and disable demo mode.

---

## Running the scheduler locally

Laravel's scheduler needs to run continuously to trigger automated reminders and lease expiry.

```bash
php artisan schedule:work
```

Or manually trigger individual commands:

```bash
# Expire overdue leases
php artisan leases:expire-overdue

# Send rent reminders
php artisan reminders:send-rent
```

---

## Git branch strategy

main ← stable, production-ready snapshots only
develop ← integration branch, all features merged here first
feature/_ ← one branch per feature
hotfix/_ ← urgent fixes branched off main
backup/db-snapshots ← automated DB dumps

---

## Environment variables reference

```env
APP_NAME=EstateFlow
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=estate_flow
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database

QUEUE_CONNECTION=sync

AT_USERNAME=sandbox
AT_API_KEY=your_africastalking_api_key

REGISTRATION_OPEN=true
```

---

## Running tests

```bash
php artisan test
```

Or with coverage:

```bash
php artisan test --coverage
```

---

## Seed data

The seeder creates a realistic Kenyan demo dataset:

- **3 properties** — Sunshine Apartments (Westlands), Azure Heights (Kilimani), Riverside Plaza (Riverside Drive)
- **15 units** across the three properties
- **10 tenants** with Kenyan names and +254 phone numbers
- **10 active leases** with varying start dates
- **Transactions** — most tenants have paid, John Ndegwa, Sarah Otieno, and Moses Kamau are intentionally in arrears to populate the dashboard priority list

---

## Roadmap

- [ ] M-Pesa Daraja STK Push (requires public server URL)
- [ ] Flutterwave payment gateway
- [ ] CSV import for bulk tenant migration from Excel
- [ ] Per-property detailed report PDF export
- [ ] Tenant portal (read-only view of lease and payment history)
- [ ] Two-factor authentication
- [ ] Docker containerisation
- [ ] Multi-landlord / agency mode

---

## License

Private project — not licensed for redistribution.

---

Built by Ian Bigingi · Nairobi, Kenya
