<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>
eLINGAP

## An Integrated Web-Based Records Management and Automated SMS Notification System for the Office of the Senior Citizens Affairs of Santa Maria, Bulacan

eLINGAP is a Laravel-based web application designed to support the Office of the Senior Citizens Affairs of Santa Maria, Bulacan. The system provides a centralized platform for managing senior citizen records and supporting automated SMS notifications for more efficient, organized, and timely public service.

## Project Progress

- Laravel 13 application foundation is set up.
- PostgreSQL is configured as the project database.
- Public eLINGAP landing page and responsive navigation are implemented.
- Feature folders and placeholder files are organized by Laravel domain conventions.
- Senior citizen records, applications, programs, SMS, payouts, reports, and administration modules are prepared for implementation.
- Business logic, database schema, authentication workflows, and automated SMS integration remain in progress.

## Technology Stack

- PHP 8.3 or later
- Laravel 13
- PostgreSQL
- Node.js and npm
- Vite and Tailwind CSS for frontend assets

## Prerequisites

Install the following tools before setting up the project:

- [PHP](https://www.php.net/downloads) 8.3 or later with the required Laravel extensions
- [Composer](https://getcomposer.org/download/)
- [Node.js](https://nodejs.org/) and npm
- [Git](https://git-scm.com/downloads)

## Installation

### 1. Clone the repository

Replace `<repository-url>` with the repository URL provided by the project administrator.

```bash
git clone <repository-url>
cd eLINGAP
```

### 2. Install backend dependencies

```bash
composer install
```

### 3. Configure the environment

Create the local environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

On Windows PowerShell, use this equivalent command to create the environment file:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Configure PostgreSQL in `.env`, then run the migrations:

```bash
php artisan migrate
```

Update the database, application, and SMS-related values in `.env` according to the deployment environment. Never commit `.env` or credentials to the repository.

### 4. Install frontend dependencies

```bash
npm install
npm run build
```

### 5. Start the application

In one terminal, start the Laravel development server:

```bash
php artisan serve
```

In a second terminal, start the Vite development server while working on frontend assets:

```bash
npm run dev
```

The application will be available at `http://localhost:8000`.

## Common Commands

```bash
# Run the automated test suite
composer test

# Format PHP files with Laravel Pint
vendor/bin/pint

# Build production frontend assets
npm run build
```

## Contributing

Contributions are welcome and should support the goals of the Office of the Senior Citizens Affairs of Santa Maria, Bulacan.

1. Create a feature branch from the latest main branch.
2. Make focused changes that follow the existing Laravel structure and coding conventions.
3. Add or update tests for changes that affect application behavior.
4. Run the relevant tests, formatter, and frontend build before submitting your changes.
5. Open a pull request with a clear description of the changes, motivation, testing performed, and any required configuration updates.

Please do not include personal information, production data, credentials, API keys, or local development files in commits or pull requests.

## Security

Do not report security vulnerabilities in public issues. Contact the project maintainers through the repository's private security reporting process and include enough detail to reproduce the issue safely.

## License

This project is built with the [Laravel framework](https://laravel.com), which is open-sourced under the [MIT license](https://opensource.org/licenses/MIT). Project-specific licensing terms should be confirmed with the project maintainers.
