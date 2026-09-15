<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Services\SyncService;
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

        $me = $this->user();
        $query = $me->scopeToFacility(Inventory::query());

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('medication_name', 'like', "%{$search}%")
                  ->orWhere('medication_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $items = $query->orderBy('medication_name')->paginate(15);

        $statsQuery = fn () => $me->scopeToFacility(Inventory::query());
        $stats = [
            'available' => $statsQuery()->where('status', 'available')->count(),
            'lowStock' => $statsQuery()->where('status', 'low_stock')->count(),
            'outOfStock' => $statsQuery()->where('status', 'out_of_stock')->count(),
            'expired' => $statsQuery()->where('status', 'expired')->count(),
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
            'medication_code' => 'nullable|string|max:50',
            'strength' => 'nullable|string|max:100',
            'current_stock' => 'required|integer|min:0|max:1000000',
            'minimum_stock' => 'required|integer|min:0|max:1000000',
            'maximum_stock' => 'required|integer|min:1|max:1000000|gte:minimum_stock',
            'unit_of_measurement' => 'required|string|max:50',
            'expiry_date' => 'nullable|date|after:today',
            'unit_price' => 'nullable|numeric|min:0|max:1000000',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (!$this->user()->facility_id && !$this->user()->isNationalAdmin()) {
            return redirect()->back()->with('error', 'Your account is not linked to a facility.')->withInput();
        }

        try {
            DB::beginTransaction();

            $item = Inventory::create(array_merge($validated, [
                'facility_id' => $this->user()->facility_id,
                'status' => 'available',
                'last_restocked_at' => now(),
                'last_restocked_by_user_id' => $this->user()->id,
            ]));
            $item->updateStatus();

            SyncService::enqueue('inventory', $item, 'create', $item->facility_id);

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
        $this->ensureSameFacility($item);

        return view('inventory.edit', compact('item'));
    }

    /**
     * Update an inventory item
     */
    public function update(Request $request, Inventory $item)
    {
        $this->authorize('manage_inventory');
        $this->ensureSameFacility($item);

        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'medication_code' => 'nullable|string|max:50',
            'strength' => 'nullable|string|max:100',
            'current_stock' => 'required|integer|min:0|max:1000000',
            'minimum_stock' => 'required|integer|min:0|max:1000000',
            'maximum_stock' => 'required|integer|min:1|max:1000000|gte:minimum_stock',
            'unit_of_measurement' => 'required|string|max:50',
            'expiry_date' => 'nullable|date|after:today',
            'unit_price' => 'nullable|numeric|min:0|max:1000000',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $item->update($validated);
            $item->updateStatus();

            SyncService::enqueue('inventory', $item, 'update', $item->facility_id);

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
        $this->ensureSameFacility($item);

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

            SyncService::enqueue('inventory', $item, 'update', $item->facility_id);

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

    /**
     * Local inventory control: facility-scoped users may only touch
     * stock belonging to their own facility.
     */
    protected function ensureSameFacility(Inventory $item): void
    {
        $me = $this->user();

        if (!$me->isNationalAdmin() && (int) $item->facility_id !== (int) $me->facility_id) {
            abort(403, 'You can only manage inventory for your own facility.');
        }
    }
}
