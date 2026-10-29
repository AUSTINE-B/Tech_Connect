# TechConnect software requirements specification

**Version:** 3.0 draft for implementation  
**Product:** TechConnect managed technical services  
**Operating model:** Company employed workforce, company pricebook, company collected payments  
**Initial market assumption:** Douala, Cameroon; exact zones and launch date require owner approval

## 1. Purpose

This SRS defines the first software release for TechConnect, a managed home and small-business technical-services company. Customers request repairs, TechConnect dispatches employees, technicians diagnose using an approved task catalog, customers review a fixed itemized estimate and pay the company, and customers confirm the result or open a rework/dispute case. The company pays employees through payroll independently of job timers.

This version resolves the former marketplace direction in the earlier marketing and policy materials. Independent-provider listings, technician-set prices, per-job technician wages, and cash collection by technicians are outside the product scope. The full business and product rationale is in [ASTRA_BUSINESS_DESIGN_FUNCTIONAL_PROPOSAL.md](ASTRA_BUSINESS_DESIGN_FUNCTIONAL_PROPOSAL.md).

## 2. Goals and launch boundaries

### Goals

- Make service availability, price rules, estimate contents, and customer remedies understandable before commitment.
- Route each request to an employee qualified for the trade and service zone.
- Preserve an auditable record of diagnosis, estimate, consent, payment, work, completion, and any refund or rework.
- Protect customer address, employee, payroll, and payment information by role and purpose.
- Support French and English, XAF whole-unit amounts, and low-bandwidth mobile use.
- Give operations reliable dispatch, exception, payment reconciliation, and service-quality views.

### Pilot assumptions

- Douala is the first-city hypothesis, based on the latest supplied SRS and screen concepts; it must be validated before hiring or public claims.
- Pilot starts in a few adjoining zones and two or three trades selected after demand, staff qualification, parts, and unit-cost validation.
- Candidate trades are plumbing, electrical repair, and appliance repair. Only approved tasks with safe diagnosis steps and costed prices are enabled.
- Dispatch may be supervisor-assisted. Availability is limited to staffed slots. No instant or 24/7 service promise.
- The first release is a responsive PWA and modular monolith using the MySQL-compatible server bundled with XAMPP (commonly MariaDB). Use the `techconnect` database, InnoDB, utf8mb4, and `database/mysql/techconnect.sql` for local development. The current app stores addresses as plaintext, so use synthetic data only until field encryption and production access controls are implemented.
- Payment processing is abstracted behind a provider adapter. Local development may use a clearly labeled fake adapter. Production charges require a contracted provider and verified server-side status.
- Payroll records and exports are supported; statutory calculations and declarations require a qualified Cameroon payroll adviser and approved process.

## 3. Users and permissions

| Role | Permissions |
|---|---|
| Customer | Register, create and view own requests, accept/decline estimates, initiate payment, confirm completion, request rework, open support issue, rate completed work |
| Technician | View active assignments, accept/decline, view address only while assigned, record approved diagnosis codes and quantities, submit work events/evidence and completion summary |
| Dispatcher | View dispatch queue, assign/reassign eligible employee, set ETA/reason, monitor workload |
| Supervisor | Resolve operational exceptions, safety escalations, customer cases, refund/rework decisions within assigned authority |
| Price manager | Create catalog/pricebook draft and cost/benchmark assumptions |
| Price approver | Approve a pricebook version created by another user |
| Payroll officer | Review approved attendance, payroll references, payslips and CNPS export records |
| Administrator | Manage user accounts, roles and configuration; sensitive actions remain audited |

Authorization is enforced by the backend on each request. UI visibility is not an authorization control. Sensitive HR/payroll information is unavailable to customer, technician, and general dispatch scopes.

## 4. Functional requirements

### Identity and access

- **FR-001:** Customer can register with name, Cameroon phone, password, preferred language, and terms/privacy consent.
- **FR-002:** Login sessions use random opaque tokens; only token hashes are stored. Staff accounts are created by an administrator and require least-privilege roles.
- **FR-003:** Authentication endpoints are rate-limited in production; admin/staff MFA is a production release gate.
- **FR-004:** User can view/update permitted profile fields and revoke own session.

