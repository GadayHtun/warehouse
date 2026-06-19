# Warehouse Inventory System — Feature Specification

**Author:** @gadayhtun
**Phase:** 1 (Single Organization)
**Architecture:** Monolithic Laravel Application

---

## 1. Product Overview

A warehouse inventory platform where the **Supervisor** is the operational control center. The Supervisor uses two primary tools:

- **Inventory Reconciler** — compares physical stock counts against system records, calculates variance, and resolves discrepancies through adjustments.
- **Database Agent** — a system agent that continuously monitors the database for stock anomalies and reporting irregularities, surfacing findings for Supervisor review.

Every stock movement is an auditable event. No inventory can be changed by direct database edits — only through system workflows.

### 1.1 Core Principles

| Principle | Meaning |
|---|---|
| **Supervisor is the control center** | All inventory oversight, reconciliation, and agent findings flow through the Supervisor dashboard |
| **Reconciliation is systematic** | Variance between physical counts and system records is calculated, reported, and resolved |
| **No direct stock edits** | Inventory cannot be changed by raw database updates — only through workflows |
| **Every movement is auditable** | No stock change occurs without a transaction record carrying owner and timestamp |
| **Agent-assisted monitoring** | A database agent continuously scans for anomalies and reports to the Supervisor |

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| **Backend Language** | PHP 8.3 |
| **Framework** | Laravel 12 |
| **Database** | MySQL 8 |
| **Frontend** | Blade templates + Tailwind CSS |
| **Queue** | Database queue driver |
| **Cache** | Redis |
| **Storage** | Local storage (filesystem) |
| **Testing** | PHPUnit |
| **Containerization** | Docker (app + MySQL + Redis + queue worker) |

### 2.1 Architecture Layers

```
Presentation Layer   → Blade + Tailwind (server-rendered HTML)
Application Layer    → Services (business logic orchestration)
Domain Layer         → Inventory Engine, Reconciliation Engine, Database Agent
Infrastructure Layer → Repositories + MySQL + Redis
```

---

## 3. User Roles

| Role | Purpose | Scope |
|---|---|---|
| **Admin** | System setup, user management, master data | Full access |
| **Supervisor** | Operational control — reconciliation, reviewing agent findings, dashboard oversight | Approve adjustments, view all dashboards and reports, manage reconciliation sessions |
| **Inventory Agent** | Day-to-day operations — stock in, stock out, physical counting | Record stock movements, submit count sheets, view assigned location data |

### 3.1 Role Enforcement

- Every route and service operation checks role before executing.
- A user holds exactly one role at a time.
- Failed authorization logs an audit entry and returns a forbidden response.

---

## 4. Functional Modules

---

### 4.1 Authentication & Session Management

**Purpose:** Secure user login with role-bound session.

**Behavior:**
- Login requires email + password.
- On success, the user's role is loaded into the session.
- Failed login attempts are rate-limited: 5 attempts per 15 minutes per IP.
- Session expires after 120 minutes of inactivity.
- Logout destroys the session and redirects to login.

**Implementation Guidance:**
- Use Laravel's session guard with database-backed sessions.
- Store `last_active_at` on the users table — update on each authenticated request via middleware.
- Login/logout events are written to the audit log.

---

### 4.2 Master Data

**Purpose:** Reference data that inventory operations depend on.

#### 4.2.1 Products

- **Fields:** SKU (unique, immutable), name, description, category, unit of measure (pcs, kg, L), minimum stock threshold, reorder point, cost price, retail price, barcode (optional).
- **Behavior:**
  - Products can be created, edited, and soft-deleted (not if transaction history exists).
  - Price changes are audited and do not retroactively change historical transactions — prices are snapshotted on transaction records.

#### 4.2.2 Locations

- **Fields:** Name, code, address, type (warehouse | store), status (active | inactive).
- **Behavior:**
  - Inventory is tracked per location.
  - A location with stock on hand cannot be deleted.

#### 4.2.3 Suppliers

- **Fields:** Name, contact person, phone, email, status (active | inactive).
- **Behavior:**
  - Only active suppliers appear in dropdowns.
  - Suppliers with transaction history are deactivated, not deleted.

---

### 4.3 Basic Stock Operations

**Purpose:** Simple stock-in and stock-out recording — the minimum needed to move inventory so reconciliation has data to compare against.

#### 4.3.1 Stock In

- An agent records goods received at a location.
- Fields: product, quantity, location, supplier (optional), reference note, batch/lot (optional).
- On confirmation, inventory is incremented and a `stock_in` transaction is created.
- No purchase order dependency — standalone receiving.

