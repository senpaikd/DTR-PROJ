# Oras DTR MVP Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver the approved secure Oras Daily Time Record MVP for employees and administrators.

**Architecture:** Laravel owns authentication, authorization, schedules, transactional attendance, corrections, settings, reporting, and auditing. Employees use a responsive Blade/Tailwind interface adapted from the supplied Oras design; administrators use a Filament 5 panel backed by the same services and policies.

**Tech Stack:** PHP 8.2, Laravel 12, MySQL 8, Blade, Tailwind CSS 4, minimal JavaScript, Filament 5, Laravel Excel 3.1, PHPUnit 11, Laravel Pint, Vite.

**Spec:** `docs/superpowers/specs/2026-10-14-dtr-mvp-design.md`

## Global Constraints

- Do not initialize Git, commit, create a remote, or push; the user will create the repository after coding.
- Public registration and email-based password recovery remain disabled.
- Use one organization timezone, default `Asia/Manila`; store attendance instants in UTC.
- Use one clock-in and one clock-out per shift; do not add breaks, leave, payroll, lateness, undertime, overtime, GPS, biometrics, QR punching, or certification.
- Employee attendance writes use server time, transactions, locking, idempotency, authorization, and rate limiting.
- Employees never edit official attendance directly.
- Preserve the approved Oras visual direction and employee ID flip card while keeping mobile attendance actions immediately accessible.
- Use policies, Form Requests, CSRF, normalized input, hashed passwords, session regeneration/invalidation, and production-safe errors.
- Keep secrets and real credentials out of source; demonstration credentials must be clearly local-only.
- Follow TDD for each behavior task; run focused tests after each change and the full validation suite at completion.

## Review Focus

1. Two near-simultaneous clock-in requests must produce one active record and two successful/idempotent responses — pinned in Task 5 concurrency tests.
2. A timezone change near midnight must not rewrite or reassign historical attendance instants — pinned in Task 5 timezone tests.
3. Overnight clock-out must resolve the previous local work date and correct active record — pinned in Task 5 overnight tests.
4. Crafted employee URLs and report filters must never reveal another employee's records — pinned in Tasks 4, 8, and 12 authorization tests.
5. Spreadsheet cells beginning with formula characters must be exported as literal text — pinned in Task 12 export-injection tests.

---

## File and Responsibility Map

- `app/Domain/Attendance/*`: schedule resolution, clock state transitions, duration/state value objects.
- `app/Domain/Corrections/*`: correction submission and administrative review.
- `app/Domain/Accounts/*`: temporary credential generation and employee provisioning/reset.
- `app/Domain/Auditing/*`: consistent append-only audit recording.
- `app/Domain/Settings/*`: typed application settings and timezone access.
- `app/Models/*`: persistence and relationships only; avoid embedding workflow orchestration.
- `app/Policies/*` and `app/Http/Middleware/*`: authorization and access gates.
- `app/Http/Controllers/Employee/*`: employee web endpoints.
- `app/Http/Requests/*`: employee/admin request validation and normalization.
- `app/Filament/Resources/*`, `Pages/*`, and `Widgets/*`: administrator experience.
- `app/Reports/*` and `app/Exports/*`: filtered report queries and download formats.
- `resources/views/*`: Oras employee interface and print views.
- `resources/css/app.css`: design tokens, responsive layouts, focus/reduced-motion/print rules.
- `resources/js/app.js`: minimal progressive enhancement only.
- `tests/Feature/*` and `tests/Unit/*`: behavior, authorization, workflow, export, and rendering evidence.

