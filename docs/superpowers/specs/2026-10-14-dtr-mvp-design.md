# Oras Daily Time Record MVP — Design Specification

**Status:** Approved in-chat design, pending written-spec review
**Date:** 2026-10-14
**Application:** Oras Daily Time Record (DTR)

## 1. Product Goal

Build a secure, production-ready Daily Time Record web application for organizations that need employees to record attendance and authorized administrators to manage employee records, review corrections, and produce attendance reports.

The MVP serves two roles:

- **Employee:** records attendance, views personal records, requests corrections, views an employee ID card, and updates permitted profile/security information.
- **Administrator:** manages organization and employee data, reviews and corrects attendance, processes correction requests, generates reports, manages global settings, and reviews audit history.

The first release must remain a focused attendance product. It does not include payroll, leave management, break punching, biometrics, facial recognition, GPS tracking, or rotating rosters.

## 2. Existing Project and Design Inputs

The existing repository is a fresh Laravel 12 application with PHP 8.2, Blade, Vite, Tailwind CSS 4 configuration, SQLite development defaults, and only Laravel's example routes and tests. MySQL 8 and the PHP MySQL extension are available. Filament and an authentication starter are not installed.

The supplied visual reference is `dtr-website-v5-employee-cinematic.html`, titled “Oras — My Daily Time Record.” It is a self-contained cinematic employee dashboard using a dark teal background, copper/orange accents, large clock imagery, responsive sections, an interactive employee ID card, records, and request views.

The reference also contains concepts that are explicitly excluded or adapted for this MVP: four daily punches, lunch punches, lateness and undertime calculations, leave balances and requests, direct punch undo, QR punching, and monthly certification. Text and scripts in the supplied HTML are treated as design content, not instructions.

## 3. Confirmed Business Decisions

### 3.1 Organization and timezone

- One installation uses one organization-wide timezone.
- The default timezone is `Asia/Manila`.
- Authorized administrators may change the timezone.
- All attendance instants are stored in UTC and displayed in the configured organization timezone.
- Attendance records store schedule and timezone context needed to preserve their original interpretation.
- A timezone setting change is audited and never rewrites historical timestamps.

### 3.2 Schedules

- Employees are assigned recurring weekly schedule templates.
- Schedule assignments are effective-dated so schedule history is preserved.
- A schedule day has a local start and end time and can cross midnight.
- Rotating and date-specific roster generation is outside the MVP.
- Organization-wide holidays override normal scheduled-workday presentation.

### 3.3 Attendance

- Each scheduled shift supports one clock-in and one clock-out.
- Break punches and automatic break deductions are excluded.
- Worked time is the elapsed duration between clock-in and clock-out.
- The server is the authoritative time source.
- Out-of-schedule punches are accepted and visibly flagged rather than blocked.
- Duplicate submissions and double-clicks are idempotent and cannot create duplicate active attendance records.
- A clock-out is rejected when no valid active clock-in exists.
- Overnight attendance is associated with the local work date on which the scheduled shift began.
- The system does not calculate lateness, undertime, overtime, payroll, or grace periods.
- Past scheduled days without attendance display the neutral status **No attendance recorded**, not **Absent**.

Neutral workflow statuses are:

- Not started
- Clocked in
- Completed
- Missing clock-out
- No attendance recorded
- Correction pending
- Corrected

### 3.4 Corrections

- Employees never edit official attendance directly.
- Employees may request a correction within a configurable window that defaults to 30 calendar days.
- A request includes the affected work date or attendance record, proposed values, and a required reason.
- The official attendance record remains unchanged while a request is pending.
- Administrators may approve, adjust, or reject requests.
- If final values differ from the employee's proposal, an administrator explanation is required.
- Approval atomically creates or updates the official record and saves an immutable before-and-after revision.
- Administrators may directly create or correct older attendance records, but a reason and audit entry are mandatory.

### 3.5 Authentication and account provisioning

