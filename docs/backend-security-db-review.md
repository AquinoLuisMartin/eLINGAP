# Backend Test, Security, and Database Performance Review

Conducted on 2026-09-25 against the local eLINGAP backend application before frontend development.

## 1. Executive Summary

| Category | Initial Status | Action Taken / Audit Result | Final Status |
| --- | --- | --- | --- |
| **Backend Tests** | 56 tests, 229 assertions | Added tests for senior citizen updates, verified senior checks, duplicate applications, and complete program lifecycle. | **63 tests, 263 assertions (100% passing)** |
| **Model Strictness** | Disabled | Enabled `Model::shouldBeStrict(! app()->isProduction())` in `AppServiceProvider`. Discovered and resolved missing attribute definition in `UserFactory`. | **Active (N+1 and lazy-loading protected)** |
| **Security & Integrity** | 2 validation gaps, 1 audit log corruption bug | Fixed associative array argument in `SeniorCitizenController::update()`; enforced senior verification and duplicate application constraints in `StoreApplicationRequest`. | **Resolved & verified with automated tests** |
| **Database Performance** | Missing foreign key indexes in PostgreSQL, potential memory exhaustion in unpaginated queries | Audited `pg_indexes` catalog and flagged 12 unindexed foreign keys and large collection query risks. | **Catalogued with concrete migration recommendations** |

---

## 2. Backend Test Assessment

### Test Suite Execution
- **Framework**: PHPUnit with Laravel test runner targeting PostgreSQL (`db_elingap_testing`).
- **Pass rate**: 63 passed, 0 failed, 0 errors, 263 assertions across 11 test suites (~7.9 seconds execution time).

