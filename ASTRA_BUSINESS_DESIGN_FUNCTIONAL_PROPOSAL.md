# ASTRA business, design, and functional proposal

## Recommendation

Build **TechConnect** as a managed home and small-business technical-services company. Customers book through the app; TechConnect employs, equips, schedules, and supervises technicians; the company publishes service prices, collects payment, handles complaints and rework, and pays staff through payroll. The app is the operating system for that service, not a public directory of independent tradespeople.

This is the best fit for the latest SRS: one accountable provider, standardized prices, auditable work, and a consistent customer remedy. It requires more working capital and operational discipline than a marketplace, so the launch must stay small. Use **TechConnect** as the product name if that is the approved brand; keep ASTRA only if the owner confirms it is the company name. The concept images show the managed-service journey and should be aligned to a single brand and location. :codex-file-citation{path="C:\Users\austi\Downloads\ASTRA\ASTRA-SRS.pdf" purpose="source"} :codex-file-citation{path="C:\Users\austi\Downloads\ASTRA\customer_service_discovery.png" purpose="source"} :codex-file-citation{path="C:\Users\austi\Downloads\ASTRA\customer_estimate_review.png" purpose="source"}

## 1. Business model

### Customer promise

“A qualified TechConnect technician, a clear itemized price before repair work, and one accountable team if something goes wrong.”

Promise scheduled service in staffed zones. Do not promise instant dispatch, 24/7 response, guaranteed repair, or universal coverage until the operation can prove it. Publish the service boundary, expected arrival window, diagnostic/call-out fee, what is included, and how extra work, cancellation, refunds, and rework are handled.

### Operating and revenue model

TechConnect sells completed repair and maintenance services at centrally approved prices. Customer payments go to the company through its contracted payment provider. Employees receive regular wages and approved benefits; job timers measure service performance and never calculate salary. The company retains the difference between collected service revenue and direct and fixed operating costs.

Price each catalog item from observed labor time, parts, transport, payment fees, taxes, warranty/rework allowance, and required contribution margin. Show a diagnostic fee separately when it applies. Keep parts inclusion explicit. An estimate is a frozen price snapshot; any change in scope requires a new version and fresh customer approval before the extra work begins. Do not represent a company pricebook as a government tariff. The older household price list itself labels its ranges as planning estimates, so validate every proposed public rate locally before launch. :codex-file-citation{path="C:\Users\austi\Downloads\ASTRA\TechConnect_Household_Problems_and_Repair_Prices.docx" purpose="source"}

### Launch market and offer

Treat **Douala as the pilot-city hypothesis**, because it is the location used in the latest product specification and screen concepts. This is not a claim that Douala has been validated as the best market. Confirm it with customer interviews, technician recruitment, travel-time mapping, and costed jobs before hiring. Start in a small number of adjoining zones and only open slots that staffed teams can cover.

Start with two or three technical trades, provisionally plumbing, electrical repair, and appliance repair. Include only catalog tasks that have a safe diagnosis checklist, qualified staff, dependable parts sourcing, a clear price rule, and a costed warranty/rework policy. Defer cleaning, roofing, general carpentry, and other broad categories until demand and delivery capacity are proven.

### Unit economics and scale gates

Track contribution per completed job:

`Net service revenue – parts – payment fees – direct travel/dispatch – allocated job labor – warranty/rework reserve`

Also track utilization (productive paid time / paid available time), first-time fix, arrival reliability, repeat customers, refunds/rework, acquisition cost per completed customer, payroll coverage, settlement variance, safety incidents, and cash runway. Agree numerical gates before pilot launch. Add staff, trades, zones, or paid marketing only after the gates hold across multiple review periods. Avoid revenue targets based on registrations or requests alone.

Maintenance plans and business accounts for landlords, property managers, student residences, and small offices are second-stage offers. Test scheduled service contracts only after ordinary jobs show reliable response, cost, and quality. Keep each contract’s response window, included work, parts, and exclusions explicit.

## 2. Product and design direction

### Experience principles

- **Mobile-first and low-bandwidth:** responsive progressive web app, compact pages, resumable requests, compressed media, and SMS/phone fallback for key updates.
- **Clear before commitment:** show service coverage, fee rules, expected arrival, estimate validity, parts inclusion, tax/fees, cancellation rules, and warranty before payment.
- **One service journey:** customer request → assignment → diagnosis → itemized estimate → customer approval → verified payment → repair → customer test → completion or case.
- **Role separation:** customers see their service records; assigned technicians see only the information needed for active work; dispatch sees operational details; payroll and HR have restricted access.
- **Bilingual and locally usable:** French and English, XAF, Cameroon phone formats, clear local dates/time, readable contrast, keyboard/screen-reader labels, and no meaning conveyed by color alone.
- **Truthful trust cues:** identify staff as employees only after employment and qualification checks are complete. Ratings, guarantees, response times, and verification badges must reflect real operating rules.

### Core screens