### Task 1: Runtime Dependencies and Application Foundation

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock`
- Modify: `package-lock.json` (generated)
- Create: `app/Providers/Filament/AdminPanelProvider.php` (generated, then configured)
- Modify: `bootstrap/providers.php`
- Modify: `config/app.php`
- Modify: `.env.example`
- Test: `tests/Feature/Foundation/ApplicationConfigurationTest.php`

**Interfaces:**
- Consumes: existing Laravel 12 skeleton.
- Produces: Filament 5 admin panel at `/admin`; Laravel Excel 3.1 export support; `config('app.timezone')` driven by `APP_TIMEZONE` with `Asia/Manila` fallback.

- [ ] **Step 1: Write failing foundation tests** asserting production registration routes are absent, application timezone defaults to `Asia/Manila`, and `/admin/login` is reachable.
- [ ] **Step 2: Run `php artisan test tests/Feature/Foundation/ApplicationConfigurationTest.php`** and verify failures identify the missing configuration/panel.
- [ ] **Step 3: Install compatible dependencies** with PowerShell-safe Filament 5 and Laravel Excel 3.1 constraints; run Filament panel installation without overwriting the supplied employee design source.
- [ ] **Step 4: Install frontend dependencies** with `npm install` and retain Tailwind 4/Vite integration.
- [ ] **Step 5: Configure environment defaults** including `APP_TIMEZONE=Asia/Manila`, MySQL examples, secure production notes in comments only where appropriate, and no credentials.
- [ ] **Step 6: Configure the panel** at `/admin` with registration disabled; defer panel access authorization to Task 4.
- [ ] **Step 7: Run the focused tests and `npm run build`**; expect all foundation assertions and asset compilation to pass.
- [ ] **Step 8: Record a local checkpoint**; do not initialize or commit Git.

### Task 2: Domain Schema, Models, Factories, and Settings

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php`
- Create: `database/migrations/*_create_departments_table.php`
- Create: `database/migrations/*_create_positions_table.php`
- Create: `database/migrations/*_create_employee_profiles_table.php`
- Create: `database/migrations/*_create_work_schedules_table.php`
- Create: `database/migrations/*_create_work_schedule_days_table.php`
- Create: `database/migrations/*_create_employee_schedule_assignments_table.php`
- Create: `database/migrations/*_create_holidays_table.php`
- Create: `database/migrations/*_create_attendance_records_table.php`
- Create: `database/migrations/*_create_attendance_correction_requests_table.php`
- Create: `database/migrations/*_create_attendance_revisions_table.php`
- Create: `database/migrations/*_create_audit_logs_table.php`
- Create: `database/migrations/*_create_application_settings_table.php`
- Create/Modify: matching files under `app/Models/` and `database/factories/`
- Create: `app/Enums/UserRole.php`
- Create: `app/Enums/AttendanceStatus.php`
- Create: `app/Enums/CorrectionRequestStatus.php`
- Create: `app/Domain/Settings/ApplicationSettings.php`
- Test: `tests/Feature/Database/DomainSchemaTest.php`
- Test: `tests/Unit/Settings/ApplicationSettingsTest.php`

**Interfaces:**
- Consumes: Laravel database and casting conventions.
- Produces: `ApplicationSettings::timezone(): DateTimeZone`, `ApplicationSettings::correctionWindowDays(): int`, and typed model relationships used by every later task.

- [ ] **Step 1: Write failing schema tests** for unique email/employee number/holiday date, schedule-day uniqueness, attendance relationships, role/status casts, and `Asia/Manila` plus 30-day setting defaults.
- [ ] **Step 2: Run the focused tests** and verify failures are caused by missing migrations/models.
- [ ] **Step 3: Implement migrations and enums** using UTC timestamp columns, local work dates, schedule/timezone snapshots, actor foreign keys, append-only revision/audit structures, and database indexes for report filters.
- [ ] **Step 4: Implement focused Eloquent models and factories** with explicit fillable/guarded fields, casts, and relationships; do not put workflows in models.
- [ ] **Step 5: Implement `ApplicationSettings`** with validated database overrides and safe defaults.
- [ ] **Step 6: Run focused tests and `php artisan migrate:fresh --env=testing`**; expect clean schema creation and passing assertions.
- [ ] **Step 7: Record a local checkpoint**; do not initialize or commit Git.

