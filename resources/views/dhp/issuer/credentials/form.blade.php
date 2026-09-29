@php
    $isEdit = $credential->exists;
    $type = old('type', $credential->type->value ?? 'vaccination');
    $detail = $type === 'lab_test' ? $credential->testDetail : $credential->vaccinationDetail;
    if ($replacementOf && ! old('type')) {
        $type = $replacementOf->type->value;
        $detail = $type === 'lab_test' ? $replacementOf->testDetail : $replacementOf->vaccinationDetail;
    }
@endphp
<x-dhp.layout :title="$isEdit ? 'Correct Credential' : 'Issue Credential'" :nav="[['label' => 'Search Citizen', 'url' => route('dhp.issuer.citizens.search')]]">
    <div class="panel mx-auto max-w-2xl">
        <div class="panel-header">
            <h1 class="panel-title">{{ $isEdit ? 'Correct Credential' : 'Issue Credential' }} — {{ $citizen->full_name }} ({{ $citizen->passport_id }})</h1>
        </div>
        <div class="panel-body">
            @if ($replacementOf)
                <p class="mb-4 border border-line bg-paper px-4 py-3 text-[13px]">Replacement for revoked credential <span class="mono">{{ $replacementOf->credential_number }}</span>. Review every field and submit.</p>
            @endif
            <form method="POST" action="{{ $action }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                @if ($isEdit) @method('PUT') @endif
                @if ($replacementOf)<input type="hidden" name="replace_of" value="{{ $replacementOf->id }}">@endif

                @if (! $isEdit)
                    <div class="sm:col-span-2">
                        <label class="label" for="type">Credential type *</label>
                        <select id="type" name="type" required class="input">
                            <option value="vaccination" @selected($type === 'vaccination')>Vaccination</option>
                            <option value="lab_test" @selected($type === 'lab_test')>Laboratory test</option>
                        </select>
                        @error('type')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                @endif

                <div><label class="label" for="issue_date">Issue date *</label><input id="issue_date" name="issue_date" type="date" value="{{ old('issue_date', $credential->issue_date?->format('Y-m-d') ?? $detail?->administration_date?->format('Y-m-d') ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="input">@error('issue_date')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="facility_id">Issuing facility {{ auth()->user()->facility_id ? '(yours)' : '*' }}</label>
                    @if (auth()->user()->facility_id)
                        <input type="text" value="{{ auth()->user()->facility?->name }}" disabled class="input">
                    @else
                        <select id="facility_id" name="facility_id" required class="input"><option value="">—</option>@foreach ($facilities as $id => $name)<option value="{{ $id }}" @selected((string) old('facility_id', $defaultFacilityId) === (string) $id)>{{ $name }}</option>@endforeach</select>
                    @endif
                    @error('facility_id')<p class="field-error">{{ $message }}</p>@enderror</div>

                <fieldset id="fields-vaccination" class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                    <div><label class="label" for="vaccine_name">Vaccine name *</label><input id="vaccine_name" name="vaccine_name" type="text" value="{{ old('vaccine_name', $detail->vaccine_name ?? '') }}" class="input">@error('vaccine_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="dose_number">Dose number *</label><input id="dose_number" name="dose_number" type="number" min="1" max="20" value="{{ old('dose_number', $detail->dose_number ?? '') }}" class="input">@error('dose_number')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="administration_date">Administration date *</label><input id="administration_date" name="administration_date" type="date" value="{{ old('administration_date', $detail?->administration_date?->format('Y-m-d') ?? '') }}" max="{{ now()->toDateString() }}" class="input">@error('administration_date')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="batch_number">Batch number</label><input id="batch_number" name="batch_number" type="text" value="{{ old('batch_number', $detail->batch_number ?? '') }}" class="input">@error('batch_number')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="next_dose_date">Next dose date</label><input id="next_dose_date" name="next_dose_date" type="date" value="{{ old('next_dose_date', $detail?->next_dose_date?->format('Y-m-d') ?? '') }}" class="input">@error('next_dose_date')<p class="field-error">{{ $message }}</p>@enderror</div>
                </fieldset>

                <fieldset id="fields-lab" class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                    <div><label class="label" for="test_type">Test type *</label><input id="test_type" name="test_type" type="text" value="{{ old('test_type', $detail->test_type ?? '') }}" class="input">@error('test_type')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="result">Result (issuer record only) *</label><input id="result" name="result" type="text" value="{{ old('result', $detail->result ?? '') }}" class="input">@error('result')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="sample_collection_date">Sample collection date *</label><input id="sample_collection_date" name="sample_collection_date" type="date" value="{{ old('sample_collection_date', $detail?->sample_collection_date?->format('Y-m-d') ?? '') }}" max="{{ now()->toDateString() }}" class="input">@error('sample_collection_date')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="result_date">Result date *</label><input id="result_date" name="result_date" type="date" value="{{ old('result_date', $detail?->result_date?->format('Y-m-d') ?? '') }}" max="{{ now()->toDateString() }}" class="input">@error('result_date')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div><label class="label" for="valid_until">Valid until *</label><input id="valid_until" name="valid_until" type="date" value="{{ old('valid_until', $detail?->valid_until?->format('Y-m-d') ?? '') }}" class="input">@error('valid_until')<p class="field-error">{{ $message }}</p>@enderror</div>
                </fieldset>

                <div class="sm:col-span-2"><button type="submit" class="btn-primary">{{ $isEdit ? 'Save correction' : 'Issue credential' }}</button></div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var type = document.getElementById('type');
            var vacc = document.getElementById('fields-vaccination');
            var lab = document.getElementById('fields-lab');
            function toggle() {
                var isLab = type && type.value === 'lab_test';
                if (vacc) vacc.style.display = !type || !isLab ? '' : 'none';
                if (lab) lab.style.display = isLab ? '' : 'none';
            }
            if (type) type.addEventListener('change', toggle);
            toggle();
        })();
    </script>
</x-dhp.layout>