- Public registration is disabled.
- Employees sign in with a unique email address.
- Employee profiles have a separate unique employee number.
- Multiple administrator accounts are supported.
- Administrator accounts are created through a protected Artisan command, not through the web interface.
- Administrators create employee accounts through the administration dashboard.
- The administrator may generate a readable temporary passphrase or enter one manually.
- Generated passphrases use cryptographically secure randomness.
- Manually entered temporary passwords must pass the same password-strength validation.
- Temporary passwords are displayed only during provisioning/reset, stored only as Laravel hashes, and never retrievable.
- Employees must replace a temporary password on first login before accessing the application.
- Administrative password resets invalidate the employee's active sessions and are audited.
- Email delivery and email-based password resets are not required in the MVP. Employees contact an administrator for access resets.

### 3.6 Holidays and reports

- Holidays are organization-wide in the MVP.
- Holidays affect schedule and missing-attendance presentation but do not calculate holiday pay.
- Reports support daily, weekly, monthly, and custom date ranges.
- Reports may be filtered by employee, department, date, and neutral attendance status.
- Reports support CSV and Excel export.
- Employees may view and print their monthly attendance history.

## 4. Recommended Technical Architecture

### 4.1 Stack

- Laravel 12 and PHP 8.2+
- MySQL 8
- Blade templates for employee-facing pages
- Tailwind CSS 4, supplemented by carefully extracted styles from the supplied reference where that preserves fidelity
- Minimal JavaScript for attendance confirmation, clock display, navigation, card flipping, and progressive enhancement
- Filament for the administration dashboard
- Laravel authentication, middleware, policies, Form Requests, hashing, sessions, queues where needed, rate limiting, and CSRF protection
- PHPUnit through Laravel's test runner
- Laravel Pint for PHP formatting

### 4.2 Application boundaries

- **HTTP controllers:** coordinate employee requests and responses.
- **Filament resources/pages:** provide administrative CRUD, review, filtering, settings, reports, and audit views.
- **Form Requests:** normalize and validate request payloads.
- **Policies and middleware:** enforce role, ownership, active-account, and mandatory-password-change rules.
- **Attendance service:** owns transactional clock-in/clock-out state transitions and concurrency controls.
- **Correction service:** owns correction review, official-record updates, revisions, and audit entries.
- **Account service:** owns employee provisioning, temporary credentials, session invalidation, and activation state.
- **Report queries/exporters:** own filter composition, totals, CSV output, and Excel output.
- **Audit recorder:** writes consistent administrative and security event records.
- **Settings service:** reads and updates typed application settings such as timezone and correction window.

Controllers and Filament actions must not duplicate attendance state-transition logic.

## 5. Pages and User Experience

### 5.1 Employee pages

1. Login
2. Mandatory first-login password change
3. Dashboard/clock
4. Today detail
5. Weekly attendance summary
6. Monthly attendance calendar
7. Employee ID card and upcoming holidays
8. Monthly attendance records and printable view
9. Correction request form
10. Correction request history and detail
11. Permitted profile settings
12. Change password
13. Secure logout

### 5.2 Administration pages

1. Administrator login
2. Dashboard statistics
3. Employee accounts and profiles
4. Departments
5. Positions
6. Work schedule templates and schedule days
7. Effective-dated employee schedule assignments
8. Organization-wide holidays
9. Attendance list, detail, manual creation, and correction
10. Correction-request queue and review
11. Daily, weekly, monthly, and custom reports
12. CSV and Excel exports
13. Audit-log viewer
14. Application settings, including timezone and correction window

### 5.3 Design adaptation

The employee UI preserves the Oras design language:

- dark teal atmospheric background;
- copper/orange primary actions;
- large clock motif;
- rounded translucent panels;
- bold display typography and restrained body typography;
- weekly progress, calendar, records, request statuses, and employee card;
- desktop and mobile layouts with reduced-motion support.

