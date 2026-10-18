# Architecture

Oras is a Laravel 12 monolith. Blade and Tailwind provide the employee interface; Filament 5 provides the administrator panel. MySQL is the system of record.

## Boundaries

- `app/Domain/Attendance` owns schedule resolution and transactional clock state changes.
- `app/Domain/Corrections` owns employee requests, administrator decisions, immutable revisions, and direct corrections.
- `app/Domain/Accounts` owns employee provisioning, temporary credentials, password reset, and session invalidation.
- `app/Domain/Settings` provides validated organization settings.
- `app/Reports` and `app/Exports` share one filtered attendance query.
- Controllers and Filament resources validate/authorize input, then call domain services.

Attendance instants are stored in UTC. `work_date` and schedule rules are interpreted in the configured organization timezone. Changing the timezone does not rewrite historical instants or work dates.

Authentication uses Laravel sessions, CSRF protection, login throttling, active-account middleware, forced first-login password replacement, and role/ownership policies. Administrators alone may access Filament.
