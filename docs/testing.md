# Testing

Automated tests use the dedicated MySQL database in `.env.testing` so row locking, database constraints, and date behavior match production more closely than SQLite.

Run focused tests while developing and the complete suite before handoff:

```powershell
php artisan test tests/Feature/Attendance
php artisan test
vendor\bin\pint --test
npm run build
```

High-risk regression coverage should include authorization/ownership, duplicate attendance actions, overnight shifts, timezone boundaries, correction transactions, password/session invalidation, report filter parity, and spreadsheet formula-injection protection.

For browser QA, verify employee and administrator flows at desktop and mobile widths, keyboard focus, reduced motion, validation, empty states, print layout, and browser console errors.
