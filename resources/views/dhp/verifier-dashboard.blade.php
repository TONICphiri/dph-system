<x-dhp.layout title="Verify Certificate" :nav="[['label' => 'Verify Certificate', 'url' => route('dhp.verifier.verify')]]">
    <div class="panel">
        <div class="panel-header">
            <h1 class="panel-title">Verify Certificate</h1>
        </div>
        <div class="panel-body space-y-3">
            <p class="text-sm text-muted">Scan a QR code or enter a credential number to check whether a credential is valid.</p>
            <a href="{{ route('dhp.verifier.verify') }}" class="btn-primary btn-sm">Open verification</a>
        </div>
    </div>
</x-dhp.layout>
