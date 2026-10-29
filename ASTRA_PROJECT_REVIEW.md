# ASTRA project review and recommended solution

> **Historical note:** This assessment predates the MVP implementation and the client's MySQL/XAMPP selection. For the current database setup and application status, follow [README.md](README.md), [SRS.md](SRS.md), and [docs/IMPLEMENTATION_STATUS.md](docs/IMPLEMENTATION_STATUS.md). The earlier PostgreSQL recommendation below is superseded by MySQL-compatible XAMPP MariaDB.

## Executive assessment

The package contains a promising service concept and unusually detailed operating requirements, but it is not yet an implementation-ready software project. There is no application source code, deployable database schema, API contract, payment integration, or operating-cost model in the supplied files. The strongest current source is the TechConnect Software Requirements Specification revision 2.0, extended by the revision 2.1 database design supplement in the same PDF.

The biggest issue is that the project documents describe two different businesses. The older Astra marketing, pricing, and policy documents describe an open marketplace where independent providers quote and negotiate. The current TechConnect SRS describes a managed repair company that employs technicians, controls the pricebook, collects customer payments, and pays regular salaries. Those models have different legal, staffing, support, payment, and financial requirements. The recommendations below assume the newer managed-service model is the intended direction; the owner should formally approve that choice and align the other materials to it.

## Findings by area

### Product and business model

- **Conflicting model:** The marketing plan suggests technician subscriptions or booking fees and independent providers; the SRS excludes independent provider listings, technician-set quotes, and payment to technicians per job. Pick the managed-service model as the single source of truth, or rewrite the SRS if the marketplace is actually intended.
- **Conflicting identity and geography:** The folder and two older documents use Astra; the SRS and screens use TechConnect. The marketing plan starts in Buea, while the SRS and screens describe Douala and Yaounde. The public homepage footer says Douala and Yaounde, while one service page sample says Douala. Confirm the legal/business name, product name, launch city, and first zones before publishing anything.
- **Scope is too broad for launch:** Older materials include cleaning, painting, roofing, carpentry, and general household services. The SRS focuses on technical trades and correctly says broad coverage is not a day-one promise. Launch a small catalog and limited zones where qualified staff and replacement coverage exist.
- **Pricing is not ready to publish:** The repair-price document calls its XAF ranges approximate planning estimates and says they need local confirmation. The mockups include illustrative prices and draft catalog rows. Treat them as design examples, not approved customer rates. Cost each item from labor, parts, transport, provider fees, taxes, warranty/rework, and margin; approve and version the complete pricebook centrally.
- **Policies need a model rewrite:** The client penalties document has a fair investigation and appeal concept, but several rules assume independent providers and refer to seeking compensation. Separate customer terms (cancellation, refund, payment disputes, damage, conduct) from employee HR/safety procedures. Do not charge arbitrary penalties or promise remedies until local counsel approves the terms.
- **No validated economics:** The marketing budget is explicitly illustrative (FCFA 90,000). The SRS gives contribution-margin and break-even formulas but no validated payroll burden, travel costs, job volumes, provider fees, warranty reserve, or pricebook cost inputs. Do not scale the employed workforce on registration counts alone.

### Design and user experience

The screen concepts share a clear navy/teal visual system and the estimate, payment-pending, dispatch, active-job, and receipt screens largely support the SRS journey. Keep this visual direction and prioritize accessibility and low-bandwidth performance.

Before implementation, revise the screens to use one brand and geography, replace sample dates and prices with explicitly labeled prototype content, and ensure all service cards only show categories actually staffed in the selected zone. Make the customer journey explicit about the diagnostic/call-out fee, what a price includes, when payment is collected, what happens if the technician finds extra work, and how refunds/rework work. Add loading, offline/retry, empty, error, cancellation, declined estimate, payment timeout, no-staff-available, and dispute states. The login screen presents one account for customers and staff; keep the interface unified only if backend authentication and authorization still enforce separate role-specific workspaces.

Some mockup icons render as empty square glyphs, so bundle tested icons or use accessible inline SVG. Mark sample data clearly and avoid presenting illustrative ratings, technician verification, immediate availability, guarantees, or customer counts as real claims. The duplicate Mermaid diagrams should be reduced to one maintained source; the dark ERD is hard to read at normal viewing size. The 74 SVG icons and 118 photos in `assets/Images` need an asset manifest that records intended use, source/license, and selected production assets. The top-level `payment.jpg` is a hotel checkout mockup and `Livechard.jpg` is an unrelated inventory dashboard; remove or clearly archive them so they are not mistaken for TechConnect designs.

### Database

The proposed relational model is a sound starting point: it versions prices, snapshots estimates, retains assignment and session history, separates payment evidence from work authorization, restricts address access, and keeps service timers out of payroll. The client has selected XAMPP's MySQL-compatible service (commonly MariaDB); use InnoDB, utf8mb4, and the current schema in `database/mysql/techconnect.sql`.

At the time of this original assessment, the package had no deployable `.sql` file. The current project now contains a MySQL/MariaDB schema and local API adapter. Production still needs a reviewed migration runner, least-privilege database users, row/role scoping, transition and immutable-record controls, encryption/key management, indexes, and verified restore procedures.

