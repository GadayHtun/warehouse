# Warehouse — Screenshot Report

Visual walkthrough of the Warehouse inventory control platform.

---

## Project

- **GitHub username:** @gadayhtun
- **Repo URL:** https://github.com/GadayHtun/warehouse
- **Live / download URL:** https://warehouse-mesm.onrender.com
  <!-- A web link people can open, OR a download link (apk / zip / release). -->
- **License:** <!-- e.g. MIT, Apache-2.0, GPL-3.0 — must match the LICENSE file in your repo -->
- **One-line summary:** <!-- what your project does, in one plain sentence -->

---
## Product-Intro Slides

- **Slides path:** <!-- file path inside your repo, e.g. slides/intro.md — NOT a long https:// link -->

## Demo Screenshots

## 01 — Login Page

![Login Page](01-login-page.png)

Secure authentication with role-based access control. Three user tiers:

- **Admin** — full system access
- **Supervisor** — oversight and reconciliation approval
- **Agent** — stock operations only

---

## 02 — Dashboard

![Dashboard](02-dashboard.png)

Central hub showing four key metrics at a glance:

- Pending reconciliation sessions
- Open agent findings (critical + warning)
- 30-day net variance
- Low stock alerts

Widget data cached in Redis (1–5 min TTL) with automatic invalidation on stock movements and reconciliation events.

---

## 03 — Stock Overview

![Stock Overview](03-stock-overview.png)

Real-time inventory visibility across the entire network:

- **20 SKUs** across 5 categories (Beverages, Snacks, Groceries, Cleaning, Dairy)
- **5 locations** — 2 warehouses (WH-A, WH-B), 3 stores (ST-01, ST-02, ST-03)
- **5 suppliers** with contact tracking

---

## 04 — Stock In

![Stock In](04-stock-in.png)

Record incoming inventory shipments. Every stock-in creates an append-only transaction — immutable and fully traceable. Idempotency keys prevent duplicate entries from network retries or double-clicks.

---

## 05 — Stock Out

![Stock Out](05-stock-out.png)

Dispatch inventory with **pessimistic locking** (`lockForUpdate()`). Serializes concurrent withdrawals to prevent negative stock. Every outbound movement is recorded in the transaction ledger.

---

## 06 — Reconciliation Sessions

![Reconciliation](06-reconciliation.png)

List of reconciliation sessions with status tracking. Sessions follow a 6-stage gated pipeline:

```
draft → in_progress → submitted → under_review → closed
```

---

## 07 — Agent Findings

![Findings](07-findings.png)

8 autonomous anomaly checks running on scheduled cadence:

| Check | Severity | What it catches |
|---|---|---|
| Negative Stock | Critical | On-hand below zero |
| Dormant Stock | Info | 30+ days without stock-out |
| Rapid Depletion | Warning | 2× normal outflow |
| Variance Drift | Warning | Growing discrepancy |
| Reconciliation Staleness | Info | 90+ days without count |
| Duplicate Movements | Critical | Identical txns within 60s |
| Unbalanced Transfers | Warning | Transfer mismatch after 24h |
| Price Anomaly | Info | Cost price jump >30% |

Findings deduplicated by content hash to prevent alert fatigue.

---

## 08 — Create Reconciliation

![Create Reconciliation](08-reconciliation-create.png)

Start a new cycle count session by selecting a location and optional category filter. The system enforces the **4-eyes principle** — large variances (>5% or 50 units) require approval from a different supervisor than the one who submitted.

---

## Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12, PHP 8.4-FPM |
| Database | MySQL 8.0 |
| Cache / Queue | Redis 7 |
| Web Server | Nginx (Alpine) |
| Frontend | Blade + Tailwind CSS |
| Orchestration | Docker Compose (5 services) |

