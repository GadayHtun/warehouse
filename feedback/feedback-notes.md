# User Feedback — Warehouse MESM (Inventory Management System)

- **How collected:** Automated UI audit via Playwright browser automation (1 session)
- **When:** 2026-07-06
- **URL:** https://warehouse-mesm.onrender.com
- **Credentials:** admin@warehouse.test / password
- **Pages reviewed:** Login, Dashboard, Stock, Stock In, Stock Out, Reconciliation, Create Reconciliation, Findings

## Raw feedback

### Login Page
1. **CRITICAL: Form action uses HTTP** — The login form action is `http://warehouse-mesm.onrender.com/login` instead of `https://`. This causes a "Form is not secure" error when submitting via modern browsers with mixed content protection.

### Dashboard
2. **Dashboard title says "Supervisor Dashboard"** — But logged in as "Admin User (Admin)". Role mismatch between title and actual user role.

### Stock Page
3. **Stock by Location column is empty** — All 20 products show empty values in the "Stock by Location" column. This is the most critical data display issue.

### Stock In Form
4. Form fields: Product (required), Location (required), Quantity (required), Unit Cost (optional), Supplier (optional), Batch/Lot (optional), Reference Note (optional).

### Stock Out Form
5. Form fields: Product (required), Location (required), Quantity (required), Reason (required), Reference Note (optional).

### Reconciliation
6. Empty state message: "No active reconciliation sessions" with "Start a new session" link.

## Themes (what keeps coming up)

### 🔴 Critical: Mixed Content Security Issue
The entire application is served over HTTPS, but the login form action and all navigation links use HTTP. This triggers browser security warnings and blocks form submission. This is the #1 issue to fix.

### 🟠 High: Missing Search/Filter/Pagination
The Stock table displays 20 products with no way to search, filter by category, or paginate. As the product catalog grows, this will become unusable.

### 🟡 Medium: Inconsistent Role Display
Dashboard title says "Supervisor Dashboard" but user is logged in as "Admin User (Admin)". This suggests the dashboard template doesn't adapt to the user's actual role.

### 🟢 Low: Missing UX Polish
- No loading states during form submission
- No success/error toast messages after operations
- No breadcrumb navigation
- Active nav item not highlighted

## Top 3 things to fix

### 1. Fix Mixed Content (HTTP → HTTPS)
**Priority:** 🔴 P0 — Critical
**Issue:** Login form action and all navigation links use HTTP instead of HTTPS.
**Impact:** Users cannot log in via modern browsers. Security warning displayed.
**Fix:**
```html
<!-- Change form action from HTTP to HTTPS -->
<form action="https://warehouse-mesm.onrender.com/login" method="POST">

<!-- Change all navigation links from HTTP to HTTPS -->
<a href="https://warehouse-mesm.onrender.com">Dashboard</a>
<a href="https://warehouse-mesm.onrender.com/stock">Stock</a>
<!-- etc. -->
```
**Or better:** Use relative URLs (`/stock` instead of `http://warehouse-mesm.onrender.com/stock`).

### 2. Populate Stock by Location Data
**Priority:** 🔴 P0 — Critical
**Issue:** The "Stock by Location" column is empty for all products.
**Impact:** Users cannot see where inventory is stored — the core purpose of the system.
**Fix:**
- Ensure the backend returns stock location data when fetching products
- Check the API endpoint that populates the stock table
- Verify the database has stock records linking products to locations

### 3. Add Current Stock Display in Transaction Forms
**Priority:** 🟠 P1 — High
**Issue:** Stock In/Out forms don't show current stock levels.
**Impact:** Users cannot make informed decisions about stock quantities.
**Fix:**
```javascript
// When product is selected, fetch and display current stock
onProductSelect(productId) {
  const stock = await fetchStockLevels(productId);
  displayCurrentStock(stock.byLocation);
}
```
