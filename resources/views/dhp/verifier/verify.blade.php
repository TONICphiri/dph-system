<x-dhp.layout title="Verification" :nav="[['label' => 'Verify Certificate', 'url' => route('dhp.verifier.dashboard')], ['label' => 'Verification', 'url' => route('dhp.verifier.verify')]]">
    <div class="panel">
        <div class="panel-header"><h1 class="panel-title">Verification</h1></div>
        <div class="panel-body space-y-6">
            <p class="text-sm text-muted">Scan a QR code or enter a credential number to check whether a credential is valid.</p>
            <section>
                <h2 class="text-sm font-semibold">Scan QR code</h2>
                <div id="qr-reader" class="mt-2 w-full"></div>
                <p id="scanner-status" class="mt-2 text-sm text-muted" role="status"></p>
                <div class="mt-3 flex gap-2">
                    <button type="button" id="start-scan" class="btn-primary btn-sm">Start scanner</button>
                    <button type="button" id="stop-scan" class="btn-secondary btn-sm" disabled>Stop scanner</button>
                </div>
            </section>
            <section>
                <h2 class="text-sm font-semibold">Enter credential number</h2>
                <form method="POST" action="{{ route('dhp.verifier.by-number') }}" class="mt-2 space-y-4">
                    @csrf
                    <div>
                        <label class="label" for="credential_number">Credential number</label>
                        <input id="credential_number" name="credential_number" type="text" required autocomplete="off" class="input mono">
                        @error('credential_number')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn-primary">Verify</button>
                </form>
            </section>
        </div>
    </div>

    @vite('resources/js/verify-scanner.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.dhpVerifyScannerInit({
                readerId: 'qr-reader',
                statusId: 'scanner-status',
                startId: 'start-scan',
                stopId: 'stop-scan',
                buildUrl: function (token) { return '{{ url('/verifier/by-token') }}/' + token; },
            });
        });
    </script>
</x-dhp.layout>
