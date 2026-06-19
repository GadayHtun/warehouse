<?php

namespace Tests\Feature;

use App\Exceptions\Reconciliation\LargeVarianceRequiresApprovalException;
use App\Exceptions\Reconciliation\ReasonTooShortException;
use App\Models\CurrentStock;
use App\Models\Location;
use App\Models\Product;
use App\Models\ReconciliationCountLine;
use App\Models\User;
use App\Services\InventoryEngine;
use App\Services\ReconcilerEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcilerEngineTest extends TestCase
{
    use RefreshDatabase;

    private ReconcilerEngine $reconciler;
    private InventoryEngine $inventory;
    private Product $product;
    private Location $location;
    private User $supervisor;
    private User $supervisor2;
    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reconciler = app(ReconcilerEngine::class);
        $this->inventory = app(InventoryEngine::class);

        $this->product = Product::create([
            'sku' => 'REC-TEST-001',
            'name' => 'Reconciliation Test Product',
            'category' => 'Test',
            'unit_of_measure' => 'pcs',
            'min_stock_threshold' => 10,
            'reorder_point' => 50,
            'cost_price' => 10.00,
            'retail_price' => 20.00,
        ]);

        $this->location = Location::create([
            'name' => 'Test Location',
            'code' => 'REC-LOC',
            'type' => 'warehouse',
        ]);

        $this->supervisor = User::create([
            'name' => 'Supervisor One',
            'email' => 'sup1@test.com',
            'password' => bcrypt('password'),
            'role' => 'supervisor',
        ]);

        $this->supervisor2 = User::create([
            'name' => 'Supervisor Two',
            'email' => 'sup2@test.com',
            'password' => bcrypt('password'),
            'role' => 'supervisor',
        ]);

        $this->agent = User::create([
            'name' => 'Agent',
            'email' => 'agent@test.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);
    }

    public function test_initiate_session_creates_draft(): void
    {
        $session = $this->reconciler->initiateSession(
            locationId: $this->location->id,
            user: $this->supervisor,
        );

        $this->assertEquals('draft', $session->status);
        $this->assertNotNull($session->started_at);
    }

    public function test_add_count_line_transitions_draft_to_in_progress(): void
    {
        $session = $this->reconciler->initiateSession(
            locationId: $this->location->id,
            user: $this->supervisor,
        );

        $this->reconciler->addCountLine(
            session: $session,
            productId: $this->product->id,
            physicalQuantity: 50,
        );

        $session->refresh();
        $this->assertEquals('in_progress', $session->status);
    }

    public function test_submit_session_calculates_variance(): void
    {
        // Set up system stock: 100 units
        $this->inventory->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
        );

        $session = $this->reconciler->initiateSession(
            locationId: $this->location->id,
            user: $this->supervisor,
        );

        $this->reconciler->addCountLine(
            session: $session,
            productId: $this->product->id,
            physicalQuantity: 90, // Physical count is 90, system says 100 = -10 variance
        );

        $session = $this->reconciler->submitSession($session);

        $this->assertEquals('submitted', $session->status);
        $this->assertNotNull($session->submitted_at);

        $line = $session->countLines()->first();
        $this->assertEquals(-10, $line->variance);
        $this->assertEquals(-10.0, $line->variance_percentage);
        $this->assertEquals(100, $line->system_quantity_at_count);
    }

    public function test_resolve_line_with_small_variance_creates_adjustment(): void
    {
        $this->inventory->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
        );

        $session = $this->reconciler->initiateSession(
            locationId: $this->location->id,
            user: $this->supervisor,
        );

        $this->reconciler->addCountLine(
            session: $session,
            productId: $this->product->id,
            physicalQuantity: 98, // Small variance: -2 (2% of 100, under 5%)
        );

        $session = $this->reconciler->submitSession($session);
        $line = $session->countLines()->first();

        $line = $this->reconciler->resolveLine(
            line: $line,
            resolutionType: 'accept',
            resolutionNote: 'Accepted small variance after recount confirmation',
            user: $this->supervisor,
        );

        $this->assertEquals('resolved', $line->status);
        $this->assertEquals('accept', $line->resolution_type);

        // Check adjustment was created
        $adjustment = $line->adjustment()->first();
        $this->assertNotNull($adjustment);
        $this->assertEquals('Accepted small variance after recount confirmation', $adjustment->reason);
    }

    public function test_large_variance_requires_approval(): void
    {
        $this->inventory->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
        );

        $session = $this->reconciler->initiateSession(
            locationId: $this->location->id,
            user: $this->supervisor,
        );

        $this->reconciler->addCountLine(
            session: $session,
            productId: $this->product->id,
            physicalQuantity: 60, // Large variance: -40 (40% of 100, over 5%)
        );

        $session = $this->reconciler->submitSession($session);
        $line = $session->countLines()->first();

        $this->expectException(LargeVarianceRequiresApprovalException::class);

        $this->reconciler->resolveLine(
            line: $line,
            resolutionType: 'accept',
            resolutionNote: 'Large variance accepted pending second approval',
            user: $this->supervisor,
        );
    }

    public function test_large_variance_approval_by_second_supervisor(): void
    {
        $this->inventory->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
        );

        $session = $this->reconciler->initiateSession(
            locationId: $this->location->id,
            user: $this->supervisor,
        );

        $this->reconciler->addCountLine(
            session: $session,
            productId: $this->product->id,
            physicalQuantity: 60,
        );

        $session = $this->reconciler->submitSession($session);
        $line = $session->countLines()->first();

        // First supervisor tries to resolve — gets exception for large variance
        try {
            $this->reconciler->resolveLine(
                line: $line,
                resolutionType: 'accept',
                resolutionNote: 'Large variance accepted pending second approval',
                user: $this->supervisor,
            );
        } catch (LargeVarianceRequiresApprovalException) {
            // Expected — line should now be in pending_approval
        }

        $line->refresh();
        $this->assertEquals('pending_approval', $line->large_variance_approval_status);

        // Second supervisor approves
        $adjustment = $this->reconciler->approveLargeVariance(
            line: $line,
            approver: $this->supervisor2,
        );

        $this->assertNotNull($adjustment);
        $line->refresh();
        $this->assertEquals('approved', $line->large_variance_approval_status);
    }

    public function test_reason_too_short_throws_exception(): void
    {
        $this->inventory->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
        );

        $session = $this->reconciler->initiateSession(
            locationId: $this->location->id,
            user: $this->supervisor,
        );

        $this->reconciler->addCountLine(
            session: $session,
            productId: $this->product->id,
            physicalQuantity: 98,
        );

        $session = $this->reconciler->submitSession($session);
        $line = $session->countLines()->first();

        $this->expectException(ReasonTooShortException::class);

        $this->reconciler->resolveLine(
            line: $line,
            resolutionType: 'accept',
            resolutionNote: 'short', // Only 5 chars, minimum is 10
            user: $this->supervisor,
        );
    }

    public function test_finalize_session(): void
    {
        $this->inventory->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
        );

        $session = $this->reconciler->initiateSession(
            locationId: $this->location->id,
            user: $this->supervisor,
        );

        $this->reconciler->addCountLine(
            session: $session,
            productId: $this->product->id,
            physicalQuantity: 100, // Perfect match — zero variance
        );

        $session = $this->reconciler->submitSession($session);
        $line = $session->countLines()->first();

        $this->reconciler->resolveLine(
            line: $line,
            resolutionType: 'accept',
            resolutionNote: 'Count matches system exactly',
            user: $this->supervisor,
        );

        $session = $this->reconciler->finalizeSession($session);

        $this->assertEquals('closed', $session->status);
        $this->assertNotNull($session->closed_at);
    }

    public function test_get_session_summary_includes_financial_impact(): void
    {
        $this->inventory->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
        );

        $session = $this->reconciler->initiateSession(
            locationId: $this->location->id,
            user: $this->supervisor,
        );

        $this->reconciler->addCountLine(
            session: $session,
            productId: $this->product->id,
            physicalQuantity: 80, // -20 variance × $10 cost = -$200
        );

        $session = $this->reconciler->submitSession($session);
        $summary = $this->reconciler->getSessionSummary($session);

        $this->assertEquals(1, $summary['total_lines']);
        $this->assertEquals(-20, $summary['total_variance_units']);
        $this->assertEquals(-200.0, $summary['net_financial_impact']);
    }
}
