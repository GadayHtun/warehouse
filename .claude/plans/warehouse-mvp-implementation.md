# Warehouse MVP Implementation Plan

## Architecture Overview

Monolithic Laravel 12 application with:
- **Presentation**: Blade + Tailwind CSS
- **Application**: Service classes for business logic
- **Domain**: Inventory Engine, Reconciliation Engine, Database Agent
- **Infrastructure**: Eloquent Repositories + MySQL 8 + Redis

## Technology Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Auth | Laravel built-in session guard + middleware | Simple, no OAuth needed for Phase 1 |
| Rate limiting | Laravel RateLimiter (IP-based, 5/15min) | Built-in, zero config |
| Queue | Database queue driver | No external dependency per spec |
| Cache | Redis for dashboard, file for others | Spec-compliant |
| PDF export | barryvdh/laravel-dompdf | Standard Laravel PDF solution |
| Excel export | phpoffice/phpspreadsheet | Standard Laravel Excel solution |
| Testing | PHPUnit + RefreshDatabase trait | Spec-compliant |
| Docker | 4 containers: app, mysql, redis, queue-worker | Spec-compliant |

## Database Schema (23 tables)

### Phase 1A — Foundation (5 tables)

1. **users** — id, name, email, password, role (admin|supervisor|agent), last_active_at, timestamps
2. **sessions** — Laravel default database sessions
3. **products** — id, sku (unique), name, description, category, unit_of_measure, min_stock_threshold, reorder_point, cost_price, retail_price, barcode (nullable), deleted_at, timestamps
4. **locations** — id, name, code (unique), address, type (warehouse|store), status (active|inactive), timestamps
5. **suppliers** — id, name, contact_person, phone, email, status (active|inactive), timestamps

### Phase 1B — Inventory Core (3 tables)

6. **stock_movements** — id, product_id (FK), location_id (FK), user_id (FK), direction (in|out), quantity (decimal 12,3), unit_cost_at_movement (snapshotted), supplier_id (nullable FK), reference_note, batch_lot (nullable), idempotency_key (unique), timestamps
7. **inventory_transactions** — id, product_id (FK), location_id (FK), type (stock_in|stock_out|adjustment_in|adjustment_out), quantity (decimal 12,3), reference_type, reference_id (polymorphic), user_id (FK), idempotency_key (unique), created_at (no updated_at — append-only)
8. **current_stock** — id, product_id (FK), location_id (FK), quantity_on_hand (decimal 12,3), updated_at (denormalized, updated synchronously within same DB transaction)

### Phase 1C — Reconciler (4 tables)

9. **reconciliation_sessions** — id, location_id (FK), user_id (FK), status (draft|in_progress|submitted|under_review|closed), category_filter (nullable), notes, started_at, submitted_at, closed_at, timestamps
10. **reconciliation_count_lines** — id, session_id (FK), product_id (FK), physical_quantity (decimal), system_quantity_at_count (decimal — snapshot at submit), variance (decimal), variance_percentage (decimal), status (pending|resolved|flagged_recount|deferred), resolution_type (accept|recount|defer), resolution_note, large_variance_approval_status (null|pending_approval|approved|rejected), large_variance_approver_id (nullable FK), timestamps
11. **adjustments** — id, count_line_id (FK), inventory_transaction_id (FK), reason (text, min 10 chars), approved_by (nullable FK), approved_at (nullable), timestamps
12. **reconciliation_reports** — id, session_id (FK), file_path, generated_at, timestamps

### Phase 1D — Database Agent (2 tables)

13. **agent_findings** — id, check_type (enum), severity (info|warning|critical), product_id (nullable FK), location_id (nullable FK), title, description (text), detected_at, status (open|acknowledged|dismissed), reviewer_id (nullable FK), reviewed_at (nullable), review_note (nullable text), dedup_hash (unique for deduplication), timestamps
14. **agent_check_runs** — id, check_type, started_at, completed_at, findings_count, status (running|completed|failed)

### Phase 1E — Reports & Audit (2 tables)

15. **audit_logs** — id, user_id (nullable FK), event, entity_type, entity_id, old_values (JSON), new_values (JSON), ip_address, user_agent, created_at (no updated_at)
16. **password_reset_tokens** — Laravel default
17. **jobs** — Laravel database queue
18. **cache** — Laravel database cache
19. **job_batches** — Laravel job batching

### Indexing Strategy
- `inventory_transactions`: composite index on (product_id, location_id, created_at) for on-hand SUM queries
- `current_stock`: unique composite on (product_id, location_id), index on quantity_on_hand for low-stock queries
- `stock_movements`: index on (product_id, location_id, created_at)
- `agent_findings`: composite index on (status, severity, detected_at)
- `audit_logs`: index on (entity_type, entity_id, created_at)
- `reconciliation_count_lines`: index on (session_id, status)

## Implementation Phases

### Phase 1A — Foundation
**Files to create:**
- Docker setup: `docker-compose.yml`, `Dockerfile`, `.env.example`
- Migrations: users, products, locations, suppliers, sessions
- Models: User, Product, Location, Supplier
- Auth: LoginController, RoleMiddleware, AuthService
- Seeders: UserSeeder (admin/supervisor/agent), ProductSeeder (20 products), LocationSeeder (2 warehouses, 3 stores), SupplierSeeder (5 suppliers)
- Views: login.blade.php, layouts/app.blade.php (Tailwind)
- Routes: web.php (auth routes, role-gated dashboard routes)

