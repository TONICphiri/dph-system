{{-- Repeating prescription lines. Choosing a stocked medicine is preferred; a name can be typed when it is not stocked. --}}
@php
    $rows = old('items', []);
@endphp
<div x-data="repeater(@js($rows), { medicine_id: '', medicine_name: '', dosage: '', frequency: '', duration_days: '', quantity: '', instructions: '' })">
    <template x-for="(row, index) in rows" :key="index">
        <div class="grid gap-3 border-b border-line px-5 py-4 sm:grid-cols-12">
            <div class="sm:col-span-6">
                <label class="label" :for="'item_medicine_' + index">Medicine</label>
                <select class="input" :id="'item_medicine_' + index" :name="'items[' + index + '][medicine_id]'" x-model="row.medicine_id">
                    <option value="">Not in stock, type the name</option>
                    @foreach ($medicines as $medicine)
                        <option value="{{ $medicine->id }}">{{ $medicine->displayName() }} ({{ $medicine->stock_quantity }} in stock)</option>
                    @endforeach
                </select>
                <input x-show="!row.medicine_id" class="input mt-2" :name="'items[' + index + '][medicine_name]'" x-model="row.medicine_name" placeholder="Medicine name and strength" aria-label="Medicine name">
            </div>
            <div class="sm:col-span-3"><label class="label" :for="'item_dosage_' + index">Dosage</label><input class="input" :id="'item_dosage_' + index" :name="'items[' + index + '][dosage]'" x-model="row.dosage" placeholder="2 tablets"></div>
            <div class="sm:col-span-3">
                <label class="label" :for="'item_frequency_' + index">How often</label>
                <select class="input" :id="'item_frequency_' + index" :name="'items[' + index + '][frequency]'" x-model="row.frequency">
                    <option value="">Choose</option>
                    @foreach ($frequencies as $frequency)<option value="{{ $frequency }}">{{ $frequency }}</option>@endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:col-span-4">
                <div><label class="label" :for="'item_days_' + index">Days</label><input type="number" min="1" max="365" class="input" :id="'item_days_' + index" :name="'items[' + index + '][duration_days]'" x-model="row.duration_days"></div>
                <div><label class="label" :for="'item_quantity_' + index">Quantity</label><input type="number" min="1" class="input" :id="'item_quantity_' + index" :name="'items[' + index + '][quantity]'" x-model="row.quantity"></div>
            </div>
            <div class="flex items-end gap-2 sm:col-span-8">
                <div class="flex-1"><label class="label" :for="'item_instructions_' + index">Instructions</label><input class="input" :id="'item_instructions_' + index" :name="'items[' + index + '][instructions]'" x-model="row.instructions" placeholder="After meals"></div>
                <button type="button" class="btn-secondary btn-sm mb-0.5" @click="remove(index)" x-show="rows.length > 1" aria-label="Remove medicine"><x-icon name="x" class="h-4 w-4" /></button>
            </div>
        </div>
    </template>
    @if ($errors->has('items') || $errors->has('items.*'))
        <ul class="border-b border-line bg-red-50 px-5 py-2 text-sm text-red-800">
            @foreach (collect($errors->get('items*'))->flatten()->unique() as $message)<li>{{ $message }}</li>@endforeach
        </ul>
    @endif
    <div class="px-5 py-3"><button type="button" class="btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="h-4 w-4" /> Add another medicine</button></div>
</div>
