# Deployment

## Production checklist

- Use PHP 8.2+, MySQL 8, HTTPS, and a dedicated least-privilege database account.
- Set `APP_ENV=production`, `APP_DEBUG=false`, a strong `APP_KEY`, and the correct `APP_URL` and `APP_TIMEZONE`.
- Keep `.env` and credentials outside version control. Enable secure, HTTP-only, same-site session cookies for HTTPS.
- Run `composer install --no-dev --optimize-autoloader`, `npm ci`, and `npm run build` in the release process.
- Run `php artisan migrate --force`, then cache production configuration/routes/views only after environment values are final.
- Create administrators with `php artisan dtr:create-admin`; do not run the demo seeder in production.
- Back up the database before migrations and test restoration regularly. Attendance revisions and audit logs are part of the required backup set.
- Retain application/web-server logs securely and monitor failed logins, 5xx responses, database availability, and disk space.

Deploy a previous application release for code rollback. Database rollbacks require a reviewed migration strategy and a verified backup; do not blindly roll back attendance data migrations.
