# EstateFlow

> Property management, architecturally refined.

EstateFlow is a landlord-focused property management system built for the Kenyan market. It replaces spreadsheets with a clean Laravel app for managing properties, units, tenants, leases, transactions, reminders, and reporting, with automation for testing, backups, and scheduled maintenance.

---

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13 (PHP 8.4) |
| Frontend | Livewire Volt + Blade |
| Styling | Tailwind CSS + Alpine.js |
| Database | MySQL 8.0 locally, SQLite in CI tests |
| SMS | Africa's Talking SDK |
| Build | Vite |
| Testing | Pest |
| Automation | GitHub Actions |

---

## Product scope

- Landlord-only system design
- Multi-property portfolio management
- Tenant records with emergency contacts and ID tracking
- Lease lifecycle management
- Payment recording with receipts and references
- Dashboard KPIs, arrears, occupancy, and reports
- Commercial unit support
- Scheduled reminders and lease expiry automation

There are no separate tenant, agent, or staff application roles in the current system.

---

## Core features

### Operations

- Multi-property management under landlord accounts
- Unit tracking for residential, commercial, and mixed portfolios
- Lease creation, renewal, termination, and expiry handling
- Transaction recording for rent, deposit, penalty, and refund flows
- Printable receipts with generated reference codes

### Dashboard and reporting

- Comprehensive financial and operational reporting dashboard page
- KPI cards for rent collected, arrears, and occupancy
- Vacancy cost tracking
- Priority arrears and payment reliability signals
- Revenue and collection reports
- Property performance comparisons
- Expiring lease visibility

### Commercial support

- Office, retail, warehouse, studio, and apartment unit types
- Square-foot pricing support
- Service charge, furnished flag, and floor tracking
- Lease escalation rate and business-name fields

### Automation

- Nightly lease expiry processing
- Monthly SMS reminder scheduling
- CI test automation
- Automated database snapshot workflow to `backup/db-snapshots`

---

## Requirements

- PHP 8.4+
- Composer 2+
- Node.js 20+
- MySQL 8.0+
- MySQL client tools if you want to run local DB backup automation:
  `mysqldump` or `mariadb-dump`
- **[Optional but recommended] Laragon** for local development on Windows

---

## Installation

### Using Laragon (Recommended for Windows)

1. Clone the repository into your Laragon `www` directory:
   ```bash
   cd C:\laragon\www
   git clone https://github.com/your-username/estate-flow.git
   cd estate-flow
   ```

2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Set up the environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your database in `.env` (Laragon defaults):
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=estate_flow
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Initialize the database and build assets:
   ```bash
   php artisan migrate:fresh --seed
   npm run build
   ```

6. Access the application in your browser at `http://estate-flow.test`. 
   > Note: You do not need to run `php artisan serve` when using Laragon, as it automatically provisions the virtual host. Keep `npm run dev` running in a separate terminal during active development.

### Standard Installation

```bash
git clone https://github.com/your-username/estate-flow.git
cd estate-flow

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=estate_flow
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Then initialize the app:

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Default demo login:

| Field | Value |
| --- | --- |
| Email | `ian@estateflow.co.ke` |
| Password | `password` |

Seed data may include additional landlord accounts for ownership and scoping realism, but all system users remain landlord-role users.

---

## Africa's Talking SMS setup

Add these to `.env`:

```env
AT_USERNAME=sandbox
AT_API_KEY=your_api_key_here
```

Use Demo SMS mode in Settings during development to avoid sending live SMS.

---

## Local scheduler usage

Run the scheduler continuously:

```bash
php artisan schedule:work
```

Or trigger automation manually:

```bash
php artisan leases:expire-overdue
php artisan reminders:send-rent
php artisan schedule:run -vvv
```

You can inspect the registered schedule with:

```bash
php artisan schedule:list
```

---

## GitHub Actions automation

The repository now includes these workflows:

- `CI`
  builds frontend assets and runs the full test suite with SQLite
- `Backup Database Snapshots`
  creates a MySQL dump and publishes it to the `backup/db-snapshots` branch
- `Run Scheduler Automation`
  runs Laravel scheduler automation on a schedule or by manual dispatch

### Required GitHub secrets

Add these repository secrets for backup and scheduler jobs:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=estate_flow
DB_USERNAME=your_username
DB_PASSWORD=your_password
AT_USERNAME=sandbox
AT_API_KEY=your_api_key_here
```

### Local automation simulation

Safe local simulations:

```bash
php artisan test
php artisan schedule:list
php artisan schedule:run -vvv
powershell -ExecutionPolicy Bypass -File scripts/Invoke-DbBackup.ps1
```

Notes:

- local DB snapshot simulation requires `mysqldump` or `mariadb-dump` on `PATH`
- local snapshots are written to `database/db-backups/`
- automated restore has been intentionally removed for safety

---

## Branch strategy

- `main`: stable snapshots
- `develop`: integration branch
- `feature/*`: feature work
- `hotfix/*`: urgent fixes
- `backup/db-snapshots`: automated database dumps

---

## Environment reference

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
```

---

## Testing

```bash
php artisan test
```

Coverage:

```bash
php artisan test --coverage
```

---

## Seed data

The seeding strategy is now intended for realistic stress testing rather than a tiny static demo.

It includes:

- a primary landlord demo login
- large and variable residential, commercial, and mixed portfolios
- active, expired, terminated, and open-ended leases
- occupied, vacant, and maintenance units
- rent, deposit, penalty, and refund transactions
- arrears, partial payments, and refund scenarios

Refresh the seeded dataset with:

```bash
php artisan migrate:fresh --seed
```

---

## Roadmap

- M-Pesa Daraja STK Push
- Flutterwave payment gateway
- CSV import for tenant migration
- richer PDF/export flows
- tenant-facing read-only portal
- two-factor authentication
- Docker support

---

## License

Private project. Not licensed for redistribution.

---

Built by Ian Bigingi, Nairobi, Kenya.