### Catalog and requests

- **FR-010:** Customer can browse only published/effective service catalog items and zones with explicit fee, price, inclusion, and availability rules.
- **FR-011:** Request records customer, service category, zone, contact snapshot, address, issue description, preferred slot, consent version and time.
- **FR-012:** Address is visible only to currently assigned staff for an active request; accesses and revocation are auditable.
- **FR-013:** Duplicate submissions are prevented by idempotency key.

### Dispatch and diagnosis

- **FR-020:** Dispatch assignment requires active employee, applicable trade authorization, zone coverage, shift/availability, and workload eligibility.
- **FR-021:** Accept, decline, reassign, arrival, and ETA events record actor, time, and reason.
- **FR-022:** Technician diagnosis uses approved catalog codes and quantities; technician cannot enter a price or arbitrary billable task.
- **FR-023:** Unlisted/safety-critical work pauses normal estimating and routes to supervisor review.

### Estimate, payment, and work

- **FR-030:** Server calculates an itemized estimate from one effective, published pricebook version and snapshots labels, quantities, tax, fees, warranty, exclusions, and total.
- **FR-031:** Submitted estimates are immutable; changed scope creates a new estimate version.
- **FR-032:** Customer explicitly accepts or declines the exact estimate. Declined/expired estimates cannot authorize work.
- **FR-033:** Payment attempt is bound to one accepted estimate and amount. No card credentials, mobile-money PIN, or OTP are stored.
- **FR-034:** Only server-verified provider confirmation can authorize work. Duplicate callbacks are idempotent; redirects/client state are not proof.
- **FR-035:** Technician must explicitly start work after payment verification. Timer events measure service duration, not wages.
- **FR-036:** Pause/resume records reason, actor, time, customer notice, and escalation where applicable.

### Completion, customer care, and pricebook

- **FR-040:** Technician submits work summary, items/parts used, evidence, outcome, and end time; status then awaits customer test.
- **FR-041:** Customer can confirm completion, request included rework, or report a problem. Only customer confirmation closes the request.
- **FR-042:** Refund, rework, cancellation, and dispute decisions preserve evidence, actor, reason, amount, approval, and provider reference.
- **FR-043:** Rating is linked to confirmed job and moderated; one rating cannot automatically affect pay/employment.
- **FR-044:** Pricebook changes are versioned and require maker-checker approval, effective dates, source/cost basis, and audit event.

### Workforce and reporting

- **FR-050:** Admin manages employees, role, trade authorization, zones, shifts, training, and active status.
- **FR-051:** Payroll record/export uses approved attendance, salary, leave, overtime, and adjustments; job timers never feed salary calculation.
- **FR-052:** Operations reports dispatch response/arrival, jobs by state, first-time-fix, rework/refund, payment reconciliation, utilization, and customer outcomes.

## 5. Job state model

`REQUESTED → DISPATCHED → ACCEPTED → ARRIVED → DIAGNOSING → ESTIMATE_PENDING → AWAITING_PAYMENT → READY_TO_WORK → IN_PROGRESS ↔ PAUSED → CUSTOMER_TEST`

From `CUSTOMER_TEST`, customer action becomes `COMPLETED`, `REWORK_OPEN`, or `DISPUTE_OPEN`. Cancellation and refund are explicit paths with reason and policy outcome. Reassignment revokes former-assignee access. Illegal transitions are rejected and audited.

## 6. Data and integrity requirements

Relational database: MySQL 8+ or XAMPP MariaDB with InnoDB and utf8mb4; UUIDs are stored as `CHAR(36)`, XAF as whole-unit `BIGINT`, timestamps as UTC `DATETIME(6)`, and JSON is used for event payloads. Use versioned migrations, backup/restore drills, and indexes informed by pilot workloads. Core entities: app_user, role grant, customer, employee, trade authorization, zone, shift, service category, catalog item, pricebook version/item, service request, dispatch assignment, diagnosis finding, estimate version/line, payment attempt/webhook, refund, invoice, timer/session event, work evidence, completion decision, rework/support case, rating, attendance, payroll period, payslip reference, and audit event.

