<x-dhp.layout title="Register Citizen" :nav="[['label' => 'Search Citizen', 'url' => route('dhp.issuer.citizens.search')], ['label' => 'Register Citizen', 'url' => route('dhp.issuer.citizens.create')]]">
    <div class="panel mx-auto max-w-2xl">
        <div class="panel-header">
            <h1 class="panel-title">Register Citizen</h1>
        </div>
        <div class="panel-body">
            <p class="border border-line bg-paper px-4 py-3 text-[13px] text-muted">Privacy notice: these details are recorded only to identify the citizen and their verifiable credentials. National ID is an identifier only — it is never a password or QR content.</p>

            @if (session()->has('duplicate'))
                @php $dup = session('duplicate'); @endphp
                <div class="mt-4 border border-gold-600/30 bg-gold-100 px-4 py-3 text-sm text-gold-700">
                    <p class="font-semibold">Possible duplicate: {{ $dup['full_name'] }} ({{ $dup['passport_id'] }}).</p>
                    <p class="mt-1"><a href="{{ $dup['confirm_url'] }}" class="link">Open the existing passport</a> or tick the override box below and explain why a separate record is needed.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('dhp.issuer.citizens.store') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf
                <div><label class="label" for="first_name">First name *</label><input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required class="input">@error('first_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="last_name">Last name *</label><input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required class="input">@error('last_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="sex">Sex *</label>
                    <select id="sex" name="sex" required class="input"><option value="">—</option><option value="female" @selected(old('sex') === 'female')>Female</option><option value="male" @selected(old('sex') === 'male')>Male</option></select>
                    @error('sex')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="date_of_birth">Date of birth *</label><input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" max="{{ now()->toDateString() }}" required class="input">@error('date_of_birth')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="district">District *</label>
                    <select id="district" name="district" required class="input"><option value="">—</option>@foreach ($districts as $d)<option value="{{ $d }}" @selected(old('district') === $d)>{{ $d }}</option>@endforeach</select>
                    @error('district')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="village">Village</label><input id="village" name="village" type="text" value="{{ old('village') }}" class="input">@error('village')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="national_id">National ID</label><input id="national_id" name="national_id" type="text" value="{{ old('national_id') }}" aria-describedby="national_id_help" class="input"><p id="national_id_help" class="field-help">Used only to help identify the citizen at a participating facility. It is not a password.</p>@error('national_id')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="phone">Phone</label><input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="input">@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="label" for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" class="input">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                <label class="flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" name="create_account" value="1" @checked(old('create_account')) class="mt-1 h-4 w-4 border-line text-brand-700"> Create citizen portal account (needs an email address)</label>
                <label class="flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" name="duplicate_override" value="1" @checked(old('duplicate_override')) class="mt-1 h-4 w-4 border-line text-brand-700"> This is a different person from any similar record</label>
                <div class="sm:col-span-2"><label class="label" for="duplicate_reason">Reason for separate record (needed with override)</label><textarea id="duplicate_reason" name="duplicate_reason" rows="2" class="input">{{ old('duplicate_reason') }}</textarea>@error('duplicate_reason')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><button type="submit" class="btn-primary">Register citizen</button></div>
            </form>
        </div>
    </div>
</x-dhp.layout>
