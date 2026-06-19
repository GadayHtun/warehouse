<?php

namespace Tests\Feature;

use App\Models\CurrentStock;
use App\Models\InventoryTransaction;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryEngineTest extends TestCase
{
    use RefreshDatabase;

    private InventoryEngine $engine;
    private Product $product;
    private Location $location;
    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(InventoryEngine::class);

        $this->product = Product::create([
            'sku' => 'TST-001',
            'name' => 'Test Product',
            'category' => 'Test',
            'unit_of_measure' => 'pcs',
            'min_stock_threshold' => 10,
            'reorder_point' => 50,
            'cost_price' => 5.00,
            'retail_price' => 10.00,
        ]);

        $this->location = Location::create([
            'name' => 'Test Warehouse',
            'code' => 'TST-WH',
            'type' => 'warehouse',
        ]);

        $this->agent = User::create([
            'name' => 'Test Agent',
            'email' => 'agent@test.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);
    }

    public function test_stock_in_creates_movement_and_transaction(): void
    {
        $result = $this->engine->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
        );

        $this->assertNotNull($result['movement']);
        $this->assertNotNull($result['transaction']);
        $this->assertEquals('in', $result['movement']->direction);
        $this->assertEquals(100, $result['movement']->quantity);
        $this->assertEquals('stock_in', $result['transaction']->type);
    }

    public function test_stock_in_updates_current_stock(): void
    {
        $this->engine->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
        );

        $onHand = $this->engine->getOnHand($this->product->id, $this->location->id);
        $this->assertEquals(100, $onHand);
    }

    public function test_multiple_stock_ins_accumulate(): void
    {
        $this->engine->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 50,
            user: $this->agent,
        );

        $this->engine->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 75,
            user: $this->agent,
        );

        $onHand = $this->engine->getOnHand($this->product->id, $this->location->id);
        $this->assertEquals(125, $onHand);
    }

    public function test_stock_out_reduces_inventory(): void
    {
        $this->engine->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
        );

        $this->engine->stockOut(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 30,
            user: $this->agent,
            reason: 'sales',
        );

        $onHand = $this->engine->getOnHand($this->product->id, $this->location->id);
        $this->assertEquals(70, $onHand);
    }

    public function test_stock_out_rejects_when_insufficient_stock(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->engine->stockOut(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
            reason: 'sales',
        );
    }

    public function test_idempotency_key_prevents_duplicate_stock_in(): void
    {
        $key = 'test_idempotency_key_123';

        $first = $this->engine->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
            idempotencyKey: $key,
        );

        $second = $this->engine->stockIn(
            productId: $this->product->id,
            locationId: $this->location->id,
            quantity: 100,
            user: $this->agent,
            idempotencyKey: $key,
        );

        // Should return the same transaction, not create a duplicate
        $this->assertEquals($first['transaction']->id, $second['transaction']->id);
        $this->assertEquals(1, StockMovement::count());
        $this->assertEquals(100, $this->engine->getOnHand($this->product->id, $this->location->id));
    }

    public function test_inventory_transactions_are_append_only(): void
    {
        // Verify the model has no updated_at
        $transaction = new InventoryTransaction();
        $this->assertNull($transaction->getUpdatedAtColumn());
    }
}