### Task 3: Account Authentication and Provisioning

**Files:**
- Create: `app/Domain/Accounts/TemporaryPassphraseGenerator.php`
- Create: `app/Domain/Accounts/EmployeeAccountService.php`
- Create: `app/Console/Commands/CreateAdministrator.php`
- Create: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Create: `app/Http/Controllers/Auth/FirstLoginPasswordController.php`
- Create: `app/Http/Requests/Auth/LoginRequest.php`
- Create: `app/Http/Requests/Auth/UpdateFirstLoginPasswordRequest.php`
- Create: `app/Http/Middleware/EnsurePasswordChanged.php`
- Modify: `app/Models/User.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/auth/first-login-password.blade.php`
- Test: `tests/Feature/Auth/AuthenticationTest.php`
- Test: `tests/Feature/Auth/FirstLoginPasswordTest.php`
- Test: `tests/Feature/Console/CreateAdministratorTest.php`
- Test: `tests/Unit/Accounts/TemporaryPassphraseGeneratorTest.php`

**Interfaces:**
- Consumes: `UserRole`, employee models, Laravel hashing/session/authentication.
- Produces: `TemporaryPassphraseGenerator::generate(): string`; `EmployeeAccountService::create(array $userData, array $profileData, ?string $temporaryPassword): ProvisionedEmployee`; `resetPassword(User $user, ?string $temporaryPassword): TemporaryCredential`; `dtr:create-admin` command.

- [ ] **Step 1: Write failing tests** for login throttling, inactive accounts, session regeneration, logout, no registration/recovery routes, passphrase strength, first-login gating, administrator creation, manual/generated temporary credentials, one-time plaintext return, hashed storage, and session invalidation after reset.
- [ ] **Step 2: Run focused tests** and verify expected missing behavior.
- [ ] **Step 3: Implement temporary passphrase generation and account service**; never persist or log plaintext credentials.
- [ ] **Step 4: Implement `dtr:create-admin`** with interactive/non-interactive validated inputs, duplicate protection, and mandatory first-login password replacement unless explicitly creating a local test fixture.
- [ ] **Step 5: Implement authentication routes/controllers/requests/middleware** with public registration and email recovery absent.
- [ ] **Step 6: Build accessible auth views** using Oras colors and typography without the heavy dashboard scene.
- [ ] **Step 7: Run focused tests**; expect all authentication and provisioning tests to pass.
- [ ] **Step 8: Record a local checkpoint**; do not initialize or commit Git.

### Task 4: Authorization, Ownership, and Audit Foundation

**Files:**
- Create: `app/Policies/EmployeeProfilePolicy.php`
- Create: `app/Policies/AttendanceRecordPolicy.php`
- Create: `app/Policies/AttendanceCorrectionRequestPolicy.php`
- Create: `app/Policies/AuditLogPolicy.php`
- Create: `app/Http/Middleware/EnsureActiveUser.php`
- Create: `app/Domain/Auditing/AuditRecorder.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Authorization/RoleAndOwnershipTest.php`
- Test: `tests/Unit/Auditing/AuditRecorderTest.php`

**Interfaces:**
- Consumes: roles and models from Task 2.
- Produces: `User::canAccessPanel(Panel $panel): bool`; `AuditRecorder::record(User $actor, string $action, ?Model $subject, array $before, array $after, array $metadata = []): AuditLog`.

- [ ] **Step 1: Write failing authorization tests** for guests, employees entering `/admin`, inactive users, employee cross-record URL access, administrator access, and audit redaction of password/session/token fields.
- [ ] **Step 2: Run focused tests** and verify access is not yet correctly enforced.
- [ ] **Step 3: Implement middleware and policies** with ownership scoped through the authenticated employee profile, never request-provided employee IDs.
- [ ] **Step 4: Implement Filament panel access** for active administrators only.
- [ ] **Step 5: Implement `AuditRecorder`** as an append-only writer with sensitive-key redaction and request metadata capture.
- [ ] **Step 6: Run focused tests**; expect all role, ownership, panel, and audit assertions to pass.
- [ ] **Step 7: Record a local checkpoint**; do not initialize or commit Git.

