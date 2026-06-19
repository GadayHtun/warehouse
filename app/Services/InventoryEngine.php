<?php

namespace App\Services;

use App\Models\CurrentStock;
use App\Models\InventoryTransaction;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The single gatekeeper for all inventory stock changes.
 * No controller, job, or command may modify inventory tables directly —
 * all paths must go through this service.
 *
 * Every stock change creates exactly one row in inventory_transactions (append-only)
 * and updates the denormalized current_stock table within the same DB transaction.
 */
class InventoryEngine
{
    /**
     * Record stock coming into a location.
     */
    public function stockIn(
        int $productId,
        int $locationId,
        float $quantity,
        User $user,
        ?float $unitCost = null,
        ?int $supplierId = null,
        ?string $referenceNote = null,
        ?string $batchLot = null,
        ?string $idempotencyKey = null,
    ): array {
        $idempotencyKey ??= $this->generateIdempotencyKey('in', $productId, $locationId, $quantity);

        return DB::transaction(function () use (
            $productId, $locationId, $quantity, $user,
            $unitCost, $supplierId, $referenceNote, $batchLot, $idempotencyKey,
        ) {
            // Idempotency check
            if (InventoryTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
                return $this->existingResult($idempotencyKey);
            }

            $movement = StockMovement::create([
                'product_id' => $productId,
                'location_id' => $locationId,
                'user_id' => $user->id,
                'direction' => 'in',
                'quantity' => $quantity,
                'unit_cost_at_movement' => $unitCost,
                'supplier_id' => $supplierId,
                'reference_note' => $referenceNote,
                'batch_lot' => $batchLot,
                'idempotency_key' => $idempotencyKey,
            ]);

            $transaction = $this->createTransaction(
                $productId, $locationId, 'stock_in', $quantity,
                StockMovement::class, $movement->id, $user->id,
                $idempotencyKey,
            );

            $this->updateCurrentStock($productId, $locationId, $quantity, 'in');

            return compact('movement', 'transaction');
        });
    }

    /**
     * Record stock leaving a location.
     * Uses pessimistic locking to prevent race conditions on concurrent stock-outs.
     */
    public function stockOut(
        int $productId,
        int $locationId,
        float $quantity,
        User $user,
        string $reason,
        ?string $referenceNote = null,
        ?string $idempotencyKey = null,
    ): array {
        $idempotencyKey ??= $this->generateIdempotencyKey('out', $productId, $locationId, $quantity);

        return DB::transaction(function () use (
            $productId, $locationId, $quantity, $user,
            $reason, $referenceNote, $idempotencyKey,
        ) {
            if (InventoryTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
                return $this->existingResult($idempotencyKey);
            }

            // Pessimistic lock to serialize concurrent stock-outs on same product-location
            $currentStock = CurrentStock::query()
                ->where('product_id', $productId)
                ->where('location_id', $locationId)
                ->lockForUpdate()
                ->first();

            $onHand = $currentStock?->quantity_on_hand ?? 0;

            if (($onHand - $quantity) < 0) {
                throw new \RuntimeException(
                    "Insufficient stock for product #{$productId} at location #{$locationId}. " .
                    "On hand: {$onHand}, requested: {$quantity}."
                );
            }

            $movement = StockMovement::create([
                'product_id' => $productId,
                'location_id' => $locationId,
                'user_id' => $user->id,
                'direction' => 'out',
                'quantity' => $quantity,
                'reference_note' => trim("{$reason}: {$referenceNote}"),
                'idempotency_key' => $idempotencyKey,
            ]);

            $transaction = $this->createTransaction(
                $productId, $locationId, 'stock_out', $quantity,
                StockMovement::class, $movement->id, $user->id,
                $idempotencyKey,
            );

            $this->updateCurrentStock($productId, $locationId, $quantity, 'out');

            return compact('movement', 'transaction');
        });
    }

    /**
     * Get current on-hand quantity for a product at a location.
     */
    public function getOnHand(int $productId, int $locationId): float
    {
        $stock = CurrentStock::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();

        return $stock?->quantity_on_hand ?? 0.0;
    }

    /**
     * Get all products below reorder point at a location.
     */
    public function getLowStockProducts(int $locationId): array
    {
        return CurrentStock::query()
            ->where('location_id', $locationId)
            ->whereHas('product', function ($q) {
                $q->whereRaw('current_stock.quantity_on_hand < products.reorder_point');
            })
            ->with('product')
            ->get()
            ->toArray();
    }

    private function createTransaction(
        int $productId,
        int $locationId,
        string $type,
        float $quantity,
        string $referenceType,
        int $referenceId,
        int $userId,
        string $idempotencyKey,
    ): InventoryTransaction {
        return InventoryTransaction::create([
            'product_id' => $productId,
            'location_id' => $locationId,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => $userId,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    private function updateCurrentStock(int $productId, int $locationId, float $quantity, string $direction): void
    {
        $stock = CurrentStock::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->first();

        if ($stock) {
            if ($direction === 'in') {
                $stock->quantity_on_hand += $quantity;
            } else {
                $stock->quantity_on_hand -= $quantity;
            }
            $stock->save();
        } else {
            CurrentStock::create([
                'product_id' => $productId,
                'location_id' => $locationId,
                'quantity_on_hand' => $direction === 'in' ? $quantity : -$quantity,
            ]);
        }
    }

    private function generateIdempotencyKey(string $prefix, int $productId, int $locationId, float $quantity): string
    {
        return $prefix . '_' . $productId . '_' . $locationId . '_' . time() . '_' . bin2hex(random_bytes(4));
    }

    private function existingResult(string $key): array
    {
        $transaction = InventoryTransaction::where('idempotency_key', $key)->first();
        $movement = StockMovement::where('idempotency_key', $key)->first();
        return compact('movement', 'transaction');
    }
}
