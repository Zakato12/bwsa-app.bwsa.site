# Secure Web Architecture (Thesis Document)

**Project:** BWASA Thesis System  
**Date:** February 7, 2026  
**Scope:** Laravel web application with payments, residents, barangays, and user management.  
**Thesis Title:** RBAC‑Enabled Secure Web Architecture for Decentralized Utility Payment Systems with GCash Verification Workflow

## Abstract
This document formalizes a secure web architecture for the BWASA Thesis System. It captures the system's assets, actors, trust boundaries, threats, and security controls, then proposes a target-state architecture suitable for a thesis-level study. The goal is to guide both implementation and evaluation of security posture in a real-world Laravel deployment.

## System Overview
The application is a Laravel-based web system with roles (e.g., admin, official, treasurer, resident), login, dashboards, and payment handling (including receipt upload and OCR processing). The system stores user records, payment records, and scanned receipt images. Core components are:
- Web UI (Blade views)
- Laravel application layer (controllers, validation, session)
- Database (users, roles, barangays, residents, payments, gcash_payments)
- File storage for receipts
- OCR processing (Python script invoked by the app)

## Assets (What Must Be Protected)
- User credentials (password hashes)
- Session identifiers and role assignments
- Payment records and billing history
- Receipt images and OCR output
- System configuration and secrets (.env)
- Audit and activity logs

## Actors and Access Levels
- Anonymous user: can only access public login page
- Authenticated user: dashboard and role-specific capabilities
- Admin: full user management and oversight
- Official: barangay-level monitoring and resident assistance
- Treasurer: bill generation and payment verification/approval
- Resident: self-service payments and history

## Trust Boundaries
1. Internet client to reverse proxy/load balancer (public)
2. Reverse proxy to application server (private)
3. Application server to database (private)
4. Application server to file storage (private)
5. Application server to OCR process (local process boundary)

## Current State (Observed From Code)
- Authentication is performed manually via `AuthController` using direct DB queries and session variables.
- Middleware is used for session authentication and role gating.
- File uploads target a private receipt disk by default (`RECEIPT_DISK=private`), with runtime fallback to available disks if misconfigured.
- OCR is executed via a job that can run in `queue` or `sync` mode (`OCR_PROCESSING_MODE`).

Key files reviewed:
- `routes/web.php`
- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/PaymentController.php`
- `app/Http/Kernel.php`
- `config/auth.php`

## Threat Model (STRIDE Summary)
- **Spoofing:** session fixation or missing auth middleware could allow unauthorized access.
- **Tampering:** direct DB writes in controllers risk improper authorization checks.
- **Repudiation:** limited audit logging means weak non-repudiation.
- **Information Disclosure:** receipt images in public disk risk exposure.
- **Denial of Service:** OCR execution is resource intensive and unthrottled.
- **Elevation of Privilege:** role checks are inconsistent and enforced in code paths rather than centralized policies.

## Target-State Secure Architecture
The following architecture improves security while remaining achievable for a thesis-scale project.

**Architecture Diagram (Logical)**
```
Client Browser
   |
   | HTTPS + HSTS
   v
Reverse Proxy / WAF
   |
   v
Laravel App Server
   |-- AuthN/AuthZ (middleware + RBAC)
   |-- Validation + CSRF + Rate Limits
   |-- Upload Service (private storage)
   |-- Background Queue (OCR jobs)
   |
   +--> Database (MySQL/PostgreSQL)
   +--> Object Storage (private bucket)
   +--> Audit Log Store
```

## Decentralization Option (Thesis Extension)
This section outlines a decentralized variant that can be used as a comparative study. It is not implemented in the current codebase, but can be discussed and evaluated as an alternative architecture.

**Decentralized Concept**
- Each barangay operates its own application instance and database.
- A shared identity layer enables cross‑barangay authentication.
- A consortium audit ledger (append‑only) records payment and approval events.

**Decentralized Logical Diagram**
```
Clients
  |
  v
Barangay A App <--> Barangay A DB
Barangay B App <--> Barangay B DB
Barangay C App <--> Barangay C DB
        \           |            /
         \          |           /
          +---- Shared Identity ----+
          +---- Consortium Audit ---+
