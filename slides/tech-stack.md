---
marp: true
---

# Tech Stack
### How Warehouse Was Built

A full-stack inventory control platform — containerized, agent-driven, and built with Claude Code.

---

## Application Stack

| Layer | Technology | Purpose |
|---|---|---|
| **Framework** | Laravel 12, PHP 8.4-FPM | MVC architecture, Eloquent ORM, Artisan CLI |
| **Database** | MySQL 8.0 | Row-level locking, transactions, strict mode |
| **Cache / Queue** | Redis 7 | Dashboard caching (1–5 min TTL), job dispatch |
| **Web Server** | Nginx (Alpine) | FastCGI proxy to PHP-FPM, static assets |
| **Frontend** | Blade + Tailwind CSS | Server-rendered UI, zero build step |
| **Reports** | DomPDF, CSV streaming | Downloadable reconciliation reports |
| **Orchestration** | Docker Compose | 5 services with health checks and named volumes |

> `docker compose up -d --build` — one command, five services, fully containerized.

---

## Docker Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Docker Compose (5 services)               │
│                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐  │
│  │  Nginx       │───▶│  PHP-FPM     │───▶│  MySQL 8.0   │  │
│  │  (Alpine)    │    │  (PHP 8.4)   │    │  (port 3307) │  │
│  │  port 8080   │    │  port 9000   │    │              │  │
│  └──────────────┘    └──────┬───────┘    └──────────────┘  │
│                             │                                │
│                      ┌──────▼───────┐    ┌──────────────┐  │
│                      │  Queue Worker│    │  Redis 7     │  │
│                      │  (artisan)   │    │  (port 6380) │  │
│                      └──────────────┘    └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

Health checks ensure MySQL and Redis are ready before the app starts. Named volumes persist data across restarts.

---

## Claude Code — AI Development Platform

### MCP Servers (Model Context Protocol)

| Server | Package | Purpose |
|---|---|---|
| **Context7** | `@upstash/context7-mcp` | Real-time library docs and code examples for Laravel, PHP, Redis |
| **GitHub** | `api.githubcopilot.com/mcp/` | Repository operations, PR management, issue tracking |
| **Chrome DevTools** | `@anthropic-ai/chrome-devtools-mcp` | Browser automation, screenshots, performance auditing |

### Enabled Plugins

| Plugin | Function |
|---|---|
| `context7` | Pull up-to-date documentation during development |
| `github` | Git operations and GitHub API integration |
| `code-review` | Automated code review with severity ranking |
| `frontend-design` | UI/UX guidance for Blade + Tailwind components |
| `claude-code-setup` | Project scaffolding and configuration |
| `mcp-server-dev` | Custom MCP server development |

---

## Custom Agents

### Database Engineer
` .claude/agents/database-engineer.md`

| Attribute | Detail |
|---|---|
| **Role** | Senior Database Engineer (20 years experience) |
| **Specialization** | PostgreSQL, MySQL, MongoDB, Redis, schema design |
| **Responsibilities** | Migration design, indexing strategy, query optimization, data integrity |

**Impact on project:**
- Designed 14 migration files across 13 tables
- Schema decisions: append-only `inventory_transactions` (no `updated_at`), denormalized `current_stock` with pessimistic locking, polymorphic reference system
- Naming convention: explicit constraint names, snake_case, plural tables

---

### Inventory Reconciler
`.claude/agents/inventory-reconciler.md`

| Attribute | Detail |
|---|---|
| **Role** | Senior Inventory Reconciliation Specialist (20+ years) |
| **Specialization** | Supply chain auditing, variance analysis, cycle counts |
| **Responsibilities** | Reconciliation methodology, root cause investigation, corrective recommendations |

**Impact on project:**
- Designed the 6-stage reconciliation pipeline: `draft → in_progress → submitted → under_review → closed`
- Methodology encoded into `ReconcilerEngine`: normalize data, match line items, calculate variance (physical − system), classify by materiality
- Edge-case handling: zero system quantity, negative inventory flagging, segregation of duties enforcement

---

## Skills Used

### Variance Analysis
`.claude/skills/variance-analysis/SKILL.md`

| Component | Application |
|---|---|
| **Materiality Thresholds** | 5% or 50 units → large variance requiring supervisor approval |
| **Investigation Priority** | Largest absolute dollar variance first (from skill's ordering framework) |
| **Waterfall Methodology** | Net financial impact breakdown in `getSessionSummary()` |
| **Variance Decomposition** | Units × percentage × dollar impact per reconciliation line |

**Encoded into code:**
- `ReconcilerEngine::LARGE_VARIANCE_PERCENTAGE_THRESHOLD = 5%`
- `ReconcilerEngine::LARGE_VARIANCE_UNITS_THRESHOLD = 50`
- `getSessionSummary()` output structure matches skill's framework

---

## Methodology

### Project-Based Development

Phased implementation with incremental commits:

| Phase | Scope | Components |
|---|---|---|
| **1A** | Foundation | Users, Products, Locations, Suppliers — 5 migrations, 5 seeders |
| **1B** | Inventory Core | Stock movements, append-only transactions, denormalized current stock |
| **1C** | Reconciliation | 6-stage pipeline, large-variance guard, typed exceptions |
| **1D** | Agent System | 8 autonomous checks, orchestrator, deduplication, findings dashboard |

### Design Patterns

| Pattern | Implementation |
|---|---|
| **Single Gatekeeper** | `InventoryEngine` — only class allowed to modify inventory |
| **Append-Only Ledger** | `InventoryTransaction` with `UPDATED_AT = null` — immutable audit trail |
| **Idempotency Keys** | Every stock-in/out generates unique key; retries are safe |
| **4-Eyes Principle** | Large variances require a different supervisor to approve |
| **Agent Deduplication** | Content hash prevents duplicate findings across runs |

---

## Key Commands

### Setup & Deployment

```bash
# Clone and start
git clone <repo-url> && cd warehouse
cp .env.example .env
docker compose up -d --build

# Database setup
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

### Development Workflow

```bash
# Artisan commands
docker compose exec app php artisan make:model Product
docker compose exec app php artisan route:list
docker compose exec app php artisan tinker

# Queue management
docker compose restart queue-worker

# Logs
docker compose logs -f app
docker compose logs -f queue
```

### Database Access

```bash
# Direct MySQL connection (host: 127.0.0.1, port: 3307)
# Database: warehouse | User: warehouse | Password: warehouse_secret
docker compose exec app php artisan tinker
```

---

## Architecture Summary

```
app/
├── Checks/          8 agent check classes (AgentCheck contract)
├── Contracts/       AgentCheck interface
├── Exceptions/      7 typed reconciliation exceptions
├── Http/
│   ├── Controllers/ 6 controllers (Auth, Dashboard, Stock, Reconciliation, AgentFindings, Report)
│   └── Middleware/   RoleMiddleware + TrackUserActivity
├── Jobs/            RunAgentCheck (dispatchable queue job)
├── Models/          12 models (2 append-only with UPDATED_AT = null)
├── Services/        InventoryEngine, ReconcilerEngine, AgentOrchestrator, DashboardService
└── Providers/

resources/views/     15 Blade views
database/migrations/ 14 migration files
database/seeders/    5 seeders
docker/              Nginx default.conf
Dockerfile           PHP 8.4-FPM
docker-compose.yml   5 services with health checks
```

> Every business logic mutation goes through one of three service classes. No controller, job, or command touches inventory tables directly.