1. **Service discovery:** service categories available in the customer’s zone, price/fee rules, coverage, arrival windows, and emergency limitations.
2. **Request form:** issue, contact, address, preferred slot, optional photos, consent and privacy notice. Do not request more personal information than dispatch needs.
3. **Request tracker:** assigned staff/ETA, current stage, messages/support, estimate, payment status, receipt, and past work.
4. **Estimate review:** diagnosis summary, catalog lines, quantity, labor/parts, fee, tax/mandatory charges, total, validity, warranty, exclusions, and accept/decline controls.
5. **Payment status and receipt:** provider-hosted checkout, pending state that prevents duplicate submission, server-confirmed status, and itemized receipt.
6. **Technician workspace:** assigned jobs, route/contact, limited address access, checklist diagnosis, approved items, work timer, pause/safety stop, evidence, and completion summary. No manual price entry.
7. **Dispatch and operations console:** eligibility, workload, assignment queue, late jobs, exceptions, payment/reconciliation issues, safety events, rework, and complaint work queue.
8. **Pricebook and workforce admin:** versioned catalog with different-person approval; employee authorization, shifts, zones, training, payroll approvals/exports, and audit history. Hide HR/payroll details from general operations roles.

Maintain one design system across these screens. Replace placeholder dates, prices, ratings, and staff claims before release. The concept set includes unrelated payment/dashboard imagery and duplicate diagrams; keep a selected, licensed asset set with a source/use manifest. :codex-file-citation{path="C:\Users\austi\Downloads\ASTRA\payment.jpg" purpose="source"} :codex-file-citation{path="C:\Users\austi\Downloads\ASTRA\Livechard.jpg" purpose="source"} :codex-file-citation{path="C:\Users\austi\Downloads\ASTRA\mermaid-diagram.png" purpose="source"}

## 3. Functional model

### Actors and permissions

| Actor | Main responsibilities | Must not be able to |
|---|---|---|
| Customer | Create requests, review estimates, pay, track service, test work, request rework, report issues, rate completed service | View another customer’s requests or staff records |
| Technician/employee | Accept assigned work, inspect, record approved findings/quantities, perform authorized work, pause for safety/scope reasons, submit completion evidence | Set prices, collect customer cash in the app workflow, view payroll records, access unassigned customer addresses |
| Dispatcher | Match requests to staff authorized for trade/zone/shift, reassign, update ETA and reasons | Change pricebook or approve their own sensitive financial exception |
| Supervisor | Review exceptions, unlisted faults, safety issues, rework and complaints; authorize operational resolutions | Alter immutable accepted estimates or erase case/audit history |
| Price manager and approver | Create/review pricebook versions and effective dates | Approve a version they created (maker-checker separation) |
| Payroll officer | Maintain attendance approvals, pay-period records, payslips and CNPS exports | Use service timer duration as wage calculation or expose payroll data to general staff |
| System administrator | Manage accounts, access grants, configuration and system operations | Bypass audit logging or use broad access without a logged reason |

### Job state model

`REQUESTED → DISPATCHED → ACCEPTED → ARRIVED → DIAGNOSING → ESTIMATE_PENDING → AWAITING_PAYMENT → READY_TO_WORK → IN_PROGRESS ↔ PAUSED → CUSTOMER_TEST`

From `CUSTOMER_TEST`, the customer either confirms `COMPLETED`, requests `REWORK`, or opens `DISPUTE`. Cancellation and refund are explicit case/state paths, not silent deletion. Reassignment appends a new assignment and immediately revokes the former technician’s address access. Invalid transitions are rejected by the server and recorded with actor, time, and reason.

### End-to-end behavior

1. **Browse and request:** API returns only services and time slots available in the customer’s zone. The app presents fee rules before request submission and records consent version and timestamp.
2. **Dispatch:** staff eligibility is checked against current trade authorization, zone, shift, and workload. A supervisor can manually assign during the pilot. Every offer, accept/decline, reassignment, ETA, and reason is retained.
3. **Diagnose:** assigned technician records approved fault/task codes, quantity, notes, and evidence. Unknown work pauses the quote flow and routes to a supervisor/catalog exception path; it cannot be billed as free text.
4. **Estimate and consent:** backend selects one effective approved pricebook version, calculates totals, and freezes line snapshots. Customer explicitly accepts or declines. A changed estimate requires new consent.
5. **Payment:** backend initiates provider-hosted payment. It verifies a signed callback or queries the provider’s status endpoint; redirects and client-side success messages are not proof. Duplicate events are idempotent. Work authorization occurs once, only after verified payment for the accepted estimate.
6. **Work:** technician starts the work timer after authorization. Pauses record a reason, actor, time, customer notice, and any escalation. Timer data measures service performance and is isolated from payroll calculations.
7. **Completion and recourse:** technician records work summary, used items/parts, evidence, and result. The customer tests and confirms, requests included rework, or reports a problem. Only customer confirmation closes the job. Refund, rework, and complaint decisions preserve evidence and approvals.
8. **Payroll and reporting:** payroll uses approved salary, attendance, leave, overtime, and adjustments, with a payroll officer reviewing outputs and filings. During MVP, prefer an approved payroll process/tool with controlled export over inventing unverified statutory calculations in the app.