#### 4.3.2 Stock Out

- An agent records stock leaving a location.
- Fields: product, quantity, location, reason (sales / transfer / internal-use / write-off / return-to-supplier), reference note.
- On confirmation, the system validates that sufficient stock exists — rejects if `on_hand - requested < 0`.
- A `stock_out` transaction is created.

#### 4.3.3 Implementation Guidance

- Both operations use a single `stock_movements` table with a `direction` column (`in` | `out`).
- Wrap the movement + inventory update in one database transaction.
- Use pessimistic locking (`SELECT ... FOR UPDATE`) on the inventory row during stock-out to prevent race conditions.

---

### 4.4 Inventory Engine

**Purpose:** The single source of truth for stock quantities. All stock changes flow through this engine.

#### 4.4.1 Core Concepts

- **On-Hand Quantity:** Physical stock currently at a location — computed as `SUM(transaction quantities)` for that product-location pair.
- **Reorder Point:** When on-hand falls below this threshold, the product is flagged.

#### 4.4.2 Transaction Types

| Type | Effect | Trigger |
|---|---|---|
| `stock_in` | + | Stock-in recorded |
| `stock_out` | − | Stock-out recorded |
| `adjustment_in` | + | Reconciliation adjustment (positive variance) |
| `adjustment_out` | − | Reconciliation adjustment (negative variance) |

#### 4.4.3 Rules

- Every inventory change creates exactly one row in `inventory_transactions` — append-only, no updates, no deletes.
- On-hand quantity is the SUM of all transaction quantities per product-location. Computed on read via indexed query.
- A `current_stock` denormalized table may be maintained, updated synchronously within the same DB transaction, with the transaction log as the authoritative source.

#### 4.4.4 Implementation Guidance

- The inventory engine is a dedicated service class. No controller, job, or command may modify inventory tables directly — all paths must go through this service.
- Pessimistic locking on stock-out to serialize concurrent decrements on the same product-location.
- Each transaction carries a unique reference to prevent duplicate processing (idempotency check before insert).

---

### 4.5 Inventory Reconciler

**Purpose:** The Supervisor's primary tool — compares physical stock counts against system records, calculates variance, and resolves discrepancies.

#### 4.5.1 Reconciliation Workflow

```
1. Initiate Session
   Supervisor creates a reconciliation session: selects a location, optionally filters by product category.
   Session state: draft.

2. Enter Physical Counts
   The supervisor or agent enters physical count quantities per product into the session.
   Session state: in_progress.

3. Submit & Calculate Variance
   The supervisor submits the session.
   System snapshots the current system quantity for each counted product.
   Variance = physical_count − system_quantity_at_count.
   Session state: submitted.

4. Review & Resolve
   Supervisor reviews each line:
   - Zero variance lines: auto-close.
   - Lines with variance: Supervisor chooses —
     a. Accept variance → creates an adjustment transaction (in or out), inventory updates to match physical.
     b. Flag for recount → line remains open for a new count.
     c. Defer with note → line stays open for investigation.
   Session state: under_review.

5. Finalize
   When all lines are resolved, the supervisor closes the session.
   A reconciliation report is generated.
   Session state: closed.
```

#### 4.5.2 Variance Interpretation

| Variance | Meaning | Suggested Action |
|---|---|---|
| **Positive** (physical > system) | Unrecorded stock-in, counting error, or system undercount | Investigate receiving records; accept if confirmed |
| **Negative** (physical < system) | Loss, theft, damage, unrecorded stock-out, or counting error | Investigate stock-out records; accept if confirmed |

#### 4.5.3 Adjustment Rules

- Every adjustment requires a reason (minimum 10 characters).
- Large variances (configurable threshold: default 5% of system quantity or 50 units, whichever is larger) require a second Supervisor approval before the adjustment is applied.
- Adjustments create `adjustment_in` or `adjustment_out` inventory transactions — fully auditable.

#### 4.5.4 Reconciliation Dashboard

A dedicated view for the Supervisor showing:
- Open reconciliation sessions (in_progress, submitted, under_review).
- Recently closed sessions with total variance summary.
- Per-product variance history (allows spotting products with recurring variance issues).
- Filterable by location and date range.

#### 4.5.5 Implementation Guidance