### Task 5: Schedule Resolution and Transactional Attendance

**Files:**
- Create: `app/Domain/Attendance/ResolvedShift.php`
- Create: `app/Domain/Attendance/ScheduleResolver.php`
- Create: `app/Domain/Attendance/AttendanceActionResult.php`
- Create: `app/Domain/Attendance/AttendanceService.php`
- Create: `app/Domain/Attendance/AttendanceStatusResolver.php`
- Create: `app/Exceptions/AttendanceSequenceException.php`
- Test: `tests/Unit/Attendance/ScheduleResolverTest.php`
- Test: `tests/Unit/Attendance/AttendanceStatusResolverTest.php`
- Test: `tests/Feature/Attendance/AttendanceServiceTest.php`
- Test: `tests/Feature/Attendance/AttendanceConcurrencyTest.php`

**Interfaces:**
- Consumes: schedules, assignments, holidays, settings, attendance models.
- Produces: `ScheduleResolver::forEmployeeAt(EmployeeProfile $employee, CarbonImmutable $instant): ?ResolvedShift`; `AttendanceService::clockIn(User $employeeUser): AttendanceActionResult`; `clockOut(User $employeeUser): AttendanceActionResult`; `AttendanceStatusResolver::forDate(EmployeeProfile $employee, CarbonImmutable $localDate): AttendanceStatus`.

- [ ] **Step 1: Write failing schedule/status tests** for recurring weekdays, assignment effective dates, holidays, no schedule, overnight shifts, missing attendance, missing clock-out, corrected records, and timezone boundaries.
- [ ] **Step 2: Write failing attendance service tests** for valid sequences, server-time authority, out-of-schedule flags, duplicate idempotency, invalid clock-out, repeated clock-out, elapsed duration, actor/source fields, and immutable timestamp behavior after timezone change.
- [ ] **Step 3: Write a database concurrency test** that dispatches two clock-ins and asserts one active row plus equivalent successful results; use a MySQL integration test where SQLite cannot prove row-lock semantics.
- [ ] **Step 4: Run focused tests** and verify missing-domain failures.
- [ ] **Step 5: Implement `ResolvedShift` and `ScheduleResolver`** using local schedule interpretation and UTC instant snapshots, including previous-day overnight resolution.
- [ ] **Step 6: Implement transactional `AttendanceService`** with row/advisory locking as supported by MySQL, database uniqueness, idempotent repeated actions, and no client-supplied timestamps.
- [ ] **Step 7: Implement neutral status resolution** without lateness, undertime, overtime, payroll, or leave assumptions.
- [ ] **Step 8: Run unit/feature tests on SQLite and concurrency tests on MySQL**; expect all attendance assertions to pass.
- [ ] **Step 9: Record a local checkpoint**; do not initialize or commit Git.

### Task 6: Correction Requests, Administrative Changes, and Revisions

**Files:**
- Create: `app/Domain/Corrections/CorrectionRequestService.php`
- Create: `app/Domain/Corrections/CorrectionReviewData.php`
- Create: `app/Domain/Corrections/CorrectionReviewService.php`
- Create: `app/Http/Requests/Attendance/StoreCorrectionRequest.php`
- Test: `tests/Feature/Corrections/CorrectionSubmissionTest.php`
- Test: `tests/Feature/Corrections/CorrectionReviewTest.php`
- Test: `tests/Feature/Corrections/AdministrativeCorrectionTest.php`

**Interfaces:**
- Consumes: attendance service/models, settings, policies, and `AuditRecorder`.
- Produces: `CorrectionRequestService::submit(User $employeeUser, array $validated): AttendanceCorrectionRequest`; `CorrectionReviewService::approve(User $administrator, AttendanceCorrectionRequest $request, CorrectionReviewData $data): AttendanceRecord`; `reject(User $administrator, AttendanceCorrectionRequest $request, string $reason): void`; `correctDirectly(User $administrator, AttendanceRecord $record, array $values, string $reason): AttendanceRecord`.

