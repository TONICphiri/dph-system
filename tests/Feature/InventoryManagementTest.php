<?php

namespace Tests\Feature;

use App\Models\Encounter;
use App\Models\Inventory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_inventory_index_page_lists_items(): void
    {
        $user = $this->adminUser();
        Inventory::factory()->create(['medication_name' => 'Paracetamol']);
        Inventory::factory()->lowStock()->create(['medication_name' => 'Amoxicillin']);

        $response = $this->actingAs($user)->get('/inventory');

        $response->assertStatus(200)
            ->assertSee('Inventory Management')
            ->assertSee('Paracetamol')
            ->assertSee('Amoxicillin')
            ->assertSee('Low Stock');
    }

    public function test_inventory_item_can_be_created(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post('/inventory', [
            'medication_name' => 'Paracetamol',
            'medication_code' => 'PAR-500',
            'strength' => '500mg',
            'current_stock' => 50,
            'minimum_stock' => 10,
            'maximum_stock' => 100,
            'unit_of_measurement' => 'tablets',
            'expiry_date' => '2027-12-31',
            'unit_price' => 250.00,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('inventory', [
            'medication_name' => 'Paracetamol',
            'current_stock' => 50,
            'status' => 'available',
        ]);
    }

    public function test_inventory_item_can_be_updated(): void
    {
        $user = $this->adminUser();
        $item = Inventory::factory()->create(['medication_name' => 'Old Name', 'current_stock' => 30]);

        $response = $this->actingAs($user)->put("/inventory/{$item->id}", [
            'medication_name' => 'New Name',
            'medication_code' => $item->medication_code,
            'strength' => $item->strength,
            'current_stock' => 20,
            'minimum_stock' => 10,
            'maximum_stock' => 100,
            'unit_of_measurement' => $item->unit_of_measurement,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('inventory', [
            'id' => $item->id,
            'medication_name' => 'New Name',
            'current_stock' => 20,
        ]);
    }

    public function test_inventory_item_can_be_restocked(): void
    {
        $user = $this->adminUser();
        $item = Inventory::factory()->lowStock()->create(['medication_name' => 'Amoxicillin', 'current_stock' => 5]);

        $response = $this->actingAs($user)->post("/inventory/{$item->id}/restock", [
            'restock_quantity' => 40,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('inventory', [
            'id' => $item->id,
            'current_stock' => 45,
            'status' => 'available',
        ]);
    }

    public function test_inventory_restock_flags_low_stock_when_still_below_minimum(): void
    {
        $user = $this->adminUser();
        $item = Inventory::factory()->outOfStock()->create(['medication_name' => 'Coartem', 'current_stock' => 0]);

        $response = $this->actingAs($user)->post("/inventory/{$item->id}/restock", [
            'restock_quantity' => 8,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('inventory', [
            'id' => $item->id,
            'current_stock' => 8,
            'status' => 'low_stock',
        ]);
    }

    public function test_pharmacy_page_only_shows_dispense_for_pending(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->create(['patient_id' => $patient->id, 'status' => 'completed']);
        Prescription::factory()->create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Paracetamol',
            'status' => 'pending',
        ]);
        Prescription::factory()->create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Amoxicillin',
            'status' => 'dispensed',
        ]);

        $response = $this->actingAs($user)->get("/pharmacy/{$patient->id}");

        $response->assertStatus(200);
        $response->assertSee('Dispense');
        $this->assertStringContainsString('Pharmacy', $response->getContent());
    }

    public function test_dispense_denied_for_non_pending_prescription(): void
    {
        $user = $this->adminUser();
        $patient = Patient::factory()->create();
        $encounter = Encounter::factory()->create(['patient_id' => $patient->id, 'status' => 'completed']);
        $dispensed = Prescription::factory()->create([
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'medication_name' => 'Amoxicillin',
            'status' => 'dispensed',
            'dispensed_at' => now(),
        ]);
        $inventory = Inventory::factory()->create(['medication_name' => 'Amoxicillin', 'current_stock' => 50]);

        $response = $this->actingAs($user)->post('/pharmacy/dispense', [
            'patient_id' => $patient->id,
            'prescription_id' => $dispensed->id,
            'quantity_dispensed' => 10,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('inventory', [
            'id' => $inventory->id,
            'current_stock' => 50,
        ]);
    }
}