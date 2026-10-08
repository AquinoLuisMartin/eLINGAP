<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>



# eLINGAP : An Integrated Web-Based Records Management and Automated SMS Notification System for the Office of the Senior Citizens Affairs of Santa Maria, Bulacan.
## Current Features

- Public landing page, login, password reset, and role-based dashboards.
- Administrator account management and account access controls.
- Senior citizen registration, record updates, photos, and death declarations or corrections.
- Program listings, beneficiary enrollment, and application status workflows.
- Payout schedules, release tracking, claimant details, and payout reversals.
- SMS templates, individual messages, broadcasts, logs, and retries.
- Application, demographic, payout, and staff reports with export routes.

The application is under active development. Some screens still contain prototype content. SMS delivery requires a configured external gateway and a running queue worker.

## Technology Stack

| Component | Project dependency |
| --- | --- |
| PHP | 8.3 or later |
| Laravel | 13 (`^13.17`) |
| Livewire | 4 (`^4.4`) |
| Database | PostgreSQL |
| Frontend | Blade, Tailwind CSS 4, Vite 8 |
| Tests | PHPUnit 12 |

Install Composer, Git, PostgreSQL, and Node.js with npm. Vite requires Node.js `^20.19.0` or `>=22.12.0`. Enable PHP's PostgreSQL extensions alongside the extensions required by Laravel.

## Local Setup

### 1. Clone and install dependencies

Replace `<repository-url>` with the project's repository URL.

```bash
git clone <repository-url>
cd eLINGAP
composer install
npm install
```

### 2. Configure the environment

Copy the environment template:

```bash
cp .env.example .env
```

For Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Create a local PostgreSQL database, then configure its connection in `.env`. The template uses PostgreSQL and database-backed sessions, cache, and queues. Configure the application URL and mail settings as needed; password reset emails require a working mail configuration.

```bash
php artisan key:generate
php artisan migrate
```

Keep `.env`, credentials, and personal data out of version control.

### 3. Create an administrator

```bash
php artisan app:create-admin
```

The command prompts interactively for account details and creates the administrator role if needed. Administrators can provision additional accounts through the application.

`php artisan db:seed` seeds roles, barangays, and users through `DatabaseSeeder`. Review the seeders before running them, especially against an existing database, because the user seeder can update accounts.

### 4. Build assets and start the application

```bash
npm run build
php artisan serve
```

Open `http://localhost:8000`. For frontend development, run `npm run dev` in a separate terminal.

For queued SMS processing, run another terminal:

```bash
php artisan queue:work
```

Alternatively, `composer dev` invokes Laravel's development command. `composer setup` installs dependencies, creates `.env` if missing, generates the application key, runs migrations, and builds assets; configure the database before using it.

## SMS Configuration

The SMS gateway reads `services.sms.url`, `services.sms.token`, and `services.sms.timeout` from `config/services.php`. Configure the corresponding environment settings locally.

The driver sends authenticated requests to the gateway's `/messages` endpoint with `to` and `message` fields and an idempotency header. It expects a JSON response containing a provider message ID in `id`. Confirm that the chosen provider supports this contract before enabling delivery.

Messages are queued after database commits. The queue job records send results and failures, retries failed attempts, and checks linked senior citizen eligibility before sending. A recorded sent status reflects the gateway request result.

## Project Layout

```text
app/
  Console/Commands/    Artisan commands, including administrator creation
  Enums/              Roles and workflow statuses
  Http/               Controllers, middleware, and form requests
  Jobs/Sms/           Queued SMS delivery
  Livewire/           Livewire components
  Models/             Eloquent models
  Policies/           Authorization rules
  Providers/          Application service bindings
  Services/           Domain logic, reports, and SMS integration
  Support/            Shared helpers
database/
  factories/          Test data factories
  migrations/         Database schema
  seeders/            Initial data and account seeding
docs/                 Project notes
resources/
  css/                Application and administration styles
  js/                 Frontend JavaScript
  views/              Blade layouts and feature views
routes/               Web and console routes
tests/                Feature and unit tests
```

Feature code is grouped around administration, authentication, senior citizens, applications, programs, payouts, reports, and SMS.

## Development Checks

The test configuration uses PostgreSQL with a separate database named `db_elingap_testing`. Create that database and ensure the local database user can access it before running database-backed tests. Use a dedicated test database because tests may reset its tables.

```bash
composer test
php artisan test --filter=SeniorCitizenManagementTest
vendor/bin/pint
npm run build
```

On Windows, use `vendor\bin\pint.bat` for formatting if the shell cannot run `vendor/bin/pint`.

## Contributing

- Follow [AGENTS.md](AGENTS.md) and the existing module conventions.
- Keep changes focused and add relevant tests for behavior changes.
- Run the applicable tests, PHP formatter, and frontend build.
- Describe configuration changes and validation in the pull request.
- Exclude personal information, production data, credentials, and local environment files.

Report security issues privately to the project maintainers.

## License

The Laravel framework is licensed under the MIT license. Confirm project-specific licensing terms with the maintainers.