- [ ] **Step 1: Write failing submission tests** for ownership, 30-day default window, configured window, required reason, valid sequences, missing records, and pending requests leaving official data untouched.
- [ ] **Step 2: Write failing review tests** for approve/adjust/reject, explanation required on adjusted values, immutable before/after revisions, transactions, duplicate review protection, and audit entries.
- [ ] **Step 3: Write failing direct-correction tests** for required reasons, valid clock order, revision preservation, and older-record administration.
- [ ] **Step 4: Run focused tests** and verify workflow failures.
- [ ] **Step 5: Implement correction submission and review value object/services** with transactions and locked request/attendance rows.
- [ ] **Step 6: Implement direct administrative correction through the same revision/audit primitives** rather than duplicating persistence logic.
- [ ] **Step 7: Run focused tests**; expect all correction, revision, transaction, and audit assertions to pass.
- [ ] **Step 8: Record a local checkpoint**; do not initialize or commit Git.

### Task 7: Employee Oras Layout and Reusable Components

**Files:**
- Create: `resources/views/components/layouts/employee.blade.php`
- Create: `resources/views/components/employee/*` for navigation, panel, status badge, empty state, flash message, field error, and loading state
- Create: `resources/views/components/employee/id-card.blade.php`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Create: `tests/Feature/Views/EmployeeLayoutTest.php`
- Create: `tests/Feature/Views/EmployeeAccessibilityMarkupTest.php`

**Interfaces:**
- Consumes: authenticated user/profile and Vite assets.
- Produces: `<x-layouts.employee>` and reusable employee components used by Tasks 8–9.

- [ ] **Step 1: Write failing view tests** for semantic landmarks, navigation, focus target, flash/error regions, no leave/payroll copy, reduced-motion class/hooks, and employee ID card accessible controls.
- [ ] **Step 2: Run focused tests** and verify missing components.
- [ ] **Step 3: Extract Oras design tokens and layout patterns** from the supplied HTML into maintainable CSS; keep the teal/copper identity and typography while avoiding copied mock business logic.
- [ ] **Step 4: Implement responsive layout and components** with mobile navigation, visible focus, sufficient contrast, keyboard card flip, static fallback, and no essential information hidden behind animation.
- [ ] **Step 5: Keep JavaScript minimal** for navigation/card enhancement and respect `prefers-reduced-motion`.
- [ ] **Step 6: Run focused tests and `npm run build`**; inspect representative desktop/mobile component renders.
- [ ] **Step 7: Record a local checkpoint**; do not initialize or commit Git.

### Task 8: Employee Clock Dashboard and ID Card

**Files:**
- Create: `app/Http/Controllers/Employee/DashboardController.php`
- Create: `app/Http/Controllers/Employee/AttendanceActionController.php`
- Create: `app/Http/Resources/EmployeeDashboardData.php` or focused presenter classes
- Create: `resources/views/employee/dashboard.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Employee/DashboardTest.php`
- Test: `tests/Feature/Employee/AttendanceActionsTest.php`

**Interfaces:**
- Consumes: `AttendanceService`, schedule/status resolvers, employee components.
- Produces: authenticated employee dashboard; `POST /attendance/clock-in`; `POST /attendance/clock-out`.

