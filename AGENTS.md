# AGENTS.md - Chatbot Keuangan WhatsApp

## Project Overview

A WhatsApp chatbot for financial recording with Fonnte API integration. Built with vanilla PHP 8.1+ using Clean Architecture.

---

## Tech Stack

- **Language**: PHP 8.1+
- **Database**: MySQL
- **Dependencies**: Composer (PSR-4 autoloading), PhpSpreadsheet, Google API Client
- **Authentication**: Google OAuth 2.0
- **External API**: Fonnte WhatsApp API

---

## Directory Structure

```
keuangan/
├── config.php              # Main configuration
├── database.php            # Database connection
├── composer.json          # Dependencies
├── public/
│   └── index.php          # Entry point
├── src/
│   ├── Bootstrap/
│   │   └── App.php        # Application bootstrap & routing
│   ├── Controllers/
│   │   ├── Admin/         # Admin controllers
│   │   │   ├── AuthController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── ExpenseController.php
│   │   │   └── ReportController.php
│   │   └── WebhookController.php
│   ├── Core/
│   │   ├── Auth.php       # Authentication
│   │   ├── Csrf.php       # CSRF protection
│   │   ├── Router.php     # Custom router
│   │   ├── Session.php    # Session management
│   │   └── View.php       # View renderer
│   ├── Application/
│   │   ├── Handlers/
│   │   │   └── MessageHandler.php
│   │   └── Services/
│   │       ├── ExpenseService.php
│   │       ├── FonnteService.php
│   │       └── ReportService.php
│   ├── Domain/
│   │   └── Parsers/
│   │       └── Parser.php
│   ├── Infrastructure/
│   │   ├── Database/
│   │   │   └── Connection.php
│   │   └── Reporting/
│   │       └── ExcelGenerator.php
│   ├── Repositories/
│   │   ├── AdminRepository.php
│   │   ├── CategoryRepository.php
│   │   ├── DashboardRepository.php
│   │   └── ExpenseRepository.php
│   └── Console/
│       ├── Migration.php
│       ├── MigrationRunner.php
│       ├── Seeder.php
│       └── SeederRunner.php
├── views/
│   ├── layouts/           # Layout templates
│   ├── auth/              # Auth views
│   ├── categories/        # Category views
│   ├── dashboard/         # Dashboard views
│   ├── expenses/          # Expense views
│   └── partials/          # Partial templates
└── database/
    ├── migrations/        # Database migrations
    └── seeders/           # Database seeders
```

---

## Coding Conventions

### PHP Standards
- Always use `declare(strict_types=1)` at the top of every PHP file
- Follow PSR-4 autoloading
- PSR-12 coding style
- Use PHP 8.1+ features: constructor promotion, readonly properties, enums, match expressions

### Naming Conventions
- Classes: `PascalCase` (e.g., `ExpenseController`, `CategoryRepository`)
- Methods/variables: `camelCase` (e.g., `getExpenses`, `$expenseList`)
- Constants: `UPPER_SNAKE_CASE` (e.g., `DB_HOST`, `SESSION_TIMEOUT`)
- Files: Match class name (e.g., `ExpenseController.php`)

### Architecture
- **Controllers**: Handle HTTP requests, delegate to services
- **Services**: Business logic, orchestrate repositories
- **Repositories**: Data access layer
- **Core**: Framework-like components (Router, Auth, Session, View)

---

## Configuration

All configuration via `.env` file:
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `FONNTE_TOKEN` - WhatsApp API token
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`
- `APP_ENV`, `APP_DEBUG`, `TIMEZONE`
- `WHITELIST_NUMBERS` - Allowed WhatsApp numbers
- `SESSION_IDLE_TIMEOUT_MINUTES` - Session idle timeout (default: 1440 = 24 jam)
- `SESSION_ABSOLUTE_TIMEOUT_MINUTES` - Session absolute timeout (default: 1440 = 24 jam)

Use `envValue()` and `envBool()` helper functions from `config.php`.

---

## Running the Application

### Web Server
```bash
php -S localhost:8000 -t public
```

### Run Migrations
```bash
php database/migrations/2026_02_12_000000_create_initial_schema.php
```

### Run Seeders
```bash
php database/seeders/DatabaseSeeder.php
```

---

## Available Skills

- `php-best-practices` - PHP 8.5+ best practices, PSR standards, SOLID principles
- `frontend-design` - UI/UX design for web components
- `security-reviewer` - Security audits and vulnerability scanning

---

## Important Notes

- This is a vanilla PHP project (no framework)
- Uses custom Router class in `src/Core/Router.php`
- Admin authentication via Google OAuth
- WhatsApp bot receives messages via webhook at `/webhook`
- CSRF protection enabled for all POST forms (via `Csrf` class)
- Session security headers configured in `public/index.php`