- Reconciliation session states: `draft` → `in_progress` → `submitted` → `under_review` → `closed`.
- Snapshot system quantities at submission time into `system_quantity_at_count` on each count line — this ensures the variance calculation is based on what the system believed at count time, not what it believes after later stock movements.
- Large-variance dual-approval: flag the adjustment line in a `pending_approval` state and queue it in the Supervisor's approval list.
- Reconciliation reports export to PDF (server-side render from a Blade template).

---

### 4.6 Database Agent

**Purpose:** A system agent that continuously monitors the database for anomalies, irregularities, and patterns. It surfaces findings for Supervisor review — acting as a proactive watchdog over inventory data.

#### 4.6.1 Agent Checks

The agent runs the following checks on a schedule (configurable per check):

| Check | What It Detects | Frequency |
|---|---|---|
| **Variance Drift** | Products where the gap between physical count and system quantity is growing across reconciliation sessions | After each reconciliation close |
| **Dormant Stock** | Products with zero stock-out movements for a configurable period (default: 30 days) — potential dead stock | Daily |
| **Negative Stock** | Any product-location where on-hand has gone negative (indicates a system bug or bypassed validation) | Hourly |
| **Rapid Depletion** | Products where stock-out rate over the last 7 days exceeds the 30-day average by 2× or more | Daily |
| **Reconciliation Staleness** | Product-locations where the last reconciliation was more than a configurable threshold ago (default: 90 days) | Weekly |
| **Duplicate Movements** | Stock movements with identical product, quantity, location, and timestamp within a 60-second window | Daily |
| **Unbalanced Transfers** | Stock transfers where the out quantity at source does not match the in quantity at destination within a grace period | Daily |
| **Price Anomaly** | Products where cost price changed by more than 30% in a single edit | On price change |

#### 4.6.2 Agent Findings Dashboard

A dedicated view for the Supervisor showing:
- **Open Findings:** List of all unresolved agent findings, ordered by severity and date.
- Each finding shows: check type, severity (info | warning | critical), product/location affected, description, detected date.
- **Actions per finding:**
  - **Acknowledge:** Supervisor marks the finding as reviewed. It moves to acknowledged state.
  - **Dismiss** (with reason): Supervisor determines it's a false positive. Requires a note.
  - **Create Reconciliation:** For variance-related findings, a one-click action to create a reconciliation session scoped to the affected product-location.
- **Finding History:** All acknowledged and dismissed findings, filterable by check type and date range.

#### 4.6.3 Severity Levels

| Severity | Meaning | Examples |
|---|---|---|
| **Critical** | Data integrity at risk | Negative stock, duplicate movements |
| **Warning** | Operational attention needed | Rapid depletion, variance drift, unbalanced transfers |
| **Info** | Awareness | Dormant stock, reconciliation staleness, price anomaly |

#### 4.6.4 Implementation Guidance

- Each check is a dedicated class implementing a common `AgentCheck` interface with a `run(): array<Finding>` method.
- Checks are dispatched as queued jobs. A scheduler (Laravel task scheduler) triggers them at the configured frequency.
- Findings are stored in an `agent_findings` table with: check type, severity, product_id (nullable), location_id (nullable), title, description, detected_at, status (open | acknowledged | dismissed), reviewer_id, reviewed_at, review_note.
- The agent must deduplicate: before inserting a finding, check if an identical finding is already open for the same product-location. If so, skip.
- When a finding is acknowledged or dismissed, record the reviewer and timestamp — this is auditable.

---

### 4.7 Audit Log

**Purpose:** Immutable record of every operation that affects data.

#### 4.7.1 What Gets Logged

| Event | Data Captured |
|---|---|
| **Stock movements** | Type, product, quantity, location, user, timestamp |
| **Inventory adjustments** | Reconciliation session reference, variance, reason, approver |
| **Master data changes** | Entity type, entity ID, old values (diff JSON), new values (diff JSON), user |
| **Reconciliation sessions** | Created, submitted, resolved, closed — with user and timestamp |
| **Agent finding reviews** | Finding ID, action (acknowledge/dismiss), reviewer, note |
| **Authentication** | Login, logout, failed attempts |

#### 4.7.2 Constraints

- Audit tables are append-only — no UPDATE or DELETE.
- Inventory transactions retained indefinitely. General audit log may be archived after 2 years.
- Audit log is not directly queryable by end users — accessed through pre-built audit views.

#### 4.7.3 Implementation Guidance

- `inventory_transactions` table for stock movements (separate from general audit log for query performance).
- `audit_logs` table for all other events — old_values and new_values stored as JSON diffs.
- Use Laravel model events with a queued listener for non-critical audit writes.

---

### 4.8 Supervisor Dashboard