- [ ] **Step 1: Write failing dashboard tests** for server-rendered current state, schedule, actual times, weekly hours, monthly calendar statuses, upcoming holidays, employee ID data, and cross-employee isolation.
- [ ] **Step 2: Write failing HTTP action tests** for CSRF/auth/role, rate limiting, idempotent double-clicks, invalid clock-out feedback, and redirects/flash states.
- [ ] **Step 3: Run focused tests** and verify missing endpoints/views.
- [ ] **Step 4: Implement controller/presenters/routes** without accepting employee IDs or timestamps from the browser.
- [ ] **Step 5: Build the dashboard** with clock action first on mobile, decorative clock as non-blocking progressive enhancement, single in/out timeline, weekly summary, neutral monthly calendar, holidays, and approved flip ID card.
- [ ] **Step 6: Replace direct undo with correction-request navigation** and exclude leave/late/undertime/certification UI.
- [ ] **Step 7: Run focused tests, build assets, and visually inspect desktop/mobile states** including not started, clocked in, completed, and errors.
- [ ] **Step 8: Record a local checkpoint**; do not initialize or commit Git.

### Task 9: Employee History, Print View, Profile, and Correction UI

**Files:**
- Create: `app/Http/Controllers/Employee/AttendanceHistoryController.php`
- Create: `app/Http/Controllers/Employee/CorrectionRequestController.php`
- Create: `app/Http/Controllers/Employee/ProfileController.php`
- Create: `app/Http/Requests/Profile/UpdateEmployeeProfileRequest.php`
- Create: `resources/views/employee/attendance/index.blade.php`
- Create: `resources/views/employee/attendance/show.blade.php`
- Create: `resources/views/employee/attendance/print.blade.php`
- Create: `resources/views/employee/corrections/index.blade.php`
- Create: `resources/views/employee/corrections/create.blade.php`
- Create: `resources/views/employee/profile/edit.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Employee/AttendanceHistoryTest.php`
- Test: `tests/Feature/Employee/CorrectionPagesTest.php`
- Test: `tests/Feature/Employee/ProfileTest.php`

**Interfaces:**
- Consumes: correction submission service, policies, layout/components.
- Produces: owned daily/monthly history, responsive/printable records, correction screens, and permitted profile update.

- [ ] **Step 1: Write failing tests** for daily/month filters, monthly totals, neutral statuses, print output, request validation/status filtering, ownership tampering, and permitted-versus-protected profile fields.
- [ ] **Step 2: Run focused tests** and verify missing routes/views.
- [ ] **Step 3: Implement scoped controllers/requests/routes** using authenticated ownership and bounded date parsing.
- [ ] **Step 4: Build responsive history** using a desktop table and compact mobile daily cards; ensure no horizontal clipping.
- [ ] **Step 5: Build correction form/history** adapted from the prototype with only clock-in/out proposals and approved statuses.
- [ ] **Step 6: Build print and profile views** with semantic fields and print-specific CSS.
- [ ] **Step 7: Run focused tests, asset build, and desktop/mobile/print visual checks**.
- [ ] **Step 8: Record a local checkpoint**; do not initialize or commit Git.

### Task 10: Filament Organization and Employee Administration

**Files:**
- Create: `app/Filament/Resources/Departments/*`
- Create: `app/Filament/Resources/Positions/*`
- Create: `app/Filament/Resources/WorkSchedules/*`
- Create: `app/Filament/Resources/Holidays/*`
- Create: `app/Filament/Resources/Employees/*`
- Create: `app/Http/Requests/Admin/*` only where logic is shared outside Filament schemas/actions
- Test: `tests/Feature/Admin/OrganizationResourcesTest.php`
- Test: `tests/Feature/Admin/EmployeeManagementTest.php`

**Interfaces:**
- Consumes: account service, models, policies, settings.
- Produces: admin CRUD for departments, positions, schedules/days, assignments, holidays, and employee lifecycle.

- [ ] **Step 1: Write failing Livewire/Filament feature tests** for administrator-only access, create/edit/activate/deactivate, unique constraints, schedule validation, non-overlapping assignments, credential mode choice, reset/session invalidation, and audit entries.
- [ ] **Step 2: Run focused tests** and verify missing resources.
- [ ] **Step 3: Implement reference-data resources** with search, filters, validation, active states, and safe delete/deactivate behavior.
- [ ] **Step 4: Implement employee resource/actions** through `EmployeeAccountService`, including one-time temporary credential display and no current-password visibility.
- [ ] **Step 5: Implement schedule assignment management** with effective dates and overlap protection.
- [ ] **Step 6: Run focused tests**; manually verify Filament forms/tables and credential flows.
- [ ] **Step 7: Record a local checkpoint**; do not initialize or commit Git.