### Backend boundary

Use a modular monolith with a versioned REST API/OpenAPI contract, MySQL-compatible InnoDB database through XAMPP MariaDB during development, private object storage, a background worker for notifications and provider callbacks, and role-based staff consoles. Keep modules for identity/access, catalog/pricebook, requests/dispatch, diagnosis/estimates, payments/refunds, work/rework, and payroll exports. Use append-only audit/session events, idempotency keys, server-side total calculation, request correlation IDs, and daily payment reconciliation. The current local schema is at `database/mysql/techconnect.sql`; review and expand it before production. :codex-file-citation{path="C:\Users\austi\Downloads\ASTRA\ASTRA-SRS.pdf" purpose="source"}

## 4. MVP boundary and delivery sequence

### MVP must include

- Customer service discovery, request creation, status tracking, estimate decision, provider payment, receipt, completion choice, and support contact.
- Employee accounts, trade/zone authorization, shift/availability, assignment acceptance, restricted job access, checklist diagnosis, work events, and evidence upload.
- Supervisor dispatch and exception queues; pricebook versioning with maker-checker approval; audit trail.
- Verified payment flow, duplicate callback protection, refunds/rework case handling, backup/restore, and reconciliation process.
- Basic attendance, approved adjustments, payslip/export handling, and payroll review controls. Any statutory calculation or filing format must be validated by a qualified local payroll adviser before use.

### Defer until the service is proven

Automated dispatch optimization, broad multi-city coverage, all trades, public provider marketplace, in-app chat beyond essential job communication, loyalty subscriptions, predictive pricing/diagnosis, automated payroll law calculations, deep analytics, and customer-facing claims of 24/7 or instant response.

### Delivery order

1. Confirm business name, operating model, pilot city/zones, trades, employment approach, and customer policies.
2. Interview customers, employees, parts suppliers, and potential demand partners; cost real jobs and validate the first catalog.
3. Approve design system, state machine, roles, data retention, API contract, and database migrations.
4. Build request → dispatch → diagnosis → estimate → approval in staging, including failure/offline cases.
5. Integrate one contracted payment path, test pending/failed/duplicate/reversed payments, then add fallback options based on demand.
6. Run a supervised pilot; review service and financial gates weekly; expand only after repeated success.

## 5. Partner strategy

- **Payments:** evaluate direct Orange Money and MTN MoMo onboarding alongside one aggregator. Compare fees, merchant eligibility, XAF settlement, callbacks/status verification, refunds/reversals, reconciliation files, support, sandbox quality, and contract terms. Product pages show potential integrations but do not replace due diligence or a signed merchant agreement. [Orange Cameroun web payment](https://www.orange.cm/fr/om-partenaires/partenaire-webpayment.html), [MTN developer platform](https://developers.mtn.com/getting-started), [CamPay](https://www.campay.net/en/), [KoraPay mobile-money docs](https://developers.korapay.com/docs/mobile-money)
- **Technician supply:** vocational centers and trade associations for recruitment, qualification records, apprenticeship, and safety training.
- **Parts and quality:** local hardware and appliance-parts suppliers for stock availability, warranty terms, emergency sourcing, and reliable cost input.
- **Repeat demand:** property managers, landlords, student housing, and small businesses for scheduled maintenance, after the consumer pilot establishes delivery quality.
- **Professional controls:** Cameroon employment/payroll counsel, accountant, insurance broker, and privacy adviser. CNPS employer guidance and the personal-data law referenced in the SRS make specialist review important before production. [CNPS employer rules](https://www.cnps.cm/fr/employeurs/regles-generales-pour-les-employeurs1.html), [Official Law No. 2024/017 text](https://prc.cm/fr/multimedia/documents/10258-loi-n-2024-017-du-23-12-2024-web)

## 6. Decisions for owner approval

1. Confirm whether ASTRA is the company and TechConnect the product, or select one public name.
2. Approve Douala as the first-city hypothesis or provide a replacement, then select the actual first zones.
3. Approve the employee-based managed-service model and retire marketplace language if confirmed.
4. Select pilot trades, technician qualifications, diagnostic/call-out fee, cancellation/refund rules, parts policy, warranty/rework terms, and service hours.
5. Set the finance owner and margin rule for versioned pricebook changes; approve payroll and privacy/security reviewers.
6. Define measurable pilot gates for contribution per completed job, arrival reliability, first-time fix, rework/refund, utilization, repeat use, settlement variance, payroll timeliness, and runway.

## Source basis

This proposal uses the latest supplied SRS and database-design supplement as the system baseline, while treating the older marketing, client-policy, and household-price documents as materials to reconcile rather than current operating rules. The source SRS specifically excludes independent-provider listings and technician-set pricing from the first release, and the image concepts consistently depict centralized pricing, employee workspaces, dispatch, payment verification, and payroll separation.