Before coding, produce versioned migrations with foreign keys, unique constraints, money and quantity validation, explicit status enums, and transaction-safe commands for estimate totals, accepted estimate/payment matching, webhook idempotency, and job transitions. Make submitted estimates and invoices append-only; changed scope gets a new estimate. Use an append-only event/audit record for status changes and money-sensitive actions. Store media in private object storage, not database blobs. Keep HR/payroll access in a separate restricted service boundary or schema and enforce role checks in both API and database. Add operational tables only when the pilot needs them, but include cancellation/refund decisions, customer support cases, and parts procurement/inventory if TechConnect promises to procure replacement parts.

### Backend and API

At the time of this original assessment there was no API contract or backend implementation. The current MVP uses a responsive low-bandwidth PWA and a PHP API connected to XAMPP MySQL/MariaDB through PDO. Production still needs private media storage, a background job/notification worker, and a hardened supervisor/admin environment. Keep payroll calculations and sensitive HR records separately permissioned.

Publish an OpenAPI contract before frontend/backend implementation. A minimal API surface should cover:

| Area | Example operations | Essential controls |
|---|---|---|
| Identity and profile | `POST /v1/auth/...`, `GET /v1/me` | Short-lived sessions, MFA for staff/admin, throttling and recovery controls |
| Catalog and coverage | `GET /v1/catalog`, `GET /v1/zones` | Only published, effective items; bilingual labels; public price/fee rules |
| Requests | `POST /v1/requests`, `GET /v1/requests/{id}` | Consent version, idempotency key, customer ownership, protected address |
| Dispatch and diagnosis | `POST /v1/requests/{id}/assignments`, `POST /v1/requests/{id}/findings` | Staff scopes and trade/zone eligibility; assignment history; never accept a client-entered price |
| Estimates | `POST /v1/requests/{id}/estimates`, `POST /v1/estimates/{id}/decision` | Server-calculated totals, immutable versions, explicit customer accept/decline |
| Payments | `POST /v1/estimates/{id}/payments`, `GET /v1/payments/{id}`, `POST /v1/webhooks/{provider}` | Provider-hosted collection, signed/verified callbacks or server status checks, deduplication, reconciliation |
| Work and customer outcome | `POST /v1/requests/{id}/work-events`, `POST /v1/requests/{id}/completion-decision` | Payment-backed start, reasoned pauses, evidence retention, only customer confirmation closes |
| Operations | `GET /v1/dispatch/queue`, `POST /v1/requests/{id}/reassign`, refund/rework actions | Role checks, reason codes, second-person approval for sensitive financial actions |

Use consistent error codes, pagination, request correlation IDs, UTC timestamps, and XAF integer amounts. Document the state machine centrally and reject invalid transitions at the server boundary. Never trust a success redirect or browser state to authorize work. Deliver notifications through retryable jobs, with SMS/phone fallback for urgent dispatch where the customer has consented. Add dashboards and alerts for stuck dispatches, delayed payment confirmation, duplicate callbacks, payment settlement mismatches, late jobs, safety reports, and open customer disputes.

### Partners and operations

Start partner conversations before choosing a payment vendor or promising coverage. Candidate partner groups are:

