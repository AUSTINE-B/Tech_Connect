# Implementation status

## Implemented in the local MVP

- Customer registration/sign-in with password hashing, opaque expiring sessions, and consent version/time capture.
- Role checks for customer, technician, dispatcher, supervisor, and administrator paths.
- Public example catalog and service zones.
- Customer request creation with idempotency key and explicit privacy consent.
- Trade/zone-scoped employee assignment; active assignment controls technician address access; access is logged; declined/reassigned/completed assignments lose access.
- Approved-code diagnosis, server-calculated estimate snapshots, explicit customer accept/decline.
- Pending local payment attempt and optional admin-only manual development confirmation. This does not contact a payment network or move money.
- Payment-gated work start, pause/resume/complete events, customer completion, and rework/dispute case creation.
- Responsive browser UI for customer, technician, dispatch/operations, and read-only example pricebook views.
- PHP 8.0+ API for XAMPP Apache, using PDO MySQL with prepared statements, and MySQL/MariaDB schema using InnoDB and utf8mb4.
- REST endpoint contract in `docs/openapi.yaml`.

## Not implemented; production or later release work

- Real payment provider onboarding, checkout, signed webhooks/status polling, refund execution, settlement reconciliation, and tax/receipt issuance.
- A reviewed production migration runner, least-privilege MySQL accounts, row/role scoping, immutable triggers, field encryption/KMS, private media storage, backup/restore procedures, and monitoring.
- Staff MFA, account recovery/email verification, rate limiting, notifications/SMS, evidence upload, customer support case console, cancellation/refund adjudication, review moderation, and data export/deletion workflows.
- Pricebook maker-checker editing and approval UI; current catalog is seeded and read-only.
- Shift/leave/availability model, automated capacity matching, arrival check-in, timer display/elapsed calculations, offline queue/sync, and notifications.
- Payroll calculation, statutory deductions, CNPS filing, payslips, insurance workflows, and validated employment policy.
- Production legal/privacy review, security assessment, privacy retention schedules, content/language review, and launch pricing approval.

The implementation is a runnable local foundation, not a complete production service. XAMPP calls its panel button “MySQL” but current distributions commonly include MariaDB; the schema uses the shared MySQL-compatible feature set. Use synthetic data only until storage, security, legal, payment, payroll, and operations controls are completed. No test suite was added or run as part of this delivery.
