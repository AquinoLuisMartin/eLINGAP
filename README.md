<p align="center">
  <a href="https://laravel.com" target="_blank">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
  </a>
</p>

<p align="center">
  <a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Laravel framework tests"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Laravel framework downloads"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Laravel framework version"></a>
  <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="Laravel framework license"></a>
</p>

<h1 align="center">eLINGAP</h1>

<p align="center">
  An integrated web-based records management and automated SMS notification system
  for the Office of the Senior Citizens Affairs of Santa Maria, Bulacan.
</p>

## Overview

eLINGAP centralizes senior citizen records, benefit-program workflows, payout
monitoring, reports, staff access, and SMS notifications in one application.
It is designed for authenticated office workflows with role-based access and
auditable operational records.

> The application is under active development. SMS delivery requires a
> configured external gateway and a running queue worker.

## Contents

- [Documentation](#documentation)
- [Features](#features)
- [Technology](#technology)
- [Requirements](#requirements)
- [Installation](#installation)
- [Operations](#operations)
- [SMS configuration](#sms-configuration)
- [Project structure](#project-structure)
- [Development checks](#development-checks)
- [Contributing](#contributing)

## Documentation

Start with this README for setup and routine operations. Use the following guides
for deployment planning and recovery:

- [Hostinger deployment profile](deploy/hostinger/deployment-profile.md): server, worker, scheduler, and storage configuration.
- [Deployment and recovery](docs/deployment-and-recovery.md): release checks, backups, and restoration.
- [Supabase Free deployment](docs/supabase-free-tier-deployment.md): database limits, read optimizations, and capacity planning.
- [Hostinger resource plan](docs/hostinger-resource-plan.md): resource budgets and staging acceptance checks.

## Features

### Records and administration

- Public landing page, authentication, password reset, and role-based dashboards.
- Administrator account management and access controls.
- Senior citizen registration, private supporting documents, OSCA ID issuance, and death declarations or corrections.
- Reference data for barangays, roles, programs, and office settings.

### Benefits and payouts

- Benefit-program listings and beneficiary enrollment.
- Application status and review workflows.
- Payout schedules, release tracking, claimant details, and reversals.
- Application, demographic, payout, and staff reports with export routes.

### Messaging

- Reusable SMS templates and individual messages.
- Broadcast messaging, delivery logs, retries, and failure tracking.
- Atomic database commits for messages and queued jobs, with eligibility checks before sending.

## Technology

| Area | Stack |
| --- | --- |
| Backend | PHP 8.4.27 security baseline, Laravel 13 |
| Application UI | Blade, Livewire 4, Alpine.js |
| Styling and assets | Tailwind CSS 4, Vite 8 |
| Database | PostgreSQL |
| Documents and exports | Dompdf, PhpSpreadsheet, QR code packages |
| Testing | PHPUnit 12 |

## Requirements

Install the following before setting up the application:

- PHP **8.4.27**, or a newer patched PHP 8.4 release, with the extensions
  required by the locked dependencies, including `pdo_pgsql`.
- Composer.
- PostgreSQL.
- Node.js `^20.19.0` or `>=22.12.0` with npm.

PHP 8.4.27 is the project's security baseline. Use the same patched version for
the CLI, web server, and queue workers. Verify the active CLI version with
`php --version` before installation; this documentation does not upgrade PHP.

## Installation

### 1. Clone the repository

Replace `<repository-url>` with the repository URL:

```bash
git clone <repository-url>
cd eLINGAP
composer install
npm install
```

### 2. Configure the environment

Create the local environment file.

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Create a local PostgreSQL database and update the `DB_*` values in `.env`.
The application uses PostgreSQL and database-backed queues. Local defaults use
database-backed sessions and cache; the single-server Hostinger profile uses
persistent file sessions and cache to reduce database traffic. Configure the
application URL and any notification mail settings as needed. Password recovery
uses administrator identity verification and private code handover.

Run the migrations:

```bash
php artisan migrate
```

Keep `.env`, credentials, and personal data out of version control.

### 3. Seed reference data and create an administrator

Seed the initial reference roles, barangays, and benefit-program definitions:

```bash
php artisan db:seed
```

Create the first administrator interactively:

```bash
php artisan app:create-admin
```

The seeder does not create demo users or replace existing accounts.
Administrators can provision additional accounts through the application.

### 4. Build and run the application

Build the frontend assets and start the local server:

```bash
npm run build
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000) in a browser. During
frontend development, run `npm run dev` in a separate terminal.

For a convenient local development command, use:

```bash
composer dev
```

## Operations

### Queue worker

Run a queue worker in a separate terminal when processing SMS or other queued
work:

```bash
php artisan queue:work database-atomic --queue=default --timeout=60
```

Messages and jobs commit together on the same PostgreSQL connection; workers
process them after commit. The queue job records send results and failures,
retries failed attempts, and checks linked senior citizen eligibility before
sending. Production workers should run under a process supervisor using the
[deployment profile](deploy/hostinger/deployment-profile.md).

### Scheduler and capacity

Configure the Laravel scheduler in production for operational maintenance and
optional capacity monitoring. Use the deployment profile's cron template.

```bash
php artisan capacity:report --host --json
```

The report checks database size, private-disk headroom, and queue backlog without
printing record contents. Monitor Supabase egress separately in its dashboard.

### Deployment checks

Run the deployment prerequisite checks before a production rollout:

```bash
php artisan app:check-deployment
```

Useful options:

```bash
php artisan app:check-deployment --json
php artisan app:check-deployment --database
```

The command does not modify configuration or records and returns a nonzero exit
code when a check fails. Checks that require production settings are expected
to fail in a local development environment.

Production should use the canonical HTTPS application URL, secure session
cookies, encrypted session storage, private uploads, persistent sessions and
cache, built assets, Argon2id support, and administrator-assisted password recovery. Verify
encryption, backups, restoration, browser accessibility, and performance
separately before rollout.

Authentication uses usernames only. Administrators provision staff with one-time
temporary-password displays and personally verify recovery requests before
issuing single-use codes. See [authentication and recovery](docs/authentication-and-recovery.md)
for the migration, password policy, handover procedure and console recovery.

## SMS configuration

The SMS gateway reads the following values from `config/services.php`:

| Setting | Purpose |
| --- | --- |
| `services.sms.url` | Gateway base URL |
| `services.sms.token` | Authentication token |
| `services.sms.timeout` | Request timeout |

Configure the corresponding environment variables locally. The driver sends
authenticated requests to the gateway's `/messages` endpoint with `to` and
`message` fields and an idempotency header. It expects a JSON response
containing the provider message ID in `id`. Confirm that the selected provider
supports this contract before enabling delivery.

A recorded `sent` status reflects the gateway request result; it does not
replace provider-side delivery confirmation.

## Project structure

```text
app/
  Actions/             Application actions
  Console/Commands/    Artisan commands, including administrator creation
  Enums/               Roles and workflow statuses
  Http/                Controllers, middleware, and form requests
  Jobs/                Queued work, including SMS delivery
  Livewire/            Livewire components
  Models/              Eloquent models
  Policies/            Authorization rules
  Providers/           Application service bindings
  Services/            Domain logic, reports, and integrations
  Support/             Shared helpers
database/
  factories/           Test data factories
  migrations/          Database schema
  seeders/             Reference and account seeders
resources/
  css/                 Application and administration styles
  js/                  Frontend JavaScript
  views/               Blade layouts and feature views
routes/
  console.php          Console routes
  web.php              Web routes
tests/
  Feature/             Feature and workflow tests
  Unit/                Unit tests
  Js/                  JavaScript tests
docs/                  Project notes and operational documentation
```

Feature code is organized around authentication, administration, senior
citizens, applications, programs, payouts, reports, and SMS.

## Development checks

The test configuration uses PostgreSQL with a separate database named
`db_elingap_testing`. Create that database and ensure the local database user
can access it before running database-backed tests. Tests may reset tables, so
do not use a production database.

Run the main checks:

```bash
composer test
php artisan test --filter=SeniorCitizenManagementTest
vendor/bin/pint
npm run build
```

On Windows, use the formatter batch file if the shell cannot run the Unix-style
path:

```powershell
vendor\bin\pint.bat
```

## Contributing

- Follow [AGENTS.md](AGENTS.md) and the existing module conventions.
- Keep changes focused and add tests for behavior changes.
- Run the applicable tests, PHP formatter, and frontend build.
- Document configuration changes and validation in the pull request.
- Never include personal information, production data, credentials, or local
  environment files.

Report security issues privately to the project maintainers.

## License

The Laravel framework is licensed under the MIT license. Confirm project-specific
licensing terms with the maintainers.
