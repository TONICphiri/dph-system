<x-layouts.guest title="Verify Certificate">
    <h1 class="text-2xl">Verify Certificate</h1>
    <p class="mt-1 text-sm text-muted">This service verifies the status of a Digital Health Passport credential. Do not submit National ID numbers or personal medical information.</p>

    <div class="mt-8 space-y-6">
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Scan QR code</h2></div>
            <div class="panel-body">
                <div id="qr-reader" class="w-full"></div>
                <p id="scanner-status" class="mt-2 text-sm text-muted" role="status"></p>
                <div class="mt-3 flex gap-2">
                    <button type="button" id="start-scan" class="btn-primary btn-sm">Start scanner</button>
                    <button type="button" id="stop-scan" class="btn-secondary btn-sm" disabled>Stop scanner</button>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Enter credential number</h2></div>
            <div class="panel-body">
                <form method="POST" action="{{ route('dhp.verify.by-number') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="label" for="credential_number">Credential number</label>
                        <input id="credential_number" name="credential_number" type="text" required autocomplete="off"
                            placeholder="MW-CRED-2026-000123" class="input mono">
                        @error('credential_number')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn-primary">Verify</button>
                </form>
            </div>
        </section>
    </div>

    @vite('resources/js/verify-scanner.js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.dhpVerifyScannerInit({
                readerId: 'qr-reader',
                statusId: 'scanner-status',
                startId: 'start-scan',
                stopId: 'stop-scan',
                buildUrl: function (token) { return '{{ url('/verify/by-token') }}/' + token; },
            });
        });
    </script>
</x-layouts.guest>