```

**Security Impact**
- Reduces single‑point failure by distributing data and control.
- Increases coordination overhead and consistency challenges.
- Requires strong identity federation and audit integrity controls.

**Use in Thesis**
- Compare centralized vs. decentralized security properties.
- Evaluate tradeoffs in availability, governance, and auditability.

## Centralized vs. Decentralized Comparison
| Dimension | Centralized (Current) | Decentralized (Extension) |
|---|---|---|
| Control | Single application owner | Multi‑barangay shared governance |
| Availability | Single point of failure | Higher resilience, more nodes |
| Data Isolation | All data in one DB | Per‑barangay data separation |
| Identity | Local auth/session | Federated identity layer |
| Auditability | App logs + DB | Consortium append‑only audit ledger |
| Consistency | Strong consistency | Eventual consistency across nodes |
| Operational Complexity | Lower | Higher (coordination + sync) |
| Security Surface | Smaller, centralized | Larger, multi‑node |

## Decentralized Design Applied (Option A: Barangay‑Scoped Governance)
This project adopts a decentralized governance model **within a single deployment** by enforcing **barangay‑level sovereignty** and **append‑only auditability**. While infrastructure is centralized, **authority and data access are decentralized by barangay**, which aligns with the thesis title and scope.

**Decentralized Principles (Option A)**
- **Tenant isolation by barangay**: officials and treasurers can only view or act on records from their barangay.
- **Local autonomy**: residents only access their own records.
- **Append‑only audit ledger**: key security-sensitive actions emit immutable log entries (simulated by append‑only file logs).

**Implemented Controls (Code‑Level)**
- Barangay scoping for:
  - Payment visibility, verification, approval, bill creation, and walk‑in payments
  - Resident listing and management
- File‑based append‑only audit ledger for payment events and walk‑in submissions

**Data Flow (Hybrid)**
1. Resident submits payment (scoped to own record)
2. OCR runs asynchronously and stores results
3. Official verifies only if the payer belongs to the same barangay
4. Treasurer approves only within the same barangay
5. All actions are appended to an audit ledger

**Limitations**
- No true distributed consensus (single server still exists)
- Audit ledger uses hash chaining (tamper‑evident) but is not externally notarized
- Full decentralization would require multi‑node hosting and federation

## Decentralized RBAC Model (Thesis + Implementation)
This project adopts **decentralized RBAC** by scoping roles to barangay boundaries and enforcing permissions locally. Administrators retain global oversight, while officials and treasurers exercise authority only within their barangay. This mirrors decentralized governance without requiring a full distributed system.

**RBAC Roles and Scope**
- **Global roles**
  - `admin`: system‑wide oversight and cross‑barangay authority
- **Barangay‑scoped roles**
  - `official`: resident account assistance and barangay‑level monitoring
  - `treasurer`: bill generation, payment verification and approval within their barangay
  - `resident`: access only their own records and payments

**Decentralized Enforcement Rules**
1. **Role check**: the user must hold an allowed role.
2. **Scope check**: if the role is barangay‑scoped, the target record must belong to the same barangay.
3. **Audit**: sensitive actions emit append‑only ledger entries for accountability.

**RBAC Enforcement (Middleware + Scope Checks)**
- Route middleware enforces authenticated session and role gating.
- Controller logic enforces barangay scoping for role‑bound actions.
 - A federated access policy module centralizes role and scope checks in one code layer without changing governance boundaries.

**Governance Implication**
- Barangays operate as semi‑autonomous domains with localized authority.
- Cross‑barangay actions require global admin privilege, aligning with decentralized decision boundaries.

## RBAC Role Policy (Detailed)
**Admin**
- System‑wide access across all barangays
- Manages user accounts (create, update, deactivate)
- Assigns roles and barangay ownership to users
- Views consolidated reports across all barangays
- Configures system‑wide settings and security policies
- Monitors audit logs and system activities
- **Cannot modify payment verification outcomes**

**Official**
- Access limited to assigned barangay
- Views resident accounts and billing records within barangay
- Assists resident account management (non‑privileged actions)
- Monitors water consumption and billing summaries
- **Cannot verify payments or access system‑wide settings**
- **Cannot access other barangays’ data**

**Treasurer**
- Access limited to assigned barangay
- Generates and manages water bills for residents
- Reviews and verifies GCash payment submissions
- Approves or rejects payments based on proof verification
- Updates billing and payment statuses
- Generates barangay‑level financial reports
- **Cannot manage user roles or access other barangays**

**Resident**
- Access strictly limited to own account and assigned barangay
- Views personal profile and account information
- Views current and historical water bills
- Submits water bill payments via GCash
- Uploads proof of payment and GCash reference number
- Tracks payment status (pending / approved / rejected)
- Views payment confirmations and receipts
- Can change own password
- **Cannot view other residents’ data**
- **Cannot generate bills, verify payments, or access administrative settings**
- **Cannot access data from other barangays**

## Security Constraints (Enforced)
- All access is enforced using middleware and session‑based authorization.
- Each request is validated against both user role and barangay scope.
- Unauthorized access attempts are denied by role middleware (`403`) or redirected for session/scope failures.

**Middleware Used**
- `session.auth`: blocks unauthenticated access (redirect to login)
- `role`: role‑based route gate (403)

## Dashboard and Workflow Alignment
- **Admin dashboard**: system‑wide statistics + recent activity log; no direct resident list access.
- **Official dashboard**: residents CRUD and payments list (view only).
- **Treasurer dashboard**: payments list, bill generation, walk‑in payments, GCash verification.
- **Resident dashboard**: unpaid bills count, GCash payment creation, payment history.

## Federated Access Policy Module
To keep decentralized governance while avoiding scattered authorization checks, the project uses a **federated access policy module**. This module centralizes **how** access is enforced, not **who** has authority.

**Purpose**
- Consistent enforcement of role checks and barangay scoping
- Reduced risk of missing checks on new routes
- Clear auditability of enforcement logic for thesis evaluation

**Key Functions (Examples)**
- `isAuthenticated()`
- `hasRole([...])`
- `currentUserBarangayId()`
- `userInSameBarangay($targetUserId)`

**Short Sequence (Access Enforcement)**
```
Request -> Route Middleware (session.auth, role)
       -> Controller -> Access (role + barangay scope)
       -> Allowed? -> Proceed to DB -> Response
       -> Denied?  -> 403 / redirect
