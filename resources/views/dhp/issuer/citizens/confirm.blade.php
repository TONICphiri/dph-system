<x-dhp.layout title="Confirm Identity" :nav="[['label' => 'Search Citizen', 'url' => route('dhp.issuer.citizens.search')], ['label' => 'Register Citizen', 'url' => route('dhp.issuer.citizens.create')]]">
    <div class="panel mx-auto max-w-xl">
        <div class="panel-header">
            <h1 class="panel-title">Confirm Identity</h1>
        </div>
        <div class="panel-body">
            <p class="text-sm text-muted">Confirm at least two details before opening a citizen passport profile. Ask the citizen for the details below; the passport opens only when two or more match.</p>
            <dl class="detail-list mt-4">
                <div><dt>Passport ID</dt><dd class="mono">{{ $citizen->passport_id }}</dd></div>
                <div><dt>Name on record</dt><dd>{{ $citizen->full_name }}</dd></div>
            </dl>
            <form method="POST" action="{{ route('dhp.issuer.citizens.confirm-identity') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <input type="hidden" name="citizen_id" value="{{ $citizen->id }}">
                <div><label class="label" for="first_name">First name</label><input id="first_name" name="first_name" type="text" class="input" autocomplete="off"></div>
                <div><label class="label" for="last_name">Last name</label><input id="last_name" name="last_name" type="text" class="input" autocomplete="off"></div>
                <div><label class="label" for="date_of_birth">Date of birth</label><input id="date_of_birth" name="date_of_birth" type="date" class="input"></div>
                <div><label class="label" for="sex">Sex</label>
                    <select id="sex" name="sex" class="input"><option value="">—</option><option value="female">Female</option><option value="male">Male</option></select>
                </div>
                <div><label class="label" for="district">District</label><input id="district" name="district" type="text" class="input" autocomplete="off"></div>
                <div><label class="label" for="village">Village</label><input id="village" name="village" type="text" class="input" autocomplete="off"></div>
                <div class="sm:col-span-2"><button type="submit" class="btn-primary">Confirm and open passport</button></div>
            </form>
        </div>
    </div>
</x-dhp.layout>