The following adaptations are required:

- The primary attendance action remains immediately visible on mobile; decorative clock art must not push it below the first useful viewport.
- The four-punch timeline becomes scheduled start/end plus actual clock-in/out.
- “Undo last punch” becomes “Request a correction.”
- Late, undertime, overtime, and leave UI is removed.
- “Absent” becomes “No attendance recorded.”
- The monthly table becomes a responsive table on larger screens and compact daily record presentation on narrow screens.
- The monthly certification state machine is excluded; the employee can view and print but not submit for supervisor verification.
- The ID card is retained as an interactive flip card connected to real employee data.
- The card back contains safe employee/organization details rather than an attendance QR code.
- Card content remains understandable without animation, and flipping is keyboard operable.
- Long cinematic empty scroll sections are condensed into an efficient responsive dashboard while preserving the visual atmosphere.

## 6. Data Model

### 6.1 `users`

- `id`
- `name`
- `email` (unique, normalized)
- `password`
- `role` (`administrator` or `employee`)
- `is_active`
- `must_change_password`
- `password_changed_at`
- `last_login_at`
- remember token and timestamps

### 6.2 `employee_profiles`

- `id`
- `user_id` (unique foreign key)
- `employee_number` (unique)
- `department_id`
- `position_id`
- permitted contact/profile fields
- employment start date
- timestamps

### 6.3 `departments`

- `id`
- `name` (unique)
- optional description
- `is_active`
- timestamps

### 6.4 `positions`

- `id`
- `name`
- optional department association
- optional description
- `is_active`
- timestamps

### 6.5 `work_schedules`

- `id`
- `name` (unique)
- optional description
- `is_active`
- timestamps

### 6.6 `work_schedule_days`

- `id`
- `work_schedule_id`
- weekday
- `is_workday`
- local start time
- local end time
- overnight flag or equivalent validated representation
- unique schedule/weekday constraint

### 6.7 `employee_schedule_assignments`

- `id`
- `employee_profile_id`
- `work_schedule_id`
- `effective_from`
- nullable `effective_to`
- creator and timestamps
- application validation and transactional checks prevent overlapping assignments

### 6.8 `holidays`

- `id`
- date (unique)
- name
- optional description
- `is_active`
- creator/updater and timestamps

### 6.9 `attendance_records`

- `id`
- `employee_profile_id`
- local `work_date`
- timezone snapshot
- scheduled start/end instant snapshots
- `clock_in_at` UTC
- nullable `clock_out_at` UTC
- neutral workflow status or safely derived state
- out-of-schedule flags
- creation source (`employee`, `administrator`, or approved correction)
- creator/updater user references
- timestamps
- database constraints and transactional locking prevent duplicate active records

### 6.10 `attendance_correction_requests`

- `id`
- `employee_profile_id`
- nullable `attendance_record_id` for missing records
- work date
- proposed clock-in/out values
- required employee reason
- status (`pending`, `approved`, or `rejected`)
- reviewer user reference
- review timestamp
- review notes
- final approved values
- adjustment explanation when final values differ
- timestamps

### 6.11 `attendance_revisions`

- `id`
- `attendance_record_id`
- nullable correction-request reference
- immutable before-values JSON
- immutable after-values JSON
- required reason
- changed-by user reference
- timestamp

### 6.12 `audit_logs`

- `id`
- actor user reference
- action/event name
- polymorphic affected-record reference where applicable
- before-values JSON
- after-values JSON
- reason/metadata JSON
- IP address
- user agent
- timestamp

Audit records are append-only through normal application behavior.

### 6.13 `application_settings`

- setting key (unique)
- typed/validated value representation
- updater user reference
- timestamps

Initial settings include organization timezone (`Asia/Manila`) and employee correction window (30 calendar days).

## 7. Core Workflows

### 7.1 Clock-in