1. **Payment collection:** direct merchant onboarding with Orange Money and MTN MoMo, plus an aggregator candidate such as CamPay or KoraPay for a unified integration. These are options to evaluate, not endorsements. Run sandbox tests and verify merchant eligibility, settlement times, fees, refunds/reversals, callback signatures or server-side verification, reconciliation exports, support escalation, and legal contracting before selecting one. Orange Cameroun publishes a web-payment partner process; MTN publishes a developer platform; CamPay and KoraPay describe Cameroon mobile-money acceptance in their product materials. [Orange Cameroun Webpayment](https://www.orange.cm/fr/om-partenaires/partenaire-webpayment.html), [MTN Developer Platform](https://developers.mtn.com/getting-started), [CamPay](https://www.campay.net/en/), [KoraPay mobile money documentation](https://developers.korapay.com/docs/mobile-money)
2. **Supply and training:** local trade associations, vocational/technical training centers, and vetted parts/hardware shops for hiring, qualification evidence, safety training, price/cost validation, and parts availability.
3. **Demand and referrals:** landlords, property managers, student residences, small offices, and hardware stores. Offer scheduled maintenance and a clear service-level agreement only after the pilot proves response capacity.
4. **Compliance and risk:** Cameroon employment/payroll counsel, accountant/payroll specialist, insurance broker, and privacy/security adviser. The CNPS employer guidance makes employer registration, salary declarations, and contributions operational obligations; the app can support records but should not be treated as legal advice or an authoritative payroll calculator. [CNPS employer rules](https://www.cnps.cm/fr/employeurs/regles-generales-pour-les-employeurs1.html)

Do not describe La Mercuriale as a universal consumer repair tariff. The SRS already makes this distinction; keep public claims consistent with the actual scope of official references. The SRS also refers to Cameroon Law No. 2024/017 on personal data protection; confirm commencement, implementing instruments, and obligations with local counsel before launch, and design for access/correction requests, retention, incident handling, and any cross-border processor review. [Official law text](https://prc.cm/fr/multimedia/documents/10258-loi-n-2024-017-du-23-12-2024-web)

### Business strategy

Run a tightly bounded paid pilot as a managed service. Select one city and a few adjacent zones, then choose two or three trades based on observed demand, employee availability, parts supply, safety/qualification needs, and ability to cost a typical job. Offer reliable scheduled service first; avoid a 24/7 or instant-response promise. Use manual supervisor dispatch during the pilot and keep every assignment, approval, charge, refund, and completion in the system.

Validate job economics using completed work, not top-of-funnel registrations:

- **Contribution per job:** collected service revenue excluding tax/pass-through amounts, minus parts, provider fees, direct travel, job labor allocation, and a warranty/rework reserve.
- **Workforce utilization:** productive paid time divided by paid available time, with travel, training, leave, and idle capacity visible separately.
- **Quality and trust:** first-time-fix rate, customer-confirmed completion, repeat booking, rework/refund rate, safety incidents, complaints, and customer satisfaction.
- **Cash and operations:** payment settlement variance, payroll paid on time, dispatch acceptance/arrival, cost per completed customer, and runway.

Use customer interviews and a small set of real, costed jobs to test whether customers accept company-set diagnostic fees and fixed itemized estimates. Test acquisition channels in small batches (property-manager referrals, neighborhood WhatsApp with permission, local trade/shop referrals, and narrowly targeted social ads); retain only channels with trackable completed jobs and acceptable acquisition cost. Set numeric pilot gates before recruitment/marketing spend increases: minimum contribution per job, acceptable rework/refund ceiling, arrival reliability, staff utilization, repeat demand, and cash runway. Do not scale payroll or geography until the gates hold across multiple review periods.

## Recommended delivery plan

| Phase | Deliverables | Exit condition |
|---|---|---|
| 0. Decisions and field validation | Confirm brand, operating model, first city/zones/trades, fee/refund/warranty rules, employment and safety assumptions, partner shortlist, and costed catalog | Owner-approved business rules and local professional review of employment, privacy, tax, payment, and customer terms |
| 1. MVP foundation | OpenAPI contract, auth/roles, catalog/version approval, request and dispatch workflow, event/audit model, migrations, staff console, low-bandwidth customer PWA | Request-to-estimate flow works end to end in staging; no price entry by technician; role and address controls verified |
| 2. Payment and service closeout | Provider sandbox/production integration, idempotent webhook/status processing, receipts, work events, completion, refunds, rework, support cases | Duplicate/failure/pending callbacks cannot authorize work; financial reconciliation and customer recourse are operational |
| 3. Controlled pilot | Small employed team, a few zones/trades, supervisor-led dispatch, weekly unit-economics and quality review | Pilot gates met for multiple periods; payroll, settlement, safety, and service quality are stable |
| 4. Extend deliberately | Add catalog items, staff, zones, partner contracts, and later maintenance plans/business accounts | Each expansion has demand, cost, capacity, and quality evidence |

## Priority decisions before development

1. Is the approved model TechConnect as an employed-workforce service company? If yes, retire marketplace promises and rewrite the old marketing, household pricing, and client penalty materials.
2. Is Astra the corporate identity and TechConnect the product, or should all materials use one name? Confirm city and launch zones.
3. Which first trades/catalog items are both safe to staff and economically costed? Who owns pricebook approval and repricing?
4. What are the diagnostic fee, cancellation/refund, extra-work, parts, warranty/rework, and unresponsive-customer rules?
5. Which payment provider will be contracted, and what are its signed callback/status verification, settlement, refund, and reconciliation processes?
6. Which employment contracts, pay bands, leave/overtime, CNPS/tax calculations, insurance, and privacy/retention rules are approved by qualified local advisers?

## Review coverage and limitations

The original assessment reviewed all three DOCX files, all 19 pages of the source SRS/database supplement PDF, all top-level screen/concept image files, all 118 images under `assets/Images` via contact sheets, and the 74 SVG icon filenames. The image and icon folders remain a large uncurated asset library; the audit records their count, duplicate diagram, and missing source/use manifest rather than assigning a product role to every stock image or icon. At that time, there was no application source code, deployment configuration, standalone SQL file, API specification, or runnable test suite. The review is a product/architecture readiness assessment, not a code security audit or legal opinion.

## Project materials reviewed

- [ASTRA SRS and database design supplement](ASTRA-SRS.pdf)
- [Marketing strategy](Astra_Application_Marketing_Strategies.docx)
- [Problem management and client policies](Astra_Application_Problems_and_Client_Policies.docx)
- [Household repair prices](TechConnect_Household_Problems_and_Repair_Prices.docx)
- Screen concepts: registration, login, contact, service discovery, client dashboard, estimate review, payment method, payment processing, payment receipt, technician dashboard, active job, supervisor dispatch board, operations dashboard, pricebook approval, and monthly payroll.
- Supporting concepts/assets: database model, two duplicate Mermaid diagrams, theme image, sample payment and live-chat images, 120 stock/service images, and 74 SVG icons.
