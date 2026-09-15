<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">User Catalogue · Facility enrollment</p>
            <h2 class="text-2xl font-extrabold leading-tight">Enroll user with verified NIN</h2>
        </div>
    </x-slot>

    <form method="POST" action="{{ route('enroll.store') }}" class="dhp-card dhp-card-pad max-w-3xl">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-1">
                <label class="dhp-label" for="nin">National Identity Number</label>
                <input id="nin" name="nin" value="{{ old('nin') }}" required maxlength="30" class="dhp-input" placeholder="e.g. A123456789" />
                <p id="nin-status" class="dhp-help" role="status">Step 1: NIN is checked live for uniqueness.</p>
                @error('nin')<p class="dhp-field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="dhp-label" for="role">Account role</label>
                <select id="role" name="role" class="dhp-select">
                    <option value="patient">Patient (Citizen)</option>
                    <option value="practitioner">Practitioner</option>
                    <option value="facility_admin">Facility staff</option>
                    <option value="verifier">Verifier</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="dhp-label" for="full_name">Full name (as on national ID)</label>
                <input id="full_name" name="full_name" value="{{ old('full_name') }}" required class="dhp-input" />
                @error('full_name')<p class="dhp-field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="dhp-label" for="dob">Date of birth</label>
                <input id="dob" type="date" name="dob" value="{{ old('dob') }}" max="{{ now()->toDateString() }}" class="dhp-input" />
            </div>
            <div>
                <label class="dhp-label" for="gender">Gender</label>
                <select id="gender" name="gender" class="dhp-select">
                    <option value="">Select</option>
                    <option value="M">M</option>
                    <option value="F">F</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label class="dhp-label" for="phone">Phone</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" class="dhp-input" />
            </div>
            <div>
                <label class="dhp-label" for="email">Email (optional)</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="dhp-input" />
            </div>
            <div class="sm:col-span-2">
                <label class="dhp-label" for="id_document_ref">ID document scan reference</label>
                <input id="id_document_ref" name="id_document_ref" value="{{ old('id_document_ref') }}" class="dhp-input" placeholder="e.g. storage path / scan ref" />
            </div>
            <div class="sm:col-span-2">
                <label class="dhp-label" for="patient_id">Clinical file (optional, patient accounts only)</label>
                <input id="patient_id" name="patient_id" value="{{ old('patient_id') }}" inputmode="numeric" class="dhp-input" placeholder="Patient file internal ID, if already registered" />
                <p class="dhp-help">Links this account to its health file now; otherwise link it later from the approval queue with the DHP ID.</p>
                @error('patient_id')<p class="dhp-field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <hr class="dhp-divider" />

        <fieldset class="grid gap-3">
            <legend class="dhp-section-title">Staff declaration</legend>
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="physical_verification" value="1" class="dhp-check mt-1" required />
                <span>I physically inspected the national ID document and confirm the NIN matches the person present. My staff identity will be recorded.</span>
            </label>
            <div>
                <label class="dhp-label" for="staff_pin_confirm">Re-enter YOUR password to confirm</label>
                <input id="staff_pin_confirm" type="password" name="staff_pin_confirm" required class="dhp-input max-w-sm" autocomplete="off" />
                @error('staff_pin_confirm')<p class="dhp-field-error">{{ $message }}</p>@enderror
            </div>
        </fieldset>

        <div class="mt-5 flex gap-2">
            <button type="submit" class="btn-primary">Submit for PENDING approval</button>
            <a href="{{ route('enroll.pending') }}" class="btn-secondary">Approval queue</a>
        </div>
    </form>

    <script>
    (function () {
        var nin = document.getElementById('nin'), status = document.getElementById('nin-status'), t;
        if (!nin) return;
        nin.addEventListener('input', function () {
            clearTimeout(t);
            var v = nin.value.trim();
            if (v.length < 6) { status.textContent = 'Step 1: NIN is checked live for uniqueness.'; return; }
            status.textContent = 'Checking…';
            t = setTimeout(function () {
                fetch('{{ route('enroll.check-nin') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ nin: v })
                }).then(function (r) { return r.json(); }).then(function (d) {
                    status.textContent = d.available ? ('Available: ' + (d.masked || '')) : (d.message || 'Identity already enrolled');
                }).catch(function () { status.textContent = 'Check failed. Try again.'; });
            }, 400);
        });
    })();
    </script>
</x-app-layout>
