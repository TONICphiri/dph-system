<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Facility') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <form method="POST" action="{{ route('facilities.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Facility Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Facility Code</label>
                        <input type="text" name="facility_code" value="{{ old('facility_code') }}" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Facility Type</label>
                            <select name="facility_type" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                                <option value="Health Centre">Health Centre</option>
                                <option value="Hospital">Hospital</option>
                                <option value="Clinic">Clinic</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Status</label>
                            <select name="status" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">District</label>
                            <input type="text" name="district" value="{{ old('district') }}" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Region</label>
                            <input type="text" name="region" value="{{ old('region') }}" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Address</label>
                        <textarea name="address" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('address') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Phone Number</label>
                            <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-sky-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-sky-800 focus:bg-sky-800 active:bg-sky-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition ease-in-out duration-150">Save Facility</button>
                        <a href="{{ route('facilities.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-200 border border-transparent rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest hover:bg-slate-300">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
