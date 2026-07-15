<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\InventoryEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function __construct(
        private InventoryEngine $inventory,
    ) {}

    public function index()
    {
        $locations = Location::active()->get();
        $products = Product::query()->with(['currentStock'])->get();

        return view('stock.index', compact('locations', 'products'));
    }

    public function createIn()
    {
        $products = Product::query()->orderBy('name')->get();
        $locations = Location::active()->orderBy('name')->get();
        $suppliers = Supplier::active()->orderBy('name')->get();

        return view('stock.in', compact('products', 'locations', 'suppliers'));
    }

    public function storeIn(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'reference_note' => ['nullable', 'string', 'max:500'],
            'batch_lot' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->inventory->stockIn(
            productId: $validated['product_id'],
            locationId: $validated['location_id'],
            quantity: $validated['quantity'],
            user: Auth::user(),
            unitCost: $validated['unit_cost'] ?? null,
            supplierId: $validated['supplier_id'] ?? null,
            referenceNote: $validated['reference_note'] ?? null,
            batchLot: $validated['batch_lot'] ?? null,
        );

        return redirect()->route('stock.index')
            ->with('success', 'Stock-in recorded. Transaction #' . $result['transaction']->id);
    }

    /**
     * API: return current stock levels for a product, grouped by location.
     */
    public function stockLevels(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $levels = \App\Models\CurrentStock::query()
            ->where('product_id', $request->product_id)
            ->with('location:id,name,code')
            ->get()
            ->map(fn ($s) => [
                'location' => $s->location->code . ' — ' . $s->location->name,
                'quantity' => $s->quantity_on_hand,
            ]);

        return response()->json(['levels' => $levels]);
    }

    public function createOut()
    {
        $products = Product::query()->orderBy('name')->get();
        $locations = Location::active()->orderBy('name')->get();

        return view('stock.out', compact('products', 'locations'));
    }

    public function storeOut(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'in:sales,transfer,internal-use,write-off,return-to-supplier'],
            'reference_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->inventory->stockOut(
                productId: $validated['product_id'],
                locationId: $validated['location_id'],
                quantity: $validated['quantity'],
                user: Auth::user(),
                reason: $validated['reason'],
                referenceNote: $validated['reference_note'] ?? null,
            );

            return redirect()->route('stock.index')
                ->with('success', 'Stock-out recorded. Transaction #' . $result['transaction']->id);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }
    }
}