### Task 11: Filament Attendance, Corrections, Settings, Audit, and Dashboard

**Files:**
- Create: `app/Filament/Resources/AttendanceRecords/*`
- Create: `app/Filament/Resources/CorrectionRequests/*`
- Create: `app/Filament/Resources/AuditLogs/*`
- Create: `app/Filament/Pages/ManageApplicationSettings.php`
- Create: `app/Filament/Widgets/AttendanceOverview.php`
- Create: `app/Filament/Widgets/CorrectionQueue.php`
- Test: `tests/Feature/Admin/AttendanceManagementTest.php`
- Test: `tests/Feature/Admin/CorrectionManagementTest.php`
- Test: `tests/Feature/Admin/SettingsAndAuditTest.php`
- Test: `tests/Feature/Admin/DashboardWidgetsTest.php`

**Interfaces:**
- Consumes: correction review service, settings service, attendance models, audit policies.
- Produces: searchable/filterable attendance administration, correction review, read-only audit viewer, settings, and dashboard statistics.

- [ ] **Step 1: Write failing tests** for attendance filters, required correction reasons, sequence validation, approve/adjust/reject actions, read-only audits, timezone/correction-window validation, timezone audit history, and dashboard counts.
- [ ] **Step 2: Run focused tests** and verify missing resources/pages/widgets.
- [ ] **Step 3: Implement attendance resource** with employee/department/date/status filters and service-backed create/correct actions.
- [ ] **Step 4: Implement correction resource** with side-by-side original/proposal/final values and service-backed decisions.
- [ ] **Step 5: Implement settings and audit pages** with warning copy for timezone changes and no audit mutation actions.
- [ ] **Step 6: Implement basic dashboard widgets** using bounded aggregate queries.
- [ ] **Step 7: Run focused tests** and manually inspect admin workflows.
- [ ] **Step 8: Record a local checkpoint**; do not initialize or commit Git.

### Task 12: Reports, CSV, and Excel Exports

**Files:**
- Create: `app/Reports/AttendanceReportFilters.php`
- Create: `app/Reports/AttendanceReportQuery.php`
- Create: `app/Exports/AttendanceReportExport.php`
- Create: `app/Support/SpreadsheetTextSanitizer.php`
- Create: `app/Filament/Pages/AttendanceReports.php`
- Create: `resources/views/filament/pages/attendance-reports.blade.php` if required by the page implementation
- Test: `tests/Feature/Admin/AttendanceReportsTest.php`
- Test: `tests/Feature/Admin/AttendanceExportTest.php`
- Test: `tests/Unit/Support/SpreadsheetTextSanitizerTest.php`

**Interfaces:**
- Consumes: attendance data, policies, Filament, Laravel Excel.
- Produces: `AttendanceReportFilters::fromArray(array $input): self`; `AttendanceReportQuery::build(AttendanceReportFilters $filters): Builder`; `SpreadsheetTextSanitizer::literal(string $value): string`; authorized CSV/XLSX downloads.

- [ ] **Step 1: Write failing report tests** for daily/weekly/monthly/custom ranges, employee/department/status combinations, overnight work dates, pagination, bounded invalid ranges, and exact totals.
- [ ] **Step 2: Write failing export tests** asserting CSV/XLSX rows match on-screen filters and values beginning with `=`, `+`, `-`, or `@` are exported as literal text.
- [ ] **Step 3: Run focused tests** and verify missing report/export behavior.
- [ ] **Step 4: Implement immutable filters and reusable authorized query** so screen and exports cannot diverge.
- [ ] **Step 5: Implement CSV/XLSX downloads** with formula-injection protection, stable headings, timezone-local display, and safe filenames.
- [ ] **Step 6: Implement Filament report page** with validation, empty states, and export actions.
- [ ] **Step 7: Run focused tests** and manually open representative CSV/XLSX files.
- [ ] **Step 8: Record a local checkpoint**; do not initialize or commit Git.