- Submitted estimates/invoices are immutable snapshots.
- Foreign keys and uniqueness constraints prevent orphaned records and repeated provider event IDs.
- Estimate line version, quantity, effective catalog item and calculated total are validated transactionally.
- Address and private media access is scoped and logged; media is outside SQL storage with signed short-lived links and EXIF stripping.
- Provider callbacks and create-request commands are idempotent.
- Audit history is append-only for dispatch, price, consent, payment/refund, work, customer outcome, role, and payroll changes.

The MySQL schema is initialized by the backend when the selected database exists. Schema evolution must use reviewed, numbered migrations; do not edit an already deployed schema in place. The starting migration does not yet include database roles, row-level security, encrypted address fields, immutable triggers, or a production migration runner. Production launch requires least-privilege MySQL users, application-level role scoping, managed encryption keys, immutable-record controls, and tested recovery.

## 7. API requirements

REST JSON under `/api/v1`; OpenAPI contract is maintained at [docs/openapi.yaml](docs/openapi.yaml). Money uses integer XAF; timestamps are UTC ISO-8601; errors include stable code, safe message, and request ID. Authenticated mutations require a bearer session; request creation and payment operations accept idempotency keys. Pagination is cursor or bounded offset. Never return passwords, token hashes, provider secrets, PIN/OTP, unrestricted HR fields, or other customers’ data.

Core resources: `/auth/register`, `/auth/login`, `/auth/logout`, `/me`, `/catalog`, `/zones`, `/requests`, `/dispatch/queue`, `/requests/{id}/assignments`, `/requests/{id}/diagnosis`, `/requests/{id}/estimates`, `/estimates/{id}/decision`, `/estimates/{id}/payments`, `/payments/{id}`, `/webhooks/{provider}`, `/requests/{id}/work-events`, `/requests/{id}/completion`, `/pricebook/versions`, and `/operations/metrics`.

## 8. Architecture and deployment

Responsive PWA/static frontend → PHP 8.0+ REST API using PDO MySQL → XAMPP MySQL-compatible service (MariaDB in many XAMPP distributions). Production requires a managed MySQL-compatible service, private object storage, worker queue, managed secrets, TLS termination, structured logs, monitoring, backup, and contracted payment adapter. The local backend requires PDO and pdo_mysql and is a functional foundation, not a hardened production deployment.

Deploy frontend and API behind HTTPS with secure cookies or bearer-token handling, CSRF protection where cookies are used, CSP/security headers, rate limits, input validation, secret rotation, least-privilege database role, malware-safe media processing, and incident-response procedures.

## 9. Non-functional acceptance targets

- p95 under 2.5 seconds for browse/request/estimate API at pilot load, excluding payment-provider latency.
- 99.5% monthly availability target for core service during controlled pilot.
- Usable on current mobile browsers and narrow screens with low bandwidth.
- French/English strings, XAF formatting, Cameroon phone validation, and local time display.
- Daily payment reconciliation; encrypted backup; verified restore before production.
- Accessibility: keyboard access, visible focus, semantic labels, sufficient contrast, and status not conveyed by color alone.
- Security/privacy review and locally validated retention and data-subject processes before launch.

## 10. Pilot acceptance gates

Release only when: a request can be created and tracked; only eligible staff can be assigned; address access is revoked after reassignment; technician cannot set arbitrary prices; estimate total is server-calculated and immutable; declined estimate cannot proceed; failed/pending/duplicate payment cannot authorize work; only customer confirmation closes a job; refund/rework retains history; payroll remains separate from timer; and reconciliation, support, safety escalation, backup and restore procedures have named owners.

## 11. Decisions still requiring client/business owner approval

Confirm the legal/product brand, first city/zones, launch trades, qualification rules, pricebook and fee rules, parts sourcing, cancellation/refund/rework/warranty terms, service hours/coverage, customer acceptance timeout, contracted payment provider and fees, employee contracts/pay bands/benefits, payroll/CNPS adviser, privacy retention/hosting, insurance, and numerical pilot economics gates.