```

## Security Controls
### Authentication
- Authentication is implemented through manual session management in `AuthController` (with password hashing and session hardening).
- Enforce session authentication via middleware for protected routes.
- Rotate session ID after login and password change.
- Add login throttling (rate limit) at controller entry points.

### Gap Closure Status (Implemented)
**Gap #1: Session and Authentication Hardening**
- Session ID regeneration on login and password change.
- CSRF token regeneration on login/logout transitions.
- Session fingerprint binding:
  - User-Agent hash is stored at login and revalidated on each protected request.
  - Optional IP binding (`SESSION_BIND_IP`) invalidates session on IP mismatch.
- Secure session-cookie controls are environment-driven:
  - `SESSION_SECURE_COOKIE`
  - `SESSION_SAME_SITE`
  - `SESSION_EXPIRE_ON_CLOSE`
  - `SESSION_BIND_IP`

**Gap #2: Security Headers Hardening**
- A dedicated middleware sets security headers for all web responses:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy`
  - `Permissions-Policy`
  - `Content-Security-Policy` (configurable baseline policy)
  - `Strict-Transport-Security` (only for HTTPS requests)
- Header behavior is configurable through environment variables:
  - `SECURITY_HEADERS_ENABLED`
  - `CSP_POLICY`
  - `REFERRER_POLICY`
  - `PERMISSIONS_POLICY`

**Gap #3: File Upload and Content Validation Hardening**
- Resident GCash receipt upload is restricted to image files only:
  - Allowed extensions: `jpg`, `jpeg`, `png`
  - Allowed MIME types: `image/jpeg`, `image/png`
  - Max file size: 2 MB
- Server-side file-signature validation is enforced before storage:
  - Detected MIME check (`mime_content_type`)
  - Image magic-byte check (`exif_imagetype`)
  - Basic dimension sanity check (`getimagesize`) to reject malformed payloads
- Invalid uploads are rejected with validation errors before OCR processing.

**Gap #5: Secrets and Environment Hygiene (Production Guardrails)**
- Added runtime baseline enforcement middleware for `web` and `api` requests.
- In production mode, the baseline checks:
  - `APP_DEBUG` must be `false`
  - `APP_KEY` must be present/valid
  - `SESSION_SECURE_COOKIE` should be `true`
- On baseline failure:
  - A critical security log is emitted (`security.baseline_failed`)
  - Requests can be blocked (`503`) when `SECURITY_BASELINE_BLOCK_ON_FAIL=true`
- Config/Env controls:
  - `SECURITY_BASELINE_ENABLED`
  - `SECURITY_BASELINE_BLOCK_ON_FAIL`

