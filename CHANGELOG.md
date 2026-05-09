# Changelog

All notable changes to EstateFlow are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/).

---

## [1.0.0] — 2026-05-09

### 🎉 First stable release

EstateFlow v1.0.0 is the first production-ready release of the platform.
Built over a focused two-day sprint, this release covers the complete
property management workflow for a single Kenyan landlord.

---

### Added

#### Authentication

- Landlord registration with name, email, phone, password
- Laravel Breeze + Livewire Volt (Functional API) authentication
- Password complexity enforcement — min 8 chars, mixed case, number, symbol
- Compromised password rejection via HaveIBeenPwned
- Remember me session persistence
- Database-backed sessions
- Login rate limiting

#### Properties

- Create, edit, delete properties with active lease guard
- Property types — residential, commercial, mixed
- Portfolio overview with occupancy stats and search
- Batch unit creation — pattern mode (A1–A10) and manual mode
- Add Unit and Batch Add Units from property detail page

#### Units

- Full CRUD with bedrooms, bathrooms, floor, furnished flag
- Commercial unit types — office, retail, warehouse, studio
- Square footage pricing (rate per sqft × size auto-calculates rent)
- Service charge field for commercial units
- Status management — vacant, occupied, maintenance
- Auto status flip on lease create and terminate

#### Tenants

- Full CRUD with ID verification, emergency contact
- Tenant detail page — personal info, current lease, full payment history, lease history
- Tenant reliability score — 0–100 based on last 6 months payment history
- Search by name and phone
- Rent status badge per month

#### Leases

- Create lease — property → unit → tenant → dates → rent → deposit
- Rent and deposit auto-filled from unit base rent
- Terminate lease with confirmation
- Renew lease with new end date and optional rent adjustment
- Batch lease creation for all vacant units in a property
- Nightly auto-expiry of overdue leases via Laravel scheduler
- Status — active, expired, terminated
- Commercial lease fields — escalation rate, service charge, business name, lease type
- Filter by status, search by tenant

#### Transactions

- Record payment — type, amount, method, date, reference, notes
- Payment types — rent, deposit, penalty, refund
- Payment methods — M-Pesa, bank transfer, cash, cheque
- Auto-generated unique reference codes (TXN-XXXXXX)
- Printable receipts per transaction
- CSV export of all transactions
- Filter by type and payment method
- Summary KPIs — collected this month, total count, M-Pesa count

#### Dashboard

- KPI cards — rent collected, pending arrears, occupancy rate with progress bars
- Vacancy cost tracker — daily revenue lost from vacant units
- Tenant reliability scores on priority arrears list
- WhatsApp click-to-chat nudge buttons
- Recent payments feed
- Quick action buttons — add property, add tenant, new lease, record payment
- Personalised greeting with date
- Sandbox banner (dismissible via settings)

#### Reports

- Monthly revenue bar chart with 3/6/12 month period selector
- Payment method breakdown with doughnut chart
- Collection rate progress bar vs expected rent
- Per-property revenue comparison with bars
- Top paying tenants ranking
- Expiring leases table (next 30 days)
- Portfolio snapshot — total, occupied, vacant units
- Average days to pay metric
- Print report (browser print with clean print styles)

#### Settings

- Currency switcher — KES, USD, GBP, EUR (applies globally via Blade directive)
- Live exchange rates via ExchangeRatesAPI (cached 24 hours, fallback rates if API down)
- Date format preference — applied globally via `@appdate()` directive
- Compact dashboard mode
- Demo SMS mode — logs to file instead of charging Africa's Talking credits
- Run rent reminders manually
- Reset and reseed sandbox with one click

#### Communication

- SMS rent reminders via Africa's Talking SDK
- Automated monthly reminders — 3rd of each month at 9 AM via Laravel scheduler
- Bulk SMS to all tenants with unpaid rent
- WhatsApp click-to-chat nudge on dashboard and tenant pages

#### Automation

- Nightly lease expiry — `php artisan leases:expire-overdue`
- Monthly rent reminders — `php artisan reminders:send-rent`
- Bulk mark-all-paid per property

#### RESTful API (v1)

- Sanctum token authentication
- Endpoints — dashboard, properties, units, tenants, leases, transactions
- Lease terminate and renew endpoints
- Transaction CSV export endpoint
- M-Pesa Daraja callback endpoint stub (ready for post-deployment wiring)

#### Developer experience

- GitHub Actions — CI, DB backup every 6 hours, nightly scheduler, rollback
- Database performance indexes on all foreign keys and frequently queried columns
- Service layer — PropertyService, UnitService, TenantService, LeaseService,
  TransactionService, DashboardService, CurrencyService, SmsService
- Global Blade directives — `@money()`, `@symbol()`, `@appdate()`
- Comprehensive seed data — 3 Nairobi properties, 10 Kenyan tenants, active leases,
  intentional arrears for dashboard demo
- Universal footer on all authenticated pages
- Empty states with CTAs on all list pages

---

### Technical stack

| Layer    | Technology                     | Version |
| -------- | ------------------------------ | ------- |
| Backend  | Laravel                        | 13.x    |
| PHP      | PHP                            | 8.4     |
| Frontend | Livewire Volt (Functional API) | Latest  |
| Styling  | Tailwind CSS + Alpine.js       | v3      |
| Database | MySQL                          | 8.0     |
| Auth     | Laravel Breeze + Sanctum       | Latest  |
| SMS      | Africa's Talking SDK           | Latest  |
| Charts   | Chart.js                       | 4.4.1   |
| Build    | Vite                           | 8.x     |
| Testing  | Pest                           | 4.x     |
| CI/CD    | GitHub Actions                 | —       |

---

### Known limitations in v1.0.0

- **M-Pesa STK Push** — requires a public server URL; deferred to post-deployment
- **File uploads** — property photos, lease documents, and tenant ID scans not yet implemented
- **Email notifications** — lease expiry and payment confirmation emails not yet built
- **Multi-landlord** — single-landlord system; agency/multi-user mode is roadmap
- **Tenant portal** — tenants are records only, not system users
- **Mobile app** — REST API is ready; native mobile app is roadmap

---

## [Unreleased]

### Planned for v1.1.0

- M-Pesa Daraja STK Push integration (post-deployment)
- Flutterwave card payment gateway
- Email notifications on lease events and payment confirmation
- CSV bulk import for existing tenant data from Excel
- Property photo uploads
- Tenant portal (read-only lease and payment history)

### Planned for v1.2.0

- Two-factor authentication (SMS or TOTP)
- Multi-landlord / agency mode with role-based access
- Per-property detailed PDF report export
- Maintenance request tracking
- Docker containerisation
- Mobile app (React Native against v1 API)
