<x-dhp.layout title="Search Citizen" :nav="[['label' => 'Search Citizen', 'url' => route('dhp.issuer.citizens.search')], ['label' => 'Register Citizen', 'url' => route('dhp.issuer.citizens.create')]]">
    <div class="panel">
        <div class="panel-header">
            <h1 class="panel-title">Search Citizen</h1>
        </div>
        <div class="panel-body">
            <form method="GET" action="{{ route('dhp.issuer.citizens.search') }}" class="grid gap-4 sm:grid-cols-[1fr_200px_auto]">
                <div>
                    <label class="label" for="query">Passport ID, National ID or name</label>
                    <input id="query" name="query" type="text" value="{{ $query }}" autocomplete="off" minlength="3"
                        data-suggestions-url="{{ route('dhp.issuer.citizens.suggestions') }}" class="input" placeholder="e.g. MW-DHP-2026-000123 or family name">
                    <ul id="suggestions" class="mt-1 hidden border border-line bg-white text-sm" aria-live="polite"></ul>
                </div>
                <div>
                    <label class="label" for="date_of_birth">Date of birth (with another field)</label>
                    <input id="date_of_birth" name="date_of_birth" type="date" value="{{ $dateOfBirth }}" class="input">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn-primary">Search</button>
                </div>
            </form>
            <p class="field-help">Name search needs at least 3 characters. Date of birth alone is not accepted.</p>

            @if ($searchError)
                <p class="mt-4 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $searchError }}</p>
            @endif

            @if (is_array($results))
                <h2 class="mt-6 text-sm font-semibold">Results ({{ count($results) }})</h2>
                @if ($results === [])
                    <p class="mt-2 text-sm text-muted">No matching citizen was found. Check the information and try again, or <a href="{{ route('dhp.issuer.citizens.create') }}" class="link">register a new citizen</a>.</p>
                @else
                    <div class="mt-2 overflow-x-auto">
                        <table class="table">
                            <thead><tr><th>Passport ID</th><th>Name</th><th>National ID</th><th>Age</th><th>Sex</th><th>District</th><th></th></tr></thead>
                            <tbody>
                                @foreach ($results as $citizen)
                                    <tr>
                                        <td class="mono">{{ $citizen->passport_id }}</td>
                                        <td>{{ $citizen->full_name }}</td>
                                        <td class="mono">{{ $citizen->maskedNationalId() ?? '—' }}</td>
                                        <td>{{ $citizen->date_of_birth?->age }}</td>
                                        <td class="capitalize">{{ $citizen->sex }}</td>
                                        <td>{{ $citizen->district }}</td>
                                        <td><a href="{{ route('dhp.issuer.citizens.confirm-form', $citizen) }}" class="link text-sm">Open</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <script>
        (function () {
            var input = document.getElementById('query');
            var list = document.getElementById('suggestions');
            if (!input || !list) return;
            var token = document.querySelector('meta[name="csrf-token"]');
            var timer = null;
            input.addEventListener('input', function () {
                clearTimeout(timer);
                var q = input.value.trim();
                if (q.length < 3) { list.classList.add('hidden'); list.innerHTML = ''; return; }
                timer = setTimeout(function () {
                    fetch(input.dataset.suggestionsUrl + '?query=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token ? token.content : '' },
                        credentials: 'same-origin'
                    }).then(function (r) { return r.json(); }).then(function (body) {
                        list.innerHTML = '';
                        (body.data || []).slice(0, 10).forEach(function (c) {
                            var li = document.createElement('li');
                            var a = document.createElement('a');
                            a.href = c.confirm_url;
                            a.className = 'block px-3 py-2 hover:bg-brand-50';
                            a.textContent = c.full_name + ' · ' + c.passport_id;
                            li.appendChild(a);
                            list.appendChild(li);
                        });
                        list.classList.toggle('hidden', !(body.data && body.data.length));
                    }).catch(function () { list.classList.add('hidden'); });
                }, 300);
            });
            document.addEventListener('click', function (e) {
                if (!list.contains(e.target) && e.target !== input) list.classList.add('hidden');
            });
        })();
    </script>
</x-dhp.layout>
