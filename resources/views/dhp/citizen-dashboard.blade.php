<x-dhp.layout title="My Passport" :nav="[['label' => 'My Passport', 'url' => route('dhp.citizen.dashboard')]]">
    <div class="panel">
        <div class="panel-header">
            <h1 class="panel-title">My Passport</h1>
        </div>
        <div class="panel-body space-y-3">
            @if ($citizen)
                <p class="text-sm">Passport ID: <span class="mono">{{ $citizen->passport_id }}</span></p>
                <p class="text-sm text-muted">Your vaccination and laboratory-test credentials will appear here in the next phase.</p>
            @else
                <p class="text-sm">No passport profile is linked to this login yet. Ask a health worker to link your passport at a health facility.</p>
            @endif
        </div>
    </div>
</x-dhp.layout>
