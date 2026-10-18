# Domain rules

- An employee may have at most one attendance record per local work date.
- A record has at most one clock-in and one clock-out. Repeated clicks are idempotent; breaks are outside this MVP.
- Server time is authoritative. The browser never submits official punch timestamps or employee IDs.
- Overnight clock-out resolves the still-active previous-day shift.
- Statuses are neutral: not started, clocked in, completed, missing clock-out, no record, correction pending, or corrected. The system does not infer lateness, undertime, overtime, leave, payroll, or pay.
- Employees cannot edit official attendance. They submit correction requests within the configured window.
- Approvals, adjusted approvals, rejections, and direct administrator corrections are transactional and audited. Official changes create immutable revisions.
- Employee and administrator passwords are hashed. Temporary plaintext credentials are returned only once and are never stored or logged.
- Administrator accounts are created with `php artisan dtr:create-admin`, not through the web UI.
