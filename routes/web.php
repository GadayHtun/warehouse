<?php

use App\Http\Controllers\AgentFindingsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

// ───────────────────────────────────
// Auth Routes
// ───────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ───────────────────────────────
    // Dashboard (admin + supervisor)
    // ───────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ───────────────────────────────
    // Stock Operations (all roles)
    // ───────────────────────────────
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [StockController::class, 'index'])->name('index');
        Route::get('/in', [StockController::class, 'createIn'])->name('in.create');
        Route::post('/in', [StockController::class, 'storeIn'])->name('in.store');
        Route::get('/out', [StockController::class, 'createOut'])->name('out.create');
        Route::post('/out', [StockController::class, 'storeOut'])->name('out.store');
    });

    // ───────────────────────────────
    // Reconciliation (supervisor only)
    // ───────────────────────────────
    Route::prefix('reconciliation')->name('reconciliation.')->middleware('role:supervisor,admin')->group(function () {
        Route::get('/', [ReconciliationController::class, 'index'])->name('index');
        Route::get('/create', [ReconciliationController::class, 'create'])->name('create');
        Route::post('/', [ReconciliationController::class, 'store'])->name('store');
        Route::get('/{session}/count', [ReconciliationController::class, 'count'])->name('count');
        Route::post('/{session}/count', [ReconciliationController::class, 'addCountLine'])->name('add-count-line');
        Route::post('/{session}/submit', [ReconciliationController::class, 'submit'])->name('submit');
        Route::get('/{session}/review', [ReconciliationController::class, 'review'])->name('review');
        Route::post('/lines/{line}/resolve', [ReconciliationController::class, 'resolveLine'])->name('resolve-line');
        Route::post('/lines/{line}/approve', [ReconciliationController::class, 'approveLargeVariance'])->name('approve-large-variance');
        Route::post('/lines/{line}/reject', [ReconciliationController::class, 'rejectLargeVariance'])->name('reject-large-variance');
        Route::post('/{session}/finalize', [ReconciliationController::class, 'finalize'])->name('finalize');
        Route::get('/{session}', [ReconciliationController::class, 'show'])->name('show');
    });

    // ───────────────────────────────
    // Agent Findings (supervisor only)
    // ───────────────────────────────
    Route::prefix('findings')->name('findings.')->middleware('role:supervisor,admin')->group(function () {
        Route::get('/', [AgentFindingsController::class, 'index'])->name('index');
        Route::get('/{finding}', [AgentFindingsController::class, 'show'])->name('show');
        Route::post('/{finding}/acknowledge', [AgentFindingsController::class, 'acknowledge'])->name('acknowledge');
        Route::post('/{finding}/dismiss', [AgentFindingsController::class, 'dismiss'])->name('dismiss');
    });

    // ───────────────────────────────
    // Reports (supervisor + admin)
    // ───────────────────────────────
    Route::prefix('reports')->name('reports.')->middleware('role:supervisor,admin')->group(function () {
        Route::get('/reconciliation/{session}', [ReportController::class, 'reconciliationReport'])
            ->name('reconciliation');
        Route::get('/stock-movements', [ReportController::class, 'stockMovementLog'])
            ->name('stock-movements');
    });
});
