<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<h1 align="center">eLINGAP</h1>

<p align="center">
  Senior citizen records, benefit management, and SMS notifications<br>
  for the Office of the Senior Citizens Affairs of Santa Maria, Bulacan.
</p>

## Overview

eLINGAP brings registration, OSCA ID issuance, benefit applications, payouts, and reporting into one office workspace. Staff sign in with assigned employee IDs, and role-based permissions control access to records and administrative actions. Audit logs support accountability across office workflows.

The application is under active development. SMS delivery requires a configured gateway and an active queue worker.

## Contents

- [Features](#features)
- [Technology](#technology)
- [Local setup](#local-setup)
- [Background services](#background-services)
- [SMS configuration](#sms-configuration)
- [Development checks](#development-checks)
- [Deployment and documentation](#deployment-and-documentation)
- [Project structure](#project-structure)
- [Contributing](#contributing)
- [License](#license)

## Features

| Area | Capabilities |
| --- | --- |
| Senior citizen records | Registration, profile updates, supporting documents, transfers, and death declarations or corrections |
| OSCA IDs | ID applications, review, issuance, and printable cards |
| Benefits and programs | Program definitions, beneficiary enrollment, application review, and benefit scheduling |
| Payouts | Release schedules, claimant recording, payout tracking, and reversals |
| Reports | Filtered senior citizen and beneficiary lists, demographic and staff reports, and Excel, CSV, and PDF exports |
| Messaging | SMS templates, individual and broadcast messages, queue processing, delivery logs, and retries |
| Administration | Staff accounts, employee ID access, administrator-assisted password recovery, reference data, and office settings |
| Public information | Landing page with office information and program listings |

## Technology

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.4, Laravel 13 |
| Interface | Blade, Livewire 4, Alpine.js 3 |
| Styles and assets | Tailwind CSS 4, Vite 8 |
| Database | PostgreSQL |
| Documents | Dompdf, PhpSpreadsheet, QR code generation |
| Tests and formatting | PHPUnit 12, Node.js test runner, Laravel Pint |

## Local setup

### Prerequisites

- PHP 8.4.27 or a newer patched PHP 8.4 release, matching the project's documented baseline.
- Composer and the PHP extensions required by `composer.lock`, including PostgreSQL PDO, GD, DOM, and ZIP.
- PostgreSQL with a dedicated application database.
- Node.js `^20.19.0` or `>=22.12.0`, with npm.

Use the same PHP version for the CLI, web server, and queue workers. Run `composer check-platform-reqs` after installing dependencies to verify the required runtime and extensions.

### 1. Install dependencies

Clone the repository, open its root directory, and install the locked dependencies:

```bash
git clone <repository-url>
cd eLINGAP
composer install
npm ci
```

Replace `<repository-url>` with this project's repository URL.

### 2. Configure the environment

Copy the example environment file:

```bash
cp .env.example .env
```

On Windows PowerShell, use:

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

In `.env`, configure the application URL and the `DB_*` settings for your local PostgreSQL database. Review the session, cache, and queue settings for your environment. Keep environment files and credentials out of version control.

### 3. Initialize the database

For a fresh installation, run:

```bash
php artisan migrate
php artisan db:seed
php artisan app:create-admin
```

The default seeders initialize roles, office location settings, barangays, benefit programs, and program SMS templates. They do not create demo accounts. The administrator command prompts for account details interactively; use the assigned official employee ID.

For an existing installation switching from usernames, follow the [employee ID migration guide](docs/employee-id-access-migration.md) before running migrations.

### 4. Start the application

```bash
npm run build
php artisan serve
```

Open [localhost:8000](http://localhost:8000) and sign in with the administrator's employee ID and password. Administrators can provision additional staff accounts through the application.

For frontend development, run `npm run dev` in a separate terminal. The repository also provides `composer dev` for its local development workflow.

## Background services

### Queue worker

SMS delivery and benefit recipient preparation use the `database-atomic` queue connection. Start a worker in a separate terminal:

```bash
php artisan queue:work database-atomic --queue=default --timeout=60
```

Messages and queued jobs are committed together on the same database connection. Production workers should run under a process supervisor and restart after releases.

### Scheduler

For local scheduled tasks, run:

```bash
php artisan schedule:work
```

Scheduled tasks expire password recovery requests, prune operational logs, and optionally record capacity reports. Production uses the scheduler cron entry in the [Hostinger deployment profile](deploy/hostinger/deployment-profile.md).

## SMS configuration

Configure the gateway through these environment variables, read by `config/services.php`:

| Variable | Purpose |
| --- | --- |
| `SMS_GATEWAY_URL` | Gateway base URL |
| `SMS_GATEWAY_TOKEN` | Gateway authentication token |
| `SMS_GATEWAY_TIMEOUT` | Request timeout in seconds; defaults to 10 |

The driver sends authenticated POST requests to `/messages` with `to` and `message` fields and an `Idempotency-Key` header. The gateway must return a JSON response containing the provider message ID in `id`.

Confirm gateway compatibility before enabling delivery. A recorded `sent` status reflects the gateway request result; provider delivery confirmation is separate.

## Development checks

Database-backed tests use PostgreSQL and the separate `db_elingap_testing` database configured in `phpunit.xml`. Create that database and grant the local database user access before running tests. Test runs can reset tables, so use a dedicated test database.

```bash
composer test
node --test tests/Js/*.test.js
vendor/bin/pint --test
npm run build
```

To run a focused feature test:

```bash
php artisan test --filter=SeniorCitizenManagementTest
```

On Windows PowerShell, use `vendor\bin\pint.bat --test` for the formatter. If PowerShell blocks npm scripts, use `npm.cmd` in place of `npm`.

## Deployment and documentation

Run the read-only prerequisite checks before a production release:

```bash
php artisan app:check-deployment
php artisan app:check-deployment --database
```

Production-specific checks can fail in a local environment. For capacity diagnostics, use `php artisan capacity:report --host --json`.

Deploy with HTTPS, private uploads, supervised workers, a running scheduler, persistent session and cache storage, and verified backups. Use the guides below for release and recovery procedures.

| Guide | Scope |
| --- | --- |
| [Hostinger deployment profile](deploy/hostinger/deployment-profile.md) | Server setup, queue supervision, scheduler, and storage |
| [Deployment and recovery](docs/deployment-and-recovery.md) | Release checks, backups, and restoration |
| [Supabase deployment](docs/supabase-free-tier-deployment.md) | Database limits and capacity planning |
| [Hostinger resource plan](docs/hostinger-resource-plan.md) | Resource budgets and staging checks |
| [Employee ID migration](docs/employee-id-access-migration.md) | Moving existing accounts to official employee IDs |
| [Authentication and recovery](docs/authentication-and-recovery.md) | Password policy and administrator-assisted recovery |
| [Reference data seeding](docs/reference-data-seeding.md) | Initial reference data and synchronization |
| [Reports and masterlists](docs/reports-and-masterlists.md) | Report contents, filters, and export formats |

## Project structure

| Directory | Purpose |
| --- | --- |
| `app/Http/` | Controllers, middleware, and form requests |
| `app/Livewire/` | Interactive components |
| `app/Models/`, `app/Policies/` | Data models and authorization |
| `app/Services/` | Domain workflows, reports, and integrations |
| `app/Jobs/`, `app/Console/Commands/` | Background jobs and operational commands |
| `database/` | Migrations, reference seeders, and test factories |
| `resources/` | Blade views, stylesheets, JavaScript, and bundled assets |
| `routes/` | Web routes and scheduled tasks |
| `tests/` | Feature, unit, and JavaScript tests |
| `deploy/`, `docs/` | Deployment configuration and project documentation |

## Contributing

- Follow the existing module structure and coding conventions.
- Keep changes focused and cover behavior changes with relevant tests.
- Run applicable tests, formatting checks, and the frontend build.
- Describe configuration changes and verification in the pull request.
- Keep personal information, production records, credentials, and local environment files out of contributions.

Report security issues privately to the project maintainers.

## License

The Laravel framework is licensed under the MIT license. Confirm project-specific licensing terms with the maintainers.
