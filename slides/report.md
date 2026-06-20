# ch-3 Personal Project — Report

github_username: gadayhtun
personal_repo_url: https://github.com/GadayHtun/warehouse
project_summary: A warehouse and mini-market inventory platform that controls inventory through approvals, transaction history, and reconciliation.
slides_url: slides/pitch.md

## Methodology

**Project-based development** — the entire application was built as a single Laravel monolith organized by domain concern. Three service classes (`InventoryEngine`, `ReconcilerEngine`, `AgentOrchestrator`) act as the sole gatekeepers for all business logic — no controller, job, or command mutates inventory tables directly. The project was structured around a phased implementation plan: Phase 1A (foundation — users, products, locations, suppliers), Phase 1B (inventory core — stock movements, append-only transactions, denormalized current stock), and Phase 1C (reconciliation pipeline with gated state machine).

**Git workflow** — every feature was committed incrementally as it was built. The `ReconcilerEngine` had two bugs discovered during live testing (premature `status = 'resolved'` assignment before large-variance check, and `approveLargeVariance` missing the status transition to `resolved`). Both were fixed and verified against real reconciliation sessions, with the database state corrected in the same commit cycle.

## Evidence — Claude Code usage

### MCP
- path: .mcp.json
- what: Not used for this project. The `enabledPlugins` in `.claude/settings.json` only references `frontend-design@claude-plugins-official` for Tailwind UI assistance. No custom MCP servers were configured — all data access was through direct database queries via `docker compose exec app php artisan tinker` and file reads.

### Skill
- path: .agents/skills/variance-analysis/SKILL.md
- what: The `variance-analysis` skill (from `anthropics/knowledge-work-plugins`) provided the financial variance decomposition methodology used in the `ReconcilerEngine`. Its materiality thresholds (5% or 50 units for large variances) were directly encoded into `ReconcilerEngine::LARGE_VARIANCE_PERCENTAGE_THRESHOLD` and `ReconcilerEngine::LARGE_VARIANCE_UNITS_THRESHOLD`. The skill's waterfall chart methodology and investigation priority ordering (largest absolute dollar variance first) shaped the `getSessionSummary()` method's sorting logic. The `ReconcilerEngine::getSessionSummary()` output structure — with net financial impact, investigation priority ordered by dollar variance, positive/negative/zero variance breakdowns — maps directly to the skill's variance analysis framework.

### Agent
- path: .claude/agents/database-engineer.md
- what: A senior database engineer agent (20 years experience, PostgreSQL/MySQL/MongoDB/Redis specialization). Used for designing the project's database schema — 13 tables including the append-only `inventory_transactions` table (no `updated_at` column), the denormalized `current_stock` table with pessimistic locking, the polymorphic reference system on transactions, and the reconciliation tables with their `large_variance_approval_status` state machine. The agent's decision-making framework (access patterns first, explicit constraint naming, lock-free migration assessment) guided every `create_users_table`, `create_products_table`, and `create_current_stock_table` migration.

- path: .claude/agents/inventory-reconciler.md
- what: A senior inventory reconciliation specialist (20+ years in supply chain auditing). Used for designing the `ReconcilerEngine`'s 6-stage pipeline: draft → in_progress → submitted → under_review → closed. The agent's methodology (normalize data, match line items, calculate variance = physical − system, compute variance percentage, classify by materiality, require segregation of duties) is directly implemented in the `submitSession()`, `resolveLine()`, `approveLargeVariance()`, and `finalizeSession()` methods. The agent's edge-case handling (zero system quantity with positive physical count, negative inventory flagging) informed the variance-percentage edge-case logic in `submitSession()`.

## Project Evidence — Quantitative Summary

### Database State (live, verified via tinker)

| Entity | Count | Details |
|---|---|---|
| Users | 5 | 1 admin, 2 supervisors, 2 agents |
| Products | 20 | 5 categories (Beverages 5, Snacks 4, Groceries 6, Cleaning 4, Dairy 1) |
| Locations | 5 | 2 warehouses (WH-A, WH-B), 3 stores (ST-01, ST-02, ST-03); 4 active, 1 inactive |
| Suppliers | 5 | 4 active, 1 inactive |
| Stock Movements | 9 | 7 stock-in, 2 stock-out |
| Inventory Transactions | 14 | 7 stock_in, 2 stock_out, 5 adjustment_in — 322,000 units received, 6,000 units dispatched, 13,200 units adjusted |
| Reconciliation Sessions | 2 | Both closed, covering North Distribution Center (WH-B) and Mall Kiosk (ST-02) |
| Adjustments | 5 | All adjustment_in, approved by Jordan Supervisor (different from session creator Sarah Supervisor) |
| Current Stock Rows | 12 | 10,000 units of Rice at WH-B; 100,000 units of Fresh Milk at WH-A; 115,000 units of Energy Drink at WH-A; etc. |

