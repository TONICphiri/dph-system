<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight">
            {{ __('Medical Travel Clearance') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-6 rounded-2xl border border-sky-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-sky-950">Find Patient</h3>
                <p class="mt-1 text-sm text-slate-600">A National ID, DHP ID, or scanned DHP QR code is required before generating the PDF.</p>

                <form method="GET" action="{{ route('reports.medical-clearance') }}" class="mt-4">
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input type="text" id="identifier" name="identifier" value="{{ old('identifier', $identifier) }}" placeholder="National ID or DHP-2026-00000001" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                        <button type="submit" class="rounded-md bg-sky-700 px-4 py-2 font-semibold text-white hover:bg-sky-800">Find Patient</button>
                        <button type="button" id="scan-qr-btn" class="rounded-md bg-teal-600 px-4 py-2 font-semibold text-white hover:bg-teal-700">Scan QR</button>
                        <button type="button" id="stop-scan-btn" class="hidden rounded-md bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">Stop</button>
                    </div>
                </form>

                <div id="qr-scanner" class="hidden mt-4">
                    <video id="qr-video" class="w-full max-w-md rounded-lg border border-sky-200 bg-black" autoplay muted playsinline></video>
                    <p id="qr-scanner-status" class="mt-2 text-sm text-sky-700">Point the camera at the patient's DHP QR code.</p>
                </div>
            </div>

            @if ($identifier !== '' && !$patient)
                <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800">
                    No patient found with this National ID, DHP ID, or QR code.
                </div>
            @endif

            @if ($patient)
                <div class="mb-6 rounded-2xl border border-teal-100 bg-white p-6 shadow-sm">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Full Name</p>
                            <p class="font-semibold text-sky-950">{{ $patient->full_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Date of Birth</p>
                            <p class="font-semibold text-sky-950">{{ $patient->date_of_birth?->format('M d, Y') ?? 'Not recorded' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">ID / Passport Number</p>
                            <p class="font-semibold text-sky-950">{{ $patient->national_id ?: $patient->dhp_id }}</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('reports.medical-clearance.pdf') }}" class="rounded-2xl border border-sky-100 bg-white p-6 shadow-sm">
                    @csrf
                    <input type="hidden" name="identifier" value="{{ $identifier }}">

                    <div class="grid gap-6">
                        <section>
                            <h3 class="text-lg font-semibold text-sky-950">Medical Information</h3>
                            <div class="mt-4 grid gap-4">
                                <div>
                                    <label for="diagnosis" class="block text-sm font-medium text-slate-700">Clear Diagnosis</label>
                                    <textarea id="diagnosis" name="diagnosis" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>{{ old('diagnosis', $latestEncounter?->diagnosis) }}</textarea>
                                </div>
                                <div>
                                    <label for="current_condition" class="block text-sm font-medium text-slate-700">Current Condition Summary</label>
                                    <textarea id="current_condition" name="current_condition" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>{{ old('current_condition', $latestEncounter?->examination_findings) }}</textarea>
                                </div>
                                <div>
                                    <label for="treatment_plan" class="block text-sm font-medium text-slate-700">Active Treatment Plan</label>
                                    <textarea id="treatment_plan" name="treatment_plan" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>{{ old('treatment_plan', $latestEncounter?->treatment_plan) }}</textarea>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-lg font-semibold text-sky-950">Medications and Devices</h3>
                            <div class="mt-4 grid gap-4">
                                <div>
                                    <label for="medications" class="block text-sm font-medium text-slate-700">Prescribed Drugs with Generic Names and Dosages</label>
                                    <textarea id="medications" name="medications" rows="4" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('medications', $activePrescriptions->map(fn ($p) => trim($p->medication_name . ' - ' . $p->dose . ', ' . $p->frequency . ($p->duration ? ', ' . $p->duration : '')))->implode("\n")) }}</textarea>
                                </div>
                                <div>
                                    <label for="medical_equipment" class="block text-sm font-medium text-slate-700">Necessary Medical Equipment</label>
                                    <textarea id="medical_equipment" name="medical_equipment" rows="2" placeholder="e.g., syringes, oxygen, mobility aid" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('medical_equipment') }}</textarea>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-lg font-semibold text-sky-950">Travel Clearance</h3>
                            <div class="mt-4 grid gap-4">
                                <div>
                                    <label for="travel_clearance" class="block text-sm font-medium text-slate-700">Doctor's Fitness for Travel Statement</label>
                                    <textarea id="travel_clearance" name="travel_clearance" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>{{ old('travel_clearance', 'I confirm that the patient is medically fit for travel subject to the accommodations listed below.') }}</textarea>
                                </div>
                                <div>
                                    <label for="flight_accommodations" class="block text-sm font-medium text-slate-700">Specific Flight Accommodations</label>
                                    <textarea id="flight_accommodations" name="flight_accommodations" rows="2" placeholder="e.g., wheelchair assistance, oxygen support, aisle seat" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">{{ old('flight_accommodations') }}</textarea>
                                </div>
                            </div>
                        </section>

                        <section>
                            <h3 class="text-lg font-semibold text-sky-950">Physician Sign-off</h3>
                            <div class="mt-4 grid gap-4 md:grid-cols-3">
                                <div>
                                    <label for="physician_name" class="block text-sm font-medium text-slate-700">Physician Name</label>
                                    <input id="physician_name" name="physician_name" type="text" value="{{ old('physician_name', auth()->user()->name) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                                </div>
                                <div>
                                    <label for="physician_contact" class="block text-sm font-medium text-slate-700">Contact Information</label>
                                    <input id="physician_contact" name="physician_contact" type="text" value="{{ old('physician_contact', auth()->user()->email) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                                </div>
                                <div>
                                    <label for="issue_date" class="block text-sm font-medium text-slate-700">Date of Issue</label>
                                    <input id="issue_date" name="issue_date" type="date" value="{{ old('issue_date', now()->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" required>
                                </div>
                            </div>
                        </section>

                        <div class="flex justify-end">
                            <button type="submit" class="rounded-md bg-teal-700 px-5 py-2 font-semibold text-white hover:bg-teal-800">Generate Medical PDF</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        const identifierInput = document.getElementById('identifier');
        const scanQrBtn = document.getElementById('scan-qr-btn');
        const stopScanBtn = document.getElementById('stop-scan-btn');
        const qrScanner = document.getElementById('qr-scanner');
        const qrVideo = document.getElementById('qr-video');
        const qrScannerStatus = document.getElementById('qr-scanner-status');
        let qrStream = null;
        let qrScanInterval = null;

        function parseDhpId(value) {
            try {
                const parsed = JSON.parse(value);
                if (parsed && parsed.dhp_id) {
                    return parsed.dhp_id;
                }
            } catch (e) {}

            return value;
        }

        function stopQrScanner() {
            if (qrScanInterval) {
                clearInterval(qrScanInterval);
                qrScanInterval = null;
            }
            if (qrStream) {
                qrStream.getTracks().forEach(function(track) { track.stop(); });
                qrStream = null;
            }
            qrVideo.srcObject = null;
            qrScanner.classList.add('hidden');
            stopScanBtn.classList.add('hidden');
            scanQrBtn.classList.remove('hidden');
        }

        scanQrBtn.addEventListener('click', function() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.BarcodeDetector) {
                qrScanner.classList.remove('hidden');
                qrScannerStatus.textContent = 'This browser cannot scan QR codes here. Enter the DHP ID manually.';
                return;
            }

            const detector = new BarcodeDetector({ formats: ['qr_code'] });

            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(function(stream) {
                    qrStream = stream;
                    qrVideo.srcObject = stream;
                    qrScanner.classList.remove('hidden');
                    scanQrBtn.classList.add('hidden');
                    stopScanBtn.classList.remove('hidden');

                    qrScanInterval = setInterval(function() {
                        if (qrVideo.readyState < 2) {
                            return;
                        }

                        detector.detect(qrVideo).then(function(codes) {
                            if (codes.length && codes[0].rawValue) {
                                identifierInput.value = parseDhpId(codes[0].rawValue.trim());
                                stopQrScanner();
                                identifierInput.form.submit();
                            }
                        });
                    }, 500);
                })
                .catch(function() {
                    qrScanner.classList.remove('hidden');
                    qrScannerStatus.textContent = 'Camera permission was denied or no camera is available.';
                });
        });

        stopScanBtn.addEventListener('click', stopQrScanner);
    </script>
</x-app-layout>
