<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add Medication to Inventory') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if ($message = Session::get('error'))
                <div class="mb-4 px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route("inventory.store") }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="medication_name" :value="__('Medication Name')" />
                                <x-text-input id="medication_name" name="medication_name" type="text" required
                                             :value="old('medication_name')" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('medication_name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="medication_code" :value="__('Medication Code')" />
                                <x-text-input id="medication_code" name="medication_code" type="text"
                                             :value="old('medication_code')" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('medication_code')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="strength" :value="__('Strength')" />
                            <x-text-input id="strength" name="strength" type="text" placeholder="e.g., 500mg, 100ml"
                                         :value="old('strength')" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('strength')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="current_stock" :value="__('Current Stock')" />
                                <x-text-input id="current_stock" name="current_stock" type="number" min="0" required
                                             :value="old('current_stock', 0)" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('current_stock')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="minimum_stock" :value="__('Minimum Stock')" />
                                <x-text-input id="minimum_stock" name="minimum_stock" type="number" min="0" required
                                             :value="old('minimum_stock', 10)" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('minimum_stock')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="maximum_stock" :value="__('Maximum Stock')" />
                                <x-text-input id="maximum_stock" name="maximum_stock" type="number" min="1" required
                                             :value="old('maximum_stock', 100)" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('maximum_stock')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="unit_of_measurement" :value="__('Unit of Measurement')" />
                                <select id="unit_of_measurement" name="unit_of_measurement" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="tablets" {{ old('unit_of_measurement') === 'tablets' ? 'selected' : '' }}>Tablets</option>
                                    <option value="bottles" {{ old('unit_of_measurement') === 'bottles' ? 'selected' : '' }}>Bottles</option>
                                    <option value="ampoules" {{ old('unit_of_measurement') === 'ampoules' ? 'selected' : '' }}>Ampoules</option>
                                    <option value="sachets" {{ old('unit_of_measurement') === 'sachets' ? 'selected' : '' }}>Sachets</option>
                                    <option value="units" {{ old('unit_of_measurement') === 'units' ? 'selected' : '' }}>Units</option>
                                </select>
                                <x-input-error :messages="$errors->get('unit_of_measurement')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="expiry_date" :value="__('Expiry Date')" />
                                <x-text-input id="expiry_date" name="expiry_date" type="date"
                                             :value="old('expiry_date')" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('expiry_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="unit_price" :value="__('Unit Price (MK)')" />
                                <x-text-input id="unit_price" name="unit_price" type="number" step="0.01" min="0"
                                             :value="old('unit_price')" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('unit_price')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                      placeholder="Any additional information">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="flex gap-4 pt-4">
                            <x-primary-button>{{ __('Add to Inventory') }}</x-primary-button>
                            <a href="{{ route("inventory.index") }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
