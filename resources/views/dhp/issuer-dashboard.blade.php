<x-dhp.layout title="Issuer Dashboard" :nav="[['label' => 'Issuer Dashboard', 'url' => route('dhp.issuer.dashboard')], ['label' => 'Search Citizen', 'url' => route('dhp.issuer.citizens.search')], ['label' => 'Register Citizen', 'url' => route('dhp.issuer.citizens.create')]]">
    <div class="panel">
        <div class="panel-header">
            <h1 class="panel-title">Issuer Dashboard</h1>
        </div>
        <div class="panel-body space-y-3">
            <p class="text-sm text-muted">Find a citizen to confirm their identity, issue credentials and print certificates.</p>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dhp.issuer.citizens.search') }}" class="btn-primary btn-sm">Search Citizen</a>
                <a href="{{ route('dhp.issuer.citizens.create') }}" class="btn-secondary btn-sm">Register Citizen</a>
            </div>
        </div>
    </div>
</x-dhp.layout>