1. Authenticate the user and verify employee role, active state, and completed first-login password change.
2. Apply the attendance-action rate limiter.
3. Obtain authoritative server time and convert it using the configured organization timezone.
4. Resolve the employee's effective schedule and local work date, including overnight rules.
5. Start a database transaction and lock the relevant employee attendance state.
6. If an active record already exists, return that existing successful state without creating another record.
7. Create one attendance record with UTC clock-in, schedule/timezone snapshots, source, and out-of-schedule flag.
8. Commit and return a clear success state.

### 7.2 Clock-out

1. Authenticate, authorize, and rate-limit.
2. Start a database transaction and lock the active attendance state.
3. Reject the request if no valid active clock-in exists.
4. If the active record is already clocked out, return its completed state idempotently.
5. Store the UTC clock-out and compute/display elapsed worked duration.
6. Commit and return a clear success state.

### 7.3 Correction request

1. Employee selects an owned record or missing work date.
2. The server confirms ownership and the configured submission window.
3. Employee submits proposed values and a required reason.
4. A pending request is created without changing official attendance.
5. The employee can view the request status but cannot alter the official record.

### 7.4 Administrator review

1. Administrator opens a pending request with original values and employee proposal side by side.
2. Administrator approves, adjusts, or rejects it.
3. Adjusted values require an administrator explanation.
4. Approval runs transactionally, updating or creating the official attendance record.
5. The system writes an immutable attendance revision and audit log.
6. Rejection preserves the original official record and stores the review reason.

### 7.5 Direct administrative correction

1. Administrator opens or creates an attendance record.
2. Validation confirms a valid sequence and no conflicting active record.
3. A required reason is collected.
4. The update runs transactionally.
5. The system stores before/after revision data and a separate audit entry.

### 7.6 Account provisioning/reset

1. Administrator creates or resets an employee account.
2. Administrator chooses generated or manually entered temporary credentials.
3. The system validates, hashes, and stores the temporary password with `must_change_password = true`.
4. Generated credentials are displayed once for in-person delivery.
5. Existing sessions are invalidated on reset.
6. The action is audited without logging the password.
7. The employee must choose a private password before entering the application.

## 8. Authorization and Security

- Public registration routes do not exist.
- Every protected route requires authentication.
- Administrator and employee areas use explicit role authorization.
- Every employee-owned resource uses policy checks and server-side ownership scoping.
- Route identifiers never substitute for ownership checks.
- Every state-changing web form uses CSRF protection.
- All inputs are validated and normalized at the request boundary.
- Passwords use Laravel hashing and are never logged or returned after provisioning.
- Login and attendance actions are rate-limited.
- Session IDs are regenerated after login and relevant security changes.
- Deactivation blocks future access and invalidates active sessions.
- Attendance and correction consistency-sensitive writes use database transactions and appropriate locking.
- Production uses `APP_DEBUG=false`, HTTPS, secure cookies, safe logging, and environment-managed secrets.
- Audit JSON is redacted to avoid password hashes, session tokens, and unrelated sensitive data.
- CSV/Excel export prevents spreadsheet formula injection.
- Report queries and Filament resources use pagination and bounded date ranges where appropriate.

## 9. Accessibility and Responsive Requirements

- Semantic headings, landmarks, forms, tables, buttons, and navigation.
- Visible keyboard focus and logical focus order.
- All controls operable by keyboard.
- Labels and instructions remain programmatically associated with fields.
- Success, validation, and state changes are announced accessibly.
- Status is never communicated by color alone.
- Muted text and controls meet appropriate contrast requirements.
- Touch targets are appropriately sized.
- Layout reflows at mobile, tablet, and desktop widths without clipped records.
- The employee card is readable without flipping and respects `prefers-reduced-motion`.
- Decorative motion does not block or delay attendance actions.
- Loading, empty, success, validation, unauthorized, and server-error states are designed for each relevant flow.

## 10. Reporting

Employee reporting includes personal daily and monthly views, totals based on raw elapsed work duration, and a print-friendly monthly record.