**Gap #6: Monitoring and Incident Readiness**
- Added centralized security event logging via a dedicated monitor helper.
- Security-relevant events are emitted to a separate `security` log channel:
  - Login failures and throttling
  - Role authorization denials (403 paths)
  - Session fingerprint mismatch (IP/User-Agent drift)
  - Barangay scope and user-scope denials
- Event payload includes incident-ready context:
  - `event`, `user_id`, `role`, `ip`, `route`, plus action-specific fields
- Added threshold-based alerting (critical logs) for anomaly spikes:
  - Repeated login failures from same IP within time window
  - Repeated role denials from same IP within time window
- Log retention controls (environment-driven):
  - `SECURITY_LOG_LEVEL`
  - `SECURITY_LOG_DAYS`

**Gap #7: Transport and Deployment Hardening**
- Added transport-enforcement middleware:
  - HTTP requests are redirected to HTTPS (`301`) when `APP_FORCE_HTTPS=true`
  - Enforcement is skipped for local/testing environments
- Added proxy-trust hardening for hosted deployments:
  - `TRUSTED_PROXIES` for explicit proxy IP allow-list
  - `TRUSTED_PROXY_HEADERS` (`ALL`, `AWS_ELB`, `FORWARDED`) for correct scheme/host detection
- Existing HSTS support remains active for secure requests via security-headers middleware.
- Deployment controls formalized:
  - Use least-privileged database account (no schema/admin rights for app runtime user)
  - Implement encrypted automated backups and periodic restore tests
  - Maintain patch cadence for PHP, Laravel, OpenSSL, and host OS packages
  - Keep production `APP_DEBUG=false` and rotate secrets on compromise events

**Production Environment Baseline (.env)**
```env
APP_ENV=production
APP_DEBUG=false

SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_EXPIRE_ON_CLOSE=true
SESSION_BIND_IP=false

SECURITY_HEADERS_ENABLED=true
REFERRER_POLICY=strict-origin-when-cross-origin
PERMISSIONS_POLICY="camera=(), microphone=(), geolocation=()"
CSP_POLICY="default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
SECURITY_BASELINE_ENABLED=true
SECURITY_BASELINE_BLOCK_ON_FAIL=true
SECURITY_LOG_LEVEL=warning
SECURITY_LOG_DAYS=30
APP_FORCE_HTTPS=true
TRUSTED_PROXIES=127.0.0.1
TRUSTED_PROXY_HEADERS=ALL
```

**Apply Configuration After Update**
```bash
php artisan optimize:clear
php artisan config:cache
```

### Authorization
- Enforce role gating via route middleware.
- Enforce barangay scope via controller‑level checks.

**Role‑Specific Workflows**
- Admin: system‑wide stats + recent activity log; no direct resident list access.
- Official: resident CRUD + payments list (view only).
- Treasurer: payments list + generate bills + walk‑in workflow + GCash verification.
- Resident: unpaid bills count + GCash payment creation + payment history.

### Input Validation and Output Encoding
- Server-side validation for all inputs (already in several controllers).
- Escape all untrusted output in Blade templates.
- Strict file validation (size, MIME, type checking).

### File Uploads and OCR
- Store receipts on the configured receipt disk (default private storage; fallback may occur if disk configuration is invalid).
- Serve files via signed URLs or controller authorization checks.
- Process OCR through the job pipeline using configurable execution mode (`queue` or `sync`).
- Sanitize OCR inputs and limit file size and formats.
- OCR runtime is externalized and configurable via environment:
  - `OCR_PYTHON_BINARY`, `OCR_PYTHON_CANDIDATES`
  - `TESSERACT_CMD`
  - `OCR_PROCESSING_MODE` (`sync` or `queue`)
- OCR outputs are persisted in `gcash_payments`:
  - `ocr_text`, `extracted_amount`, `extracted_reference`, `confidence_score`
- Treasurer review now includes OCR visibility in the payments table:
  - OCR status badge (`Pending`, `Failed`, `Ready`)
  - Extracted amount and extracted reference preview
- OCR can be manually retriggered by treasurer:
  - `POST /payments/{id}/ocr/reprocess`

### Transport Security
- Enforce TLS 1.2+ and HSTS.
- Redirect HTTP to HTTPS at the proxy.
- HSTS is emitted by application middleware when the request is HTTPS.

### Secrets and Configuration
- Keep `.env` out of version control.
- Use app key rotation policy.
- Use per-environment secrets store for production.

