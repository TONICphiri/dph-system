<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of inventory items
     */
    public function index(Request $request)
    {
        $this->authorize('manage_inventory');

        $query = Inventory::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('medication_name', 'like', "%{$search}%")
                  ->orWhere('medication_code', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $items = $query->orderBy('medication_name')->paginate(15);

        $stats = [
            'available' => Inventory::where('status', 'available')->count(),
            'lowStock' => Inventory::where('status', 'low_stock')->count(),
            'outOfStock' => Inventory::where('status', 'out_of_stock')->count(),
            'expired' => Inventory::where('status', 'expired')->count(),
        ];

        return view('inventory.index', compact('items', 'stats'));
    }

    /**
     * Show form to create a new inventory item
     */
    public function create()
    {
        $this->authorize('manage_inventory');

        return view('inventory.create');
    }

    /**
     * Store a newly created inventory item
     */
    public function store(Request $request)
    {
        $this->authorize('manage_inventory');

        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'medication_code' => 'nullable|string|max:255',
            'strength' => 'nullable|string|max:255',
            'current_stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'required|integer|min:1',
            'unit_of_measurement' => 'required|string|max:255',
            'expiry_date' => 'nullable|date',
            'unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $item = Inventory::create(array_merge($validated, [
                'facility_id' => $this->user()->facility_id,
                'status' => 'available',
                'last_restocked_at' => now(),
                'last_restocked_by_user_id' => $this->user()->id,
            ]));
            $item->updateStatus();

            DB::commit();

            return redirect()->route('inventory.index')
                ->with('success', "Inventory item '{$item->medication_name}' added successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Inventory creation failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to add inventory item. Please try again.');
        }
    }

    /**
     * Show form to edit an inventory item
     */
    public function edit(Inventory $item)
    {
        $this->authorize('manage_inventory');

        return view('inventory.edit', compact('item'));
    }

    /**
     * Update an inventory item
     */
    public function update(Request $request, Inventory $item)
    {
        $this->authorize('manage_inventory');

        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'medication_code' => 'nullable|string|max:255',
            'strength' => 'nullable|string|max:255',
            'current_stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'required|integer|min:1',
            'unit_of_measurement' => 'required|string|max:255',
            'expiry_date' => 'nullable|date',
            'unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $item->update($validated);
            $item->updateStatus();

            return redirect()->route('inventory.index')
                ->with('success', "Inventory item '{$item->medication_name}' updated successfully");
        } catch (\Exception $e) {
            \Log::error('Inventory update failed', [
                'error' => $e->getMessage(),
                'item_id' => $item->id,
            ]);

            return redirect()->back()->with('error', 'Failed to update inventory item. Please try again.');
        }
    }

    /**
     * Restock an inventory item
     */
    public function restock(Request $request, Inventory $item)
    {
        $this->authorize('manage_inventory');

        $validated = $request->validate([
            'restock_quantity' => 'required|integer|min:1',
        ]);

        try {
            $item->increment('current_stock', $validated['restock_quantity']);
            $item->update([
                'last_restocked_at' => now(),
                'last_restocked_by_user_id' => $this->user()->id,
            ]);
            $item->updateStatus();

            return redirect()->route('inventory.index')
                ->with('success', "Restocked '{$item->medication_name}' by {$validated['restock_quantity']} {$item->unit_of_measurement}");
        } catch (\Exception $e) {
            \Log::error('Inventory restock failed', [
                'error' => $e->getMessage(),
                'item_id' => $item->id,
            ]);

            return redirect()->back()->with('error', 'Failed to restock item. Please try again.');
        }
    }
}