### Task 13: Demonstration Data and Documentation

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `database/seeders/DemoOrganizationSeeder.php`
- Create: `database/seeders/DemoAttendanceSeeder.php`
- Modify: `.env.example`
- Replace: `README.md`
- Create: `docs/architecture.md`
- Create: `docs/domain-rules.md`
- Create: `docs/testing.md`
- Create: `docs/deployment.md`
- Create: `AGENTS.md`
- Test: `tests/Feature/Database/DemoSeederTest.php`
- Test: `tests/Feature/Documentation/EnvironmentExampleTest.php`

**Interfaces:**
- Consumes: all completed application contracts.
- Produces: deterministic, local-only demo setup and authoritative operating documentation.

- [ ] **Step 1: Write failing tests** asserting seeders create labeled administrator/employee/schedule/holiday/attendance/request data, temporary demo credentials require change, and `.env.example` contains no secrets.
- [ ] **Step 2: Run focused tests** and verify documentation/seeder gaps.
- [ ] **Step 3: Implement deterministic local demo seeders** with clearly labeled non-production accounts and representative attendance/correction states.
- [ ] **Step 4: Replace README** with Laragon, PHP/Composer/npm, MySQL database, `.env`, key generation, migration/seeding, storage link, frontend build, test, formatter, and `dtr:create-admin` instructions.
- [ ] **Step 5: Document architecture/domain/testing/deployment** including UTC/timezone behavior, queues/scheduler if used, HTTPS, backups, debug-off, secure cookies, logs, environment secrets, deployment build, and rollback considerations.
- [ ] **Step 6: Add concise `AGENTS.md` routing contributors to authoritative documents** without duplicating the full specification.
- [ ] **Step 7: Run focused tests and follow the documented clean Laragon setup** on a fresh local database.
- [ ] **Step 8: Record a local checkpoint**; do not initialize or commit Git.

### Task 14: Full Verification and Release Readiness

**Files:**
- Modify: only files required to fix validation findings
- Test: complete `tests/` suite

**Interfaces:**
- Consumes: the entire MVP.
- Produces: evidence that the implementation meets the approved specification.

- [ ] **Step 1: Run `vendor/bin/pint --test`**, fix formatting with `vendor/bin/pint`, and rerun until clean.
- [ ] **Step 2: Run `php artisan test`** and fix all failures without weakening assertions.
- [ ] **Step 3: Run `npm run build`** and fix all asset-build errors.
- [ ] **Step 4: Run a clean MySQL migration and demo seed** against a dedicated disposable database; verify rollback/re-run safety where migrations permit.
- [ ] **Step 5: Manually verify employee flows**: login, forced password change, clock-in, duplicate click, clock-out, history, card, correction request, profile, and logout.
- [ ] **Step 6: Manually verify administrator flows**: employee provisioning/reset, organization data, schedule assignment, attendance correction, request decision, reports/exports, settings, audit, and logout.
- [ ] **Step 7: Verify desktop, tablet, mobile, keyboard, reduced-motion, focus, contrast, empty, validation, and error states**; fix material findings.
- [ ] **Step 8: Run security/config checks** for debug-off production guidance, registration absence, authorization, CSRF, throttles, secrets, session cookies, and spreadsheet injection.
- [ ] **Step 9: Run the complete formatter, tests, frontend build, and clean-database smoke test again** and save exact command results for handoff.
- [ ] **Step 10: Produce the completion report** listing built features, exact validation results, demo placeholders, administrator setup, and any genuinely remaining limitations.
- [ ] **Step 11: Stop without Git initialization, commit, remote creation, or push** as requested.