**Purpose:** Single overview screen — the Supervisor's landing page after login.

| Widget | Content |
|---|---|
| **Pending Reconciliation** | Count of sessions in `submitted` or `under_review` state |
| **Agent Findings** | Count of open findings, grouped by severity (critical / warning / info) |
| **Variance at a Glance** | Total absolute variance value across the last 30 days, trend indicator |
| **Recent Activity** | Last 10 stock movements across all locations |
| **Low Stock** | Products currently below reorder point |

**Implementation Guidance:**
- Dashboard data is fetched via dedicated aggregate queries — not by loading full model collections.
- Cache dashboard query results for 5 minutes in Redis. Invalidate relevant keys on new stock movements or reconciliation events.
- Findings counts should be real-time (not cached) if the count is small — otherwise use a 1-minute cache.

---

### 4.9 Reporting

**Purpose:** Exportable reports for operational review.

| Report | Content | Format |
|---|---|---|
| **Reconciliation Report** | Per-session: count lines, variances, resolutions | PDF |
| **Inventory Status** | Current stock levels per product per location with value | PDF, Excel |
| **Stock Movement Log** | All inventory transactions in a date range | Excel, CSV |
| **Agent Findings Log** | All findings (open and closed) in a date range | PDF, Excel |

**Implementation Guidance:**
- Reports are generated on-demand. Use Blade templates rendered to PDF (DomPDF) and PhpSpreadsheet for Excel.
- Synchronous reports must complete under 10 seconds. Heavy reports (large date ranges) are generated via a queued job with an in-app notification when ready.

---

## 5. Cross-Cutting Concerns

### 5.1 Error Handling

- User-facing errors display a human-readable message — never a stack trace.
- Validation errors return field-specific messages next to the relevant input.
- Server errors (5xx) display a generic message with a support reference code.

### 5.2 Caching

| Data | Strategy |
|---|---|
| Products, locations, suppliers | Cache indefinitely, invalidate on update |
| Dashboard aggregates | 5-minute TTL |
| Inventory quantities | Not cached — always queried live |
| Agent findings counts | 1-minute TTL |

### 5.3 Queue

- Database queue driver (no external queue server in Phase 1).
- Queued work: agent checks, audit log writes, heavy report generation.
- Queue worker runs as a separate container process.

---

## 6. Success Metrics

| Metric | Target | How Measured |
|---|---|---|
| Inventory variance | < 1% of total inventory value | Reconciliation reports over 30-day rolling window |
| Reconciliation coverage | 100% of product-locations reconciled within 90 days | Agent check for reconciliation staleness |
| Stock movement traceability | 100% of movements have a transaction record | Cross-reference stock-in/out records vs inventory_transactions |
| Agent finding resolution | > 90% of findings acknowledged or dismissed within 7 days | agent_findings.reviewed_at − detected_at |
| Report generation | < 10 seconds (synchronous) | 95th percentile server-side render time |

---

## 7. Out of Scope

| Area | Rationale |
|---|---|
| Purchase orders, supplier receiving workflows | Phase 1 uses simple stock-in/out; PO workflow adds complexity not needed for reconciliation MVP |
| Complex approval engine (multi-step, conditional routing) | Keep it simple — only dual-approval for large variance adjustments |
| Email/SMS/push notifications | In-app only |
| Barcode scanning hardware | Barcode field exists for manual entry |
| Multi-tenant (multiple organizations) | Single organization for Phase 1 |
| Accounting, payroll, HR, CRM | Not an ERP |
| Customer-facing marketplace / shopping cart | Not a commerce platform |
| Real-time IoT inventory sensors | No hardware dependency in Phase 1 |

---

## 8. Implementation Phasing

| Phase | What | Why First |
|---|---|---|
| **1A — Foundation** | Auth, user roles, master data (products, locations, suppliers) | Nothing works without reference data and login |
| **1B — Inventory Core** | Stock in/out, inventory engine, inventory transactions, audit log | The transaction log is the source of truth — build it first |
| **1C — Reconciler** | Reconciliation sessions, count entry, variance calculation, adjustments | The Supervisor's primary tool — core of the product |
| **1D — Database Agent** | Agent check framework, all 8 checks, findings dashboard, review workflow | Proactive monitoring — turns the system from reactive to proactive |
| **1E — Dashboard & Reports** | Supervisor dashboard, reconciliation reports, inventory status, stock movement log, findings log | Visibility and audit readiness |

---

*Document version: 2.0 — Last updated: 2026-06-19*
