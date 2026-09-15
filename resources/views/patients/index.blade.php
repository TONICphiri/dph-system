<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Reception · Patient registry</p>
            <h2 class="text-2xl font-extrabold leading-tight">Find or register a patient</h2>
            <p class="text-sm text-dhp-100">Search nationally by name, National ID or DHP ID — or scan the passport QR code.</p>
        </div>
    </x-slot>

    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('patients.index') }}" class="flex w-full max-w-xl gap-2" role="search" aria-label="Search patients">
            <label for="patient-search" class="sr-only">Search by name, National ID, or DHP ID</label>
            <input type="text" id="patient-search" name="search" placeholder="Search name, National ID, DHP ID…" value="{{ request('search') }}" maxlength="60" class="dhp-input flex-1" />
            <button type="submit" class="btn-primary">Search</button>
            @if(request('search'))
                <a href="{{ route('patients.index') }}" class="btn-secondary">Clear</a>
            @endif
        </form>
        @can('create_patient')
            <a href="{{ route('patients.create') }}" class="btn-success">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Register new patient
            </a>
        @endcan
    </div>

    <section aria-label="QR lookup" class="dhp-card dhp-card-pad mb-6 border-emerald-200 bg-white">
        <h3 class="font-bold text-emerald-900">QR / DHP ID Lookup</h3>
        <p class="mt-1 text-sm text-slate-600">Scan the passport QR with the camera, or paste a DHP ID (e.g. DHP-2026-00000001) to open the record instantly.</p>
        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
            <label for="lookup-dhp-id" class="sr-only">Digital Health Passport ID</label>
            <input type="text" id="lookup-dhp-id" placeholder="e.g. DHP-2026-00000001" autocomplete="off" maxlength="64" class="dhp-input flex-1" />
            <button type="button" id="lookup-dhp-btn" class="btn-success">Open record</button>
            <button type="button" id="scan-qr-btn" class="btn-primary">Scan QR Code</button>
            <button type="button" id="stop-scan-btn" class="btn-danger hidden">Stop scan</button>
        </div>
        <div id="qr-scanner" class="hidden mt-4">
            <video id="qr-video" class="dhp-qr-video" autoplay muted playsinline aria-label="QR camera preview"></video>
            <p id="qr-scanner-status" class="mt-2 text-sm text-emerald-800" role="status">Point the camera at the patient's DHP QR code.</p>
        </div>
        <div id="lookup-dhp-result" class="mt-3" aria-live="polite"></div>
    </section>

    @if ($patients->count())
        <div class="dhp-table-wrap">
            <table class="dhp-table">
                <thead><tr><th>DHP ID</th><th>Patient</th><th>National ID</th><th>Age</th><th>Status</th><th>Registered</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach ($patients as $patient)
                        <tr>
                            <td class="dhp-mono">{{ $patient->dhp_id }}</td>
                            <td class="font-semibold text-dhp-900">{{ $patient->full_name }}</td>
                            <td>{{ $patient->national_id ?? '—' }}</td>
                            <td class="tabular-nums">{{ $patient->age ?? 'N/A' }}</td>
                            <td><span class="dhp-badge {{ $patient->status === 'active' ? 'badge-active' : 'badge-pending' }}">{{ ucfirst($patient->status) }}</span></td>
                            <td class="whitespace-nowrap text-xs text-slate-500">{{ $patient->registered_at?->format('d M Y') }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('patients.show', $patient) }}" class="btn-secondary !min-h-[40px] !px-3 !py-1.5 !text-xs">Open</a>
                                @can('edit_patient')
                                    <a href="{{ route('patients.edit', $patient) }}" class="ml-1 font-bold text-dhp-700 hover:underline">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $patients->links() }}</div>
    @else
        <div class="dhp-empty">
            <p class="font-bold text-dhp-900">No patients found</p>
            <p class="max-w-md text-sm text-slate-500">Try a different spelling or ID. If this is a first visit, register the patient to issue a lifelong Digital Health Passport.</p>
            @can('create_patient')
                <a href="{{ route('patients.create', ['search' => request('search')]) }}" class="btn-primary mt-3">Register this patient</a>
            @endcan
        </div>
    @endif

    <script>
        (function () {
            var dhpInput = document.getElementById('lookup-dhp-id');
            var dhpBtn = document.getElementById('lookup-dhp-btn');
            var scanQrBtn = document.getElementById('scan-qr-btn');
            var stopScanBtn = document.getElementById('stop-scan-btn');
            var qrScanner = document.getElementById('qr-scanner');
            var qrVideo = document.getElementById('qr-video');
            var qrScannerStatus = document.getElementById('qr-scanner-status');
            var dhpResult = document.getElementById('lookup-dhp-result');
            var qrStream = null, qrScanInterval = null;

            function parseDhpId(value) {
                try { var parsed = JSON.parse(value); if (parsed && parsed.dhp_id) return parsed.dhp_id; } catch (e) {}
                return value;
            }
            function stopQrScanner() {
                if (qrScanInterval) { clearInterval(qrScanInterval); qrScanInterval = null; }
                if (qrStream) { qrStream.getTracks().forEach(function (t) { t.stop(); }); qrStream = null; }
                qrVideo.srcObject = null;
                qrScanner.classList.add('hidden'); stopScanBtn.classList.add('hidden'); scanQrBtn.classList.remove('hidden');
            }
            function lookupDhpId(scannedValue) {
                var value = parseDhpId((scannedValue || dhpInput.value).trim());
                if (!value) { dhpResult.innerHTML = '<div class="dhp-alert-error">Please enter or scan a DHP ID.</div>'; return; }
                dhpInput.value = value;
                dhpResult.innerHTML = '<p class="text-sm text-slate-500" role="status">Searching for ' + value.replace(/</g, '&lt;') + '…</p>';
                fetch('{{ route('patients.search.dhp-id') }}?dhp_id=' + encodeURIComponent(value), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { if (r.status === 404) { return r.json().then(function (d) { throw new Error(d.message || 'Patient not found'); }); } if (!r.ok) throw new Error('Search failed. Try again.'); return r.json(); })
                    .then(function (data) {
                        if (data.found) {
                            var p = data.patient;
                            dhpResult.innerHTML = '<div class="dhp-alert-success"><div><strong>Record found:</strong> ' + String(p.full_name).replace(/</g, '&lt;') +
                                ' (Age: ' + (p.age ?? 'N/A') + ')<br><a href="/patients/' + p.id + '" class="font-bold underline">Open patient record →</a></div></div>';
                        } else { dhpResult.innerHTML = '<div class="dhp-alert-warning">No patient found with this DHP ID.</div>'; }
                    })
                    .catch(function (err) { dhpResult.innerHTML = '<div class="dhp-alert-error">' + String(err.message).replace(/</g, '&lt;') + '</div>'; });
            }
            dhpBtn.addEventListener('click', function () { lookupDhpId(); });
            scanQrBtn.addEventListener('click', function () {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { dhpResult.innerHTML = '<div class="dhp-alert-error">This device cannot access the camera. Enter the DHP ID manually.</div>'; return; }
                if (!window.BarcodeDetector) { dhpResult.innerHTML = '<div class="dhp-alert-warning">Built-in QR scanning needs Chrome or Edge. Enter the DHP ID manually.</div>'; return; }
                var detector = new BarcodeDetector({ formats: ['qr_code'] });
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (stream) {
                    qrStream = stream; qrVideo.srcObject = stream;
                    qrScanner.classList.remove('hidden'); scanQrBtn.classList.add('hidden'); stopScanBtn.classList.remove('hidden');
                    qrScannerStatus.textContent = 'Point the camera at the patient\u2019s DHP QR code.';
                    qrScanInterval = setInterval(function () {
                        if (qrVideo.readyState < 2) return;
                        detector.detect(qrVideo).then(function (codes) {
                            if (codes.length && codes[0].rawValue) { stopQrScanner(); lookupDhpId(codes[0].rawValue.trim()); }
                        }).catch(function () { qrScannerStatus.textContent = 'Scanning failed. Try again or enter the DHP ID manually.'; });
                    }, 500);
                }).catch(function () { dhpResult.innerHTML = '<div class="dhp-alert-error">Camera permission was denied or no camera is available.</div>'; });
            });
            stopScanBtn.addEventListener('click', stopQrScanner);
            dhpInput.addEventListener('keypress', function (e) { if (e.key === 'Enter') { e.preventDefault(); lookupDhpId(); } });
        })();
    </script>
</x-app-layout>