### Reconciliation Sessions Closed

**Session #1** — North Distribution Center (WH-B)
- Created by Sarah Supervisor, approved by Jordan Supervisor
- 2 count lines: SNK-001 (1,000 phys → 1,000 variance) and SNK-002 (5,000 phys → 5,000 variance)
- Total absolute variance: 6,000 units
- Both large variances approved and adjusted — status transitioned through draft → in_progress → submitted → under_review → closed

**Session #2** — Mall Kiosk (ST-02)
- Created by Sarah Supervisor, approved by Jordan Supervisor
- 3 count lines: BEV-003 (1,000 phys → 1,000 variance), BEV-004 (5,000 phys → 5,000 variance), BEV-001 (1,200 phys → 1,200 variance)
- Total absolute variance: 7,200 units
- All three required large-variance approval — all approved

### Architecture

```
app/
├── Checks/          (8 agent check classes implementing AgentCheck contract)
├── Contracts/       (AgentCheck interface)
├── Exceptions/      (7 typed reconciliation exceptions)
├── Http/
│   ├── Controllers/ (6 controllers: Auth, Dashboard, Stock, Reconciliation, AgentFindings, Report)
│   └── Middleware/  (RoleMiddleware with audit logging, TrackUserActivity)
├── Jobs/            (RunAgentCheck — dispatchable queue job)
├── Models/          (12 models, 2 with UPDATED_AT = null for append-only)
├── Services/        (InventoryEngine, ReconcilerEngine, AgentOrchestrator, DashboardService)
└── Providers/

resources/views/     (15 Blade views — login, dashboard, stock in/out/index, reconciliation 5 views, agent findings 2 views, reports 1 view, welcome, layout)
database/migrations/ (14 migration files)
database/seeders/    (5 seeders — User, Location, Supplier, Product, Database)
docker/              (Nginx default.conf)
Dockerfile           (PHP 8.4-FPM with intl, zip, pdo_mysql, mbstring, gd, bcmath)
docker-compose.yml   (5 services with health checks and named volumes)
```

### Key Design Decisions

1. **Append-only transactions** — `InventoryTransaction` has `UPDATED_AT = null`. Every stock movement creates exactly one transaction row; nothing is ever mutated. The `current_stock` table is denormalized and updated synchronously within the same DB transaction — it is a read-optimized view, not the source of truth.

2. **Single gatekeeper pattern** — `InventoryEngine` is the only class allowed to modify inventory. StockController delegates all mutations to it. The engine uses pessimistic locking (`lockForUpdate()`) on stock-out to serialize concurrent withdrawals and prevent negative inventory.

3. **Idempotency everywhere** — every stock-in and stock-out generates a unique idempotency key. The engine checks for existing transactions with the same key before creating new ones. Network retries, double-clicks, and queue replays are safe.

4. **4-eyes principle on large variances** — `ReconcilerEngine::approveLargeVariance()` enforces: (a) approver must be a supervisor, (b) approver cannot be the session submitter. Six typed exceptions guard every state transition.

5. **Agent deduplication** — `AgentOrchestrator::runCheck()` skips any finding whose `dedup_hash` matches an existing open finding. An agent can run hourly without flooding the findings table.

6. **Dashboard caching** — `DashboardService` caches widget data in Redis with appropriate TTLs (1 min for findings, 5 min for reconciliation and variance data) and explicit `invalidateCache()` on every stock movement and reconciliation event.

### Files Changed (from initial commit)

- `README.md` — rewritten as Docker Compose operations guide
- `app/Services/ReconcilerEngine.php` — two bug fixes (premature status assignment, missing status transition on approval)
- `slides/pitch.md` — 6-slide pitch deck (~2 minutes)
- `slides/report.md` — this file
