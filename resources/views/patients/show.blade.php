<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $patient->full_name }}
            </h2>
@can("edit_patient")
                <a href="{{ route("patients.edit", $patient) }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Edit Patient
                </a>
            @endcan
            <a href="{{ route("patients.qr-code", $patient) }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                View QR Code
            </a>
            @can("update_patient")
                <a href="{{ route("ward", $patient) }}" class="px-4 py-2 bg-cyan-600 text-white rounded hover:bg-cyan-700">
                    Ward
                </a>
            @endcan
            @can("admit_patient")
                <a href="{{ route("admission", $patient) }}" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">
                    Admit
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($message = Session::get("success"))
                <div class="mb-4 px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif

            <!-- Patient Information -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Patient Information</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">DHP ID</p>
                            <p class="font-mono font-bold text-blue-600">{{ $patient->dhp_id }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">National ID</p>
                            <p class="font-mono">{{ $patient->national_id }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date of Birth</p>
                            <p>{{ $patient->date_of_birth?->format("M d, Y") ?? "Not recorded" }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Age</p>
                            <p>{{ $patient->age ?? "N/A" }} years</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Gender</p>
                            <p>{{ $patient->gender ?? "Not recorded" }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <span class="px-2 py-1 text-xs rounded" 
                                  :class="''{{ $patient->status === "active" ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800" }}''">
                                {{ ucfirst($patient->status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Phone</p>
                            <p>{{ $patient->phone_number ?? "Not recorded" }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Registered</p>
                            <p>{{ $patient->registered_at->format("M d, Y H:i") }}</p>
                        </div>
                        @if ($patient->is_child && $guardian)
                            <div>
                                <p class="text-sm text-gray-500">Guardian</p>
                                <p>{{ $guardian->full_name }} ({{ $guardian->relationship }})</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <!-- Recent Encounters -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">Recent Encounters</h3>
                        @if ($encounters->count())
                            <div class="space-y-3">
                                @foreach ($encounters->take(5) as $encounter)
                                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                                        <p class="font-semibold text-sm">{{ ucfirst($encounter->encounter_type) }}</p>
                                        <p class="text-xs text-gray-500">{{ $encounter->encounter_date->format("M d, Y") }}</p>
                                        <p class="text-sm">{{ Str::limit($encounter->chief_complaint ?? "No complaint recorded", 50) }}</p>
                                    </div>
                                @endforeach
                            </div>
                            <a href="#encounters" class="text-blue-600 text-sm hover:underline mt-4 inline-block">View All Encounters</a>
                        @else
                            <p class="text-gray-500 text-sm">No encounters recorded</p>
                        @endif
                    </div>
                </div>

                <!-- Recent Prescriptions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">Active Prescriptions</h3>
                        @php
                            $activePrescriptions = $patient->prescriptions()
                                ->where("status", "!=", "completed")
                                ->orderByDesc("prescribed_at")
                                ->take(5)
                                ->get();
                        @endphp
                        @if ($activePrescriptions->count())
                            <div class="space-y-3">
                                @foreach ($activePrescriptions as $prescription)
                                    <div class="border-l-4 border-green-500 pl-4 py-2">
                                        <p class="font-semibold text-sm">{{ $prescription->medication_name }}</p>
                                        <p class="text-xs">{{ $prescription->dose }} - {{ $prescription->frequency }}</p>
                                        <span class="text-xs px-2 py-1 rounded" 
                                              :class="''{{ $prescription->status === "dispensed" ? "bg-yellow-100 text-yellow-800" : "bg-blue-100 text-blue-800" }}''">
                                            {{ ucfirst($prescription->status) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-sm">No active prescriptions</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Admissions Section -->
            @if ($admissions->count())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">Inpatient Admissions</h3>
                        <div class="space-y-3">
                            @foreach ($admissions->take(5) as $admission)
                                <div class="border-l-4 border-red-500 pl-4 py-2">
                                    <p class="font-semibold text-sm">{{ $admission->facility->name ?? "Unknown Facility" }}</p>
                                    <p class="text-xs text-gray-500">{{ $admission->admitted_at->format("M d, Y") }}</p>
                                    <p class="text-sm">Ward: {{ $admission->ward_name }} - Bed: {{ $admission->bed_number }}</p>
                                    <span class="text-xs px-2 py-1 rounded" 
                                          :class="''{{ $admission->status === "active" ? "bg-red-100 text-red-800" : "bg-gray-100 text-gray-800" }}''">
                                        {{ ucfirst($admission->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
</div>
            </div>
        </div>
    </div>

    <!-- Lab Orders Summary -->
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <h3 class="text-lg font-semibold mb-4">Lab Orders</h3>
            @include('patients.lab-order-summary', ['patient' => $patient])
        </div>
    </div>
</x-app-layout>