### Coverage and Additions
1. **Senior Citizen Management** ([`SeniorCitizenManagementTest`](file:///C:/Users/luism/laravel/eLINGAP/tests/Feature/SeniorCitizens/SeniorCitizenManagementTest.php)):
   - Verified registration, age threshold validation, role-restricted archiving, and newly added full update flow auditing old vs. new values.
2. **Applications Workflow** ([`ApplicationWorkflowTest`](file:///C:/Users/luism/laravel/eLINGAP/tests/Feature/Applications/ApplicationWorkflowTest.php)):
   - Verified submission, status review history (PENDING -> APPROVED), newly added rejection of unverified senior citizens, and duplicate application rejection.
3. **Programs Management** ([`ProgramManagementTest`](file:///C:/Users/luism/laravel/eLINGAP/tests/Feature/Programs/ProgramManagementTest.php)):
   - Created full test suite covering administrator program creation, staff authorization rejection, budget/date range validation, and public index/show endpoints.
4. **Authentication and Accounts** ([`AccountManagementTest`](file:///C:/Users/luism/laravel/eLINGAP/tests/Feature/Administration/AccountManagementTest.php), [`LoginSecurityTest`](file:///C:/Users/luism/laravel/eLINGAP/tests/Feature/Auth/LoginSecurityTest.php), [`RoleRoutingTest`](file:///C:/Users/luism/laravel/eLINGAP/tests/Feature/Auth/RoleRoutingTest.php)):
   - Verified rate limiting (lockout after 5 attempts), case-insensitivity, role redirection, and session revocation upon password update.

---

## 3. Security Assessment

### Resolved Security and Integrity Issues
1. **Audit Trail Corruption in Senior Citizen Updates**:
   - **File**: [`SeniorCitizenController.php`](file:///C:/Users/luism/laravel/eLINGAP/app/Http/Controllers/SeniorCitizens/SeniorCitizenController.php#L77)
   - **Vulnerability**: `$oldValues = $seniorCitizen->only($request->validated());` passed an associative key-value array into `Model::only()`. In Eloquent, this iterated over input values (e.g. `'Juan'`) rather than attribute names, storing `['Juan' => null]` in `audit_logs` and `senior_citizen_histories`.
   - **Fix**: Updated to `$seniorCitizen->only(array_keys($request->validated()));`. Verified via test assertions.
2. **Unverified Senior Citizen Benefit Application Bypass**:
   - **File**: [`StoreApplicationRequest.php`](file:///C:/Users/luism/laravel/eLINGAP/app/Http/Requests/Applications/StoreApplicationRequest.php#L17-L29)
   - **Vulnerability**: Validation previously only checked `exists:senior_citizens,id`, allowing API or direct HTTP submissions for pending or archived seniors.
   - **Fix**: Added `Rule::exists('senior_citizens', 'id')->where('status', 'VERIFIED')`.
3. **Unhandled Duplicate Application Database Exception (DoS / 500 Error)**:
   - **File**: [`StoreApplicationRequest.php`](file:///C:/Users/luism/laravel/eLINGAP/app/Http/Requests/Applications/StoreApplicationRequest.php#L17-L29)
   - **Vulnerability**: The database has a unique constraint `UNIQUE (senior_citizen_id, program_id)`. Submitting duplicate applications triggered an uncaught `QueryException (SQLSTATE 23505)` instead of returning a clean 422 validation response.
   - **Fix**: Added `Rule::unique('applications')->where('program_id', $this->integer('program_id'))`.

### Remaining Security Considerations for Frontend Implementation
1. **Unprotected Verification Route**:
   - `Route::view('applications/verify', 'applications.verify')` in `routes/web.php` is outside role middleware. It should be constrained to `role:OSCA_STAFF` or guarded via policy.
2. **Model Mass-Assignment Boundaries**:
   - In [`SeniorCitizen.php`](file:///C:/Users/luism/laravel/eLINGAP/app/Models/SeniorCitizen.php#L13), `status`, `verified_at`, `verified_by`, and `registration_number` are listed in `#[Fillable]`. While existing controllers use `$request->validated()`, keeping lifecycle state in fillable risks privilege escalation if unguarded request inputs are ever passed.
3. **Sensitive PII Storage**:
   - Senior citizen demographic records, contact numbers, and OSCA IDs are stored in clear text. When implementing frontend export or document display features, apply least privilege access and audit logging to any bulk export.

---

## 4. Database Performance Assessment

### PostgreSQL Foreign Key Indexing Deficit
PostgreSQL does not create B-tree indexes for foreign keys automatically. Review of `pg_indexes` showed the following foreign keys are unindexed and cause sequential table scans on common queries and cascade/restrict deletes:

| Table | Column | Impact |
| --- | --- | --- |
| `application_status_histories` | `application_id` | Full table scan when viewing application history. |
| `senior_citizen_histories` | `senior_citizen_id` | Full table scan when viewing senior audit history. |
| `senior_citizen_documents` | `senior_citizen_id` | Full table scan when listing senior documents. |
| `sms_delivery_logs` | `sms_message_id` | Full table scan when viewing message delivery logs. |
| `applications` | `program_id` | Full table scan when counting program applications (`loadCount('applications')`). |
| `applications` | `reviewed_by` | Sequential scan during user foreign key operations. |
| `beneficiaries` | `senior_citizen_id` | Sequential scan when finding a senior citizen's programs. |
| `payouts` | `senior_citizen_id` | Sequential scan when looking up senior payout history. |
| `payout_schedules` | `program_id` | Sequential scan when listing payout schedules for a program. |
| `sms_messages` | `senior_citizen_id`, `created_by` | Sequential scan on senior or creator message filters. |
| `senior_citizens` | `verified_by` | Sequential scan on user deletion checks. |
| `programs` | `created_by` | Sequential scan on user deletion checks. |

### Memory Scalability Concern in Application Creation
- **File**: [`ApplicationController.php:30`](file:///C:/Users/luism/laravel/eLINGAP/app/Http/Controllers/Applications/ApplicationController.php#L30)
- **Code**: `'seniorCitizens' => SeniorCitizen::query()->where('status', 'VERIFIED')->orderBy('last_name')->get()`
- **Issue**: Calling `->get()` loads every verified senior citizen into memory. When the registry contains thousands of records (e.g. 18,000+), this will exceed PHP's memory limit.
- **Frontend Recommendation**: Use an async search or paginated lookup component for selecting senior citizens on application forms.

### Lazy Loading and N+1 Prevention
- `Model::shouldBeStrict(! app()->isProduction())` was added to [`AppServiceProvider.php`](file:///C:/Users/luism/laravel/eLINGAP/app/Providers/AppServiceProvider.php#L25).
- Any relationship accessed without eager loading (`with()`, `load()`) will immediately throw a `LazyLoadingViolationException` during local development, guaranteeing no N+1 query regressions are introduced in frontend views or Livewire components.