### Logging and Monitoring
- Log authentication events, payment changes, and admin actions.
- Add immutable audit trails for billing and approval operations.
- Alerts for repeated login failures and unusual payment activity.

### Database Security
- Use least-privileged database user in production.
- Encrypt backups at rest.
- Use migrations to define schema and avoid drift.

## Secure Routing Strategy (Example With Middleware)
```
Route::middleware(['session.auth'])->group(function () {
    Route::get('/dashboard', ...);
    Route::resource('barangays', BarangayController::class)->middleware('role:admin');
    Route::resource('residents', ResidentController::class)->middleware('role:official');
    Route::get('/payments', ...); // admin, official, treasurer, resident
    Route::post('/payments/{id}/verify', ...)->middleware('role:treasurer');
    Route::post('/payments/{id}/approve', ...)->middleware('role:treasurer');
    Route::get('/payments/walkin/create', ...)->middleware('role:treasurer');
});
```

## Data Flow (Payments)
1. Resident uploads receipt -> validated -> stored on configured receipt disk (private by default)
2. OCR job dispatched -> executed in queue or sync mode -> results stored in DB
3. Treasurer reviews OCR status, extracted amount, and extracted reference
4. Treasurer may trigger OCR reprocess for failed/pending extraction
5. Treasurer verifies only after OCR integrity checks pass:
   - OCR is not pending/failed
   - Extracted amount matches submitted payment amount
6. Treasurer approves payment -> status updated -> audit log entry
   - Note: current implementation does not strictly require prior verified status before approval
7. Treasurer records walk-in payments -> status approved -> audit log entry

## Risks and Mitigations (Selected)
- **Public receipt exposure:** store privately, serve via signed URLs.
- **Role bypass:** enforce middleware and policies.
- **OCR abuse:** queue + rate limits + file size caps.
- **OCR false positives / bad extraction:** enforce treasurer-side verification gates and allow OCR reprocess before verification.
- **Session compromise:** secure cookies + SameSite + regenerate ID.

## Evaluation Plan (Thesis)
- Pen-test style validation of access control and file exposure.
- Static analysis review of routes and controllers.
- Logging and audit completeness check.
- Performance impact of OCR queue versus synchronous processing.

## Implementation Roadmap (Phased)
1. **Phase 1:** Enforce auth and role checks via middleware; add barangay scoping.
2. **Phase 2:** Move receipt storage to private disk; signed access.
3. **Phase 3:** Queue OCR tasks and add rate limiting.
4. **Phase 4:** Expand audit logging (payment + walk‑in workflows).

## Limitations
- External integrations (e.g., payment gateways) are not modeled in detail.
- Full compliance standards (e.g., PCI DSS) are out of scope.

## Appendix: Controller-Level Scope Checks
This project uses middleware for role gating and controller‑level checks for barangay scope.

**Example Scope Check**
```
requireRoleInBarangay(roles, targetUserId = null, targetBarangayId = null)
```

## Appendix: Controller-to-Role Mapping (Implemented)
- `PageController::main` requires authenticated session
- `PageController::showAddUser` allows `admin`, `official`
- `UserController::listUsers` allows `admin`
- `BarangayController::*` allows `admin`
- `ResidentController::*` allows `official`
- `PaymentController::create` allows `resident`
- `PaymentController::store` allows `resident`
- `PaymentController::index` allows `admin`, `official`, `treasurer`, `resident`
- `PaymentController::verify` allows `treasurer`
- `PaymentController::reprocessOcr` allows `treasurer`
- `PaymentController::approve` allows `treasurer`
- `PaymentController::createBill` allows `treasurer`
- `PaymentController::storeBill` allows `treasurer`
- `PaymentController::createWalkIn` allows `treasurer`
- `PaymentController::storeWalkIn` allows `treasurer`
- `PaymentController::receipt` allows `admin`, `official`, or payment owner

## Appendix: Suggested Files to Update
- `routes/web.php` for protected route mapping
- `app/Http/Controllers/AuthController.php` for session rotation and login throttling
- `app/Http/Controllers/PaymentController.php` for private storage and queue
- `config/filesystems.php` for private disk
- `app/Jobs/ProcessReceiptOcr.php` for OCR async processing
- `resources/views/payments/index.blade.php` for OCR status visibility and reprocess action
- `app/Support/Access.php` (or similar) for shared role checks