### Phase 1B — Inventory Core
**Files to create:**
- Migrations: stock_movements, inventory_transactions, current_stock, audit_logs
- Models: StockMovement, InventoryTransaction, CurrentStock, AuditLog
- Services: InventoryEngine (the single gatekeeper for all stock changes, with pessimistic locking), StockService (stock-in/stock-out orchestration)
- Controllers: StockInController, StockOutController
- Middleware: TrackUserActivity
- Views: stock/in.blade.php, stock/out.blade.php, stock/index.blade.php
- Tests: InventoryEngineTest, StockServiceTest, StockInOutTest

### Phase 1C — Reconciler
**Files to create:**
- Migrations: reconciliation_sessions, reconciliation_count_lines, adjustments, reconciliation_reports
- Models: ReconciliationSession, ReconciliationCountLine, Adjustment, ReconciliationReport
- Services: ReconcilerEngine (session lifecycle, variance calculation using variance-analysis skill methodology, adjustment creation)
- Controllers: ReconciliationController
- Views: reconciliation/index.blade.php, create.blade.php, count.blade.php, review.blade.php, show.blade.php
- Policies: ReconciliationPolicy (only supervisor can manage)
- Jobs: GenerateReconciliationReport
- Tests: ReconcilerEngineTest, ReconciliationWorkflowTest

### Phase 1D — Database Agent
**Files to create:**
- Migrations: agent_findings, agent_check_runs
- Models: AgentFinding, AgentCheckRun
- Contracts: AgentCheck interface with `run(): array<Finding>`
- Checks (8 classes): VarianceDriftCheck, DormantStockCheck, NegativeStockCheck, RapidDepletionCheck, ReconciliationStalenessCheck, DuplicateMovementsCheck, UnbalancedTransfersCheck, PriceAnomalyCheck
- Services: AgentOrchestrator (runs checks, deduplicates findings)
- Controllers: AgentFindingsController
- Views: agent-findings/index.blade.php, show.blade.php
- Console: Kernel.php (schedule checks at configured frequencies)
- Jobs: RunAgentCheck
- Tests: Each check class test, AgentOrchestratorTest

### Phase 1E — Dashboard & Reports
**Files to create:**
- Controllers: DashboardController, ReportController
- Services: DashboardService (cached aggregates), ReportService
- Views: dashboard/index.blade.php, reports/*.blade.php
- Exports: InventoryStatusExport, StockMovementExport, AgentFindingsExport
- Jobs: GenerateLargeReport
- Tests: DashboardServiceTest, ReportServiceTest

## Agent Delegation Plan

I will use the following agents for parallel work:

1. **database-engineer agent**: Design all migrations with proper constraints, indexes, and types
2. **inventory-reconciler agent**: Design ReconcilerEngine service with variance calculation methodology aligned with the variance-analysis skill
3. **Main thread**: Controllers, views, routes, Docker setup, tests

## Variance Analysis Integration

The variance-analysis skill methodologies will be baked into:
- `ReconcilerEngine::calculateVariance()` — Price/Volume decomposition adapted for inventory (physical vs system)
- Materiality thresholds: 5% or 50 units (configurable)
- Variance trending in agent checks (VarianceDriftCheck)
- Dashboard variance-at-a-glance widget with waterfall-style breakdown

## Files to Create (~75+ files)

The implementation will be organized as:
```
app/
├── Models/ (10 models)
├── Services/ (8 services)
├── Http/Controllers/ (8 controllers)
├── Http/Middleware/ (2 middleware)
├── Contracts/ (1 interface)
├── Checks/ (8 agent check classes)
├── Exports/ (3 export classes)
├── Policies/ (2 policies)
├── Jobs/ (2 jobs)
database/
├── migrations/ (16 migrations)
├── seeders/ (5 seeders)
resources/views/ (~15 Blade templates)
routes/web.php
tests/ (~10 test files)
docker-compose.yml
Dockerfile
```

## Success Criteria Mapping

| Spec Metric | Implementation Check |
|---|---|
| Inventory variance < 1% | Reconciliation engine with dual-approval for large variances |
| 100% traceable stock movements | Append-only `inventory_transactions` + audit_logs |
| Report generation < 10s | Synchronous for standard range, queued for large ranges |
| All migrations execute | Tested via `php artisan migrate:fresh` |
| Seeder populates demo data | UserSeeder, ProductSeeder, LocationSeeder, SupplierSeeder |
| Docker starts successfully | 4-container compose file |
| No duplicated transactions | Idempotency key on stock_movements and inventory_transactions |

## Implementation Order

1. Docker + Laravel scaffold + .env
2. Phase 1A migrations + models + seeders
3. Auth (login, logout, role middleware)
4. Master data CRUD + views
5. Phase 1B migrations + models
6. Inventory Engine service (core)
7. Stock In/Out services + controllers + views
8. Audit log infrastructure
9. Phase 1C migrations + models
10. Reconciler Engine service
11. Reconciliation controllers + views
12. Phase 1D migrations + models
13. Agent Check interface + 8 checks
14. Agent Orchestrator + scheduler
15. Agent findings controllers + views
16. Phase 1E dashboard + reports
17. Tests across all phases
18. Docker finalization + demo data
