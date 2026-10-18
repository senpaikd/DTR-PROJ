# Oras Daily Time Record

Oras is a secure Laravel Daily Time Record application for one clock-in and one clock-out per scheduled workday. Employees receive accounts directly from an administrator; public registration and email password recovery are intentionally disabled.

## Local setup with Laragon

Requirements: PHP 8.2+, Composer, Node.js/npm, and MySQL 8.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Create a MySQL database named `dtr`, update `.env` if your credentials differ, then run:

```powershell
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

The employee login is at `/login` and the administrator panel is at `/admin`.

The local-only demo seeder creates:

- Administrator: `admin@oras.test` / `password`
- Employee: `employee@oras.test` / `password`

The seeded accounts are immediately usable for local testing and do not require a first-login password change. Accounts created through the real administrator command or employee provisioning interface still require it. Never use the demo credentials in production.

## Administrator accounts

Administrator accounts are not created through the web interface. Create one from the server console:

```powershell
php artisan dtr:create-admin
```

Administrators can create employee accounts with either a manually supplied strong temporary password or a securely generated one. The plaintext temporary password is displayed once for in-person delivery.

## Development checks

Tests use the dedicated MySQL database configured in `.env.testing`.

```powershell
php artisan test
vendor\bin\pint --test
npm run build
```

See [architecture](docs/architecture.md), [domain rules](docs/domain-rules.md), [testing](docs/testing.md), and [deployment](docs/deployment.md) before changing security- or attendance-sensitive behavior.
