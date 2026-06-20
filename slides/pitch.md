# 📦 Warehouse — Pitch Deck
### 6 slides · 20 seconds each · ~2 minutes total

---

## Slide 1 — The Problem

**Inventory lies.**

Stock levels drift. A shipment arrives, someone keys it in wrong. A picker grabs six units but marks two. A product sits untouched for six months while the reorder budget shrinks.

Most warehouses accept this. They run a cycle count once a quarter, file a PDF, and move on. By then the discrepancy is old, the money is gone, and nobody knows why.

Manual cycle counts are slow. Spreadsheets are error-prone. And most inventory systems don't *watch* — they just record.

> **Warehouse doesn't accept drift. It detects it in real time.**

⏱ 0:20 — Next: What is it?

---

## Slide 2 — Platform Overview

**Warehouse** is an inventory control platform for multi-location operations — with an autonomous anomaly detection layer.

**What it tracks:**
- **20 SKUs** across 5 categories — Beverages, Snacks, Groceries, Cleaning, Dairy
- **5 locations** — 2 warehouses (WH-A, WH-B), 3 stores (ST-01 through ST-03)
- **5 suppliers** with contact tracking and active/inactive status

**How it's built:**
- **3 role tiers** — Admin (full access), Supervisor (oversight + reconciliation), Agent (stock ops)
- **Append-only transaction ledger** — every stock movement is immutable and traceable
- **Pessimistic locking** on stock-out — no double-ships, no race conditions
- **Idempotency keys** on every transaction — network retries won't duplicate
- **Docker Compose** — 5 services, one `docker compose up -d --build`

> Think of it as the Laravel of warehouse management — expressive, structured, and designed to catch problems *before* they compound.

⏱ 0:20 — Next: The killer feature.

---

## Slide 3 — The Agent System

**8 autonomous checks. Zero human effort.**

An *agent orchestrator* runs checks across every product × location pair. Each implements a shared `AgentCheck` contract — pluggable, deduplicated, severity-ranked.

| Check | Severity | Cadence | What it catches |
|---|---|---|---|
| Negative Stock | 🔴 Critical | Hourly | On-hand below zero — bypassed validation |
| Dormant Stock | 🔵 Info | Daily | 30+ days without a stock-out — dead inventory |
| Rapid Depletion | 🟡 Warning | Daily | 2× normal outflow — reorder now |
| Variance Drift | 🟡 Warning | Per cycle | Growing discrepancy across consecutive reconciliations |
| Reconciliation Staleness | 🔵 Info | Weekly | Locations 90+ days without a cycle count |
| Duplicate Movements | 🔴 Critical | Daily | Identical transactions within 60 seconds |
| Unbalanced Transfers | 🟡 Warning | Daily | Transfer out ≠ transfer in after 24 hours |
| Price Anomaly | 🔵 Info | On change | Cost price jump >30% in a single edit |

**Deduplication** by content hash prevents alert fatigue. Supervisors triage findings — acknowledge or dismiss — from a single page.

> It's like having an auditor who never sleeps — and never repeats themselves.

⏱ 0:20 — Next: Reconciliation workflow.

---

## Slide 4 — Reconciliation That Closes the Loop

Cycle counts follow a **6-stage gated pipeline.** No shortcuts.

```
draft  →  in_progress  →  submitted  →  under_review  →  closed
```

1. **Create** — pick a location, optional category filter, start the session
2. **Count** — scan products, enter physical quantities line by line
3. **Submit** — system snapshots on-hand, computes variance per line: **units · percentage · dollar impact**
4. **Review** — lines ordered by **financial impact**, largest first
5. **Resolve** — per line: **accept** (creates adjustment), **recount** (flag for redo), or **defer** (investigate)
6. **Large-variance guard** — discrepancies over 5% or 50 units require a **different supervisor** to approve before the adjustment posts
7. **Finalize** — all lines resolved, all large variances approved → `closed`

**Every state transition is audit-logged.** The session exports as PDF and CSV. The engine (`ReconcilerEngine`) enforces every constraint — invalid status, short reason, same-user approval, pending lines — with typed exceptions.

> Reconciliation isn't an afterthought. It's a structured, gated workflow with guardrails coded into the engine.

⏱ 0:20 — Next: What you see.

---

## Slide 5 — The Dashboard

One screen. Four key metrics. Immediate situational awareness.

```
┌───────────────────────────────────────────────────────────────┐
│  📦 Warehouse        Dashboard  │  Stock  │  Recon  │  Findings  │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐     │
│  │ Pending  │  │ Agent    │  │ Variance │  │ Low      │     │
│  │ Recon    │  │ Findings │  │ 30-Day   │  │ Stock    │     │
│  │          │  │          │  │          │  │          │     │
│  │    2     │  │  3 open  │  │  14.2u   │  │    5     │     │
│  │ sessions │  │ 1 crit   │  │ net      │  │ items    │     │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘     │
│                                                               │
│  Recent Stock Movements                                       │
│  ┌───────────────────────────────────────────────────────┐   │
│  │ Cola Can 330ml   🟢 IN   200   Main Warehouse  2m ago│   │
│  │ Potato Chips     🔴 OUT   15   Downtown Store  5m ago│   │
│  │ Rice 5kg Bag     🟢 IN   500   Main Warehouse  8m ago│   │
│  │ ...                                                   │   │
│  └───────────────────────────────────────────────────────┘   │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

**Widget data is cached** (Redis, 1–5 min TTL) with automatic invalidation on every stock movement or reconciliation event. Built entirely with Blade + Tailwind CSS — no frontend build step.

> A supervisor opens this, scans four numbers, and knows if it's a quiet day or an all-hands moment.

⏱ 0:20 — Next: Stack and roadmap.

---

## Slide 6 — Stack & Roadmap

**Built on a modern, fully containerized stack:**

| Layer | Choice | Why |
|---|---|---|
| Framework | Laravel 12, PHP 8.4-FPM | Expressive ORM, queue workers, artisan CLI |
| Database | MySQL 8.0 | Row-level locking, transactions, strict mode |
| Cache / Queue | Redis 7 | Dashboard caching, job dispatch |
| Web Server | Nginx (Alpine) | FastCGI to PHP-FPM, static asset routing |
| Frontend | Blade + Tailwind CSS | Server-rendered, zero build step in dev |
| Reports | DomPDF, CSV streaming | Downloadable reconciliation reports |
| Orchestration | Docker Compose | 5 services, health-checked startups |

**Where we're headed:**

- 🔔 **Alerts** — Slack/Teams webhook when a critical finding fires
- 📈 **Variance trending** — chart reconciliation accuracy over time across locations
- 📱 **Mobile count mode** — barcode scan → quantity entry from the warehouse floor
- 🔐 **SSO** — OAuth/OIDC for enterprise identity providers
- 🧠 **Smarter agents** — ML-based depletion forecasting, seasonal demand patterns
- ⚡ **Redis queues** — swap `database` queue driver for `redis` at production throughput

> **Warehouse** is a complete, live-tested, and running foundation. Two reconciliation sessions closed. All large-variance guardrails verified. Ready for the next phase.

---

*Built with Laravel · Dockerized · 8 agents running · 2 sessions closed · Zero open findings*
