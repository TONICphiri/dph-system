<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __("Patient QR Code - ") . $patient->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-4">Digital Health Passport QR Code</h3>
                        
                        <div class="bg-white p-8 border-2 border-blue-300 rounded-lg inline-block mb-6">
                            {!! $qrCode !!}
                        </div>

                        <div class="space-y-2 mb-6">
                            <p class="font-mono text-blue-600 font-bold text-xl">{{ $patient->dhp_id }}</p>
                            <p class="text-sm text-gray-500">National ID: {{ $patient->national_id }}</p>
                            <p class="text-sm text-gray-500">Patient: {{ $patient->full_name }}</p>
                        </div>

                        <div class="flex gap-4 justify-center">
                            <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Print QR Code
                            </button>
                            <a href="{{ route("patients.show", $patient) }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                                Back to Patient
                            </a>
                        </div>
                    </div>

                    <div class="mt-8 pt-8 border-t">
                        <h4 class="font-semibold mb-4">Instructions</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li>✓ Print this QR code and laminate it for durability</li>
                            <li>✓ Attach to patient''s health passport or identification card</li>
                            <li>✓ Scan with smartphone camera to access patient record instantly</li>
                            <li>✓ Use at any registered facility in the DHP network</li>
                            <li>✓ QR code is valid for the lifetime of the patient record</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