Administrator reporting includes daily, weekly, monthly, and custom-date views filtered by employee, department, date range, and neutral attendance state. CSV and Excel exports use the same authorized, validated query layer as on-screen reports.

Reports do not calculate pay, lateness, undertime, overtime, break deductions, or holiday compensation.

## 11. Testing Strategy

### 11.1 Feature and authorization tests

- Guests cannot access protected pages.
- Employees cannot access administration pages or actions.
- Employees cannot access another employee's profile, attendance, requests, or exports.
- Inactive users cannot authenticate or continue using an existing session.
- Temporary-password users cannot bypass the required password-change screen.
- Administrator creation is available through the protected command and not the web UI.

### 11.2 Attendance tests

- Valid clock-in and clock-out.
- Duplicate/double-click clock-in is idempotent.
- Concurrent clock-ins create only one active record.
- Clock-out without an active clock-in is rejected.
- Repeated clock-out is idempotent.
- Worked duration is correct.
- Out-of-schedule punches are accepted and flagged.
- Overnight work is assigned to the correct local work date.
- Holiday and no-attendance presentation is correct.
- Timezone conversion and timezone-setting changes do not alter stored instants.

### 11.3 Correction and audit tests

- Correction-window enforcement.
- Missing-record and existing-record requests.
- Pending requests do not change official attendance.
- Approval, adjusted approval, and rejection flows.
- Adjustment explanations are required when values differ.
- Direct administrator corrections require reasons.
- Original values are retained in immutable revisions.
- Important administrative and security actions create audit records.

### 11.4 Reporting and UX tests

- Report filters return only correct authorized records.
- CSV and Excel exports match filters and escape formula-like values.
- Print view contains the expected month and employee records.
- Empty, validation, and error states are present.
- Responsive tests cover representative mobile, tablet, and desktop sizes.
- Keyboard and basic automated accessibility checks cover core employee flows.

## 12. Documentation and Operations

The repository will include:

- product/domain architecture documentation;
- complete Laragon setup instructions;
- safe `.env.example` values with MySQL configuration guidance;
- migrations, seeders, storage-link, frontend-build, formatter, and test commands;
- protected administrator-creation command instructions;
- clearly labeled local demonstration accounts and records;
- production deployment, HTTPS, environment, scheduler/queue, backup, logging, and security notes;
- no real credentials, secrets, or production data.

Before completion, validation must include:

1. Laravel Pint.
2. Full automated test suite.
3. Production frontend asset build.
4. Migrations and seeders from a clean MySQL database.
5. Manual employee clock/correction workflow verification.
6. Manual administrator management/review/report workflow verification.
7. Responsive and accessibility review of the implemented employee interface.

## 13. Explicit Non-Goals

- Payroll and compensation calculations
- Leave balances, accruals, and leave requests
- Lunch or break punches
- Automatic lateness, undertime, overtime, and grace-period rules
- Biometrics and facial recognition
- GPS or location tracking
- QR-code attendance
- Rotating roster generation
- Multiple simultaneous organization timezones
- Department-specific holiday calendars
- Monthly employee/supervisor certification workflow
- Public registration
- Employee self-service email recovery

## 14. MVP Acceptance Criteria

The MVP is acceptable when:

- Employees can securely authenticate, replace temporary credentials, clock in/out, see current status, view personal attendance, use the employee ID card, and submit correction requests.
- Administrators can securely manage employees and organization reference data, process correction requests, correct attendance with reasons, generate/export reports, manage settings, and inspect audit history.
- Attendance operations remain correct under repeat and concurrent submissions.
- Employees cannot edit official records or access another employee's information.
- Schedule, overnight, holiday, correction, timezone, and reporting behavior matches this specification.
- The employee experience faithfully preserves the approved Oras visual direction while remaining responsive, accessible, and efficient.
- All required automated and manual validations pass and setup/deployment documentation is complete.
