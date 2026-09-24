@php
    use App\Enums\AdmissionStatus;
    $user = auth()->user();
    $patient = $admission->patient;
    $open = $admission->status !== AdmissionStatus::Discharged;
    $timeline = collect()
        ->concat($admission->progressNotes->map(fn ($note) => ['at' => $note->created_at, 'type' => 'Progress note', 'by' => $note->author?->name, 'text' => $note->note]))
        ->concat($admission->medicationAdministrations->map(fn ($dose) => ['at' => $dose->given_at, 'type' => 'Medication given', 'by' => $dose->givenBy?->name, 'text' => $dose->item?->medicine_name.($dose->notes ? '. '.$dose->notes : '')]))
        ->sortByDesc('at');
@endphp
<x-layouts.app :title="'Inpatient chart: '.$patient->full_name">
    <x-page-header :title="$patient->full_name" :description="'Admitted '.$admission->admitted_at->format('j F Y H:i').' by '.($admission->admittedBy?->name ?? 'unknown').'. Day '.$admission->lengthOfStayInDays().' of stay.'">
        <x-slot:breadcrumb><a href="{{ route('admissions.index') }}" class="hover:text-brand-700">Admissions</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Inpatient chart</x-slot:breadcrumb>
        <x-slot:actions>
            @if ($open)
                @can('discharge', $admission)
                    <a href="{{ route('admissions.discharge', $admission) }}" class="btn-primary"><x-icon name="door" class="h-4 w-4" /> Discharge patient</a>
                @endcan
            @else
                <a href="{{ route('admissions.report', $admission) }}" class="btn-primary"><x-icon name="file" class="h-4 w-4" /> Inpatient report</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="panel p-4"><p class="text-[12px] font-medium uppercase tracking-wide text-muted">Status</p><div class="mt-1"><x-status :value="$admission->status" /></div></div>
        <div class="panel p-4"><p class="text-[12px] font-medium uppercase tracking-wide text-muted">Ward and bed</p><p class="mt-1 font-medium">{{ $admission->ward ? $admission->ward->name.', bed '.$admission->bed?->bed_number : 'Not allocated' }}</p></div>
        <div class="panel p-4"><p class="text-[12px] font-medium uppercase tracking-wide text-muted">Passport number</p><p class="mono mt-1 font-medium">{{ $patient->passport_number }}</p></div>
        <div class="panel p-4"><p class="text-[12px] font-medium uppercase tracking-wide text-muted">Allergies</p><p class="mt-1 {{ $patient->allergies ? 'font-medium text-red-700' : '' }}">{{ $patient->allergies ?? 'None recorded' }}</p></div>
    </div>

    <section class="panel mb-6 p-5">
        <p class="text-[12px] font-medium uppercase tracking-wide text-muted">Reason for admission</p>
        <p class="mt-1 text-sm">{{ $admission->admission_reason }}</p>
    </section>

    {{-- Bed allocation --}}
    @if ($admission->status === AdmissionStatus::AwaitingBed)
        @can('allocateBed', $admission)
            <form method="POST" action="{{ route('admissions.allocate-bed', $admission) }}" class="panel mb-6">
                @csrf
                <div class="panel-header"><h2 class="panel-title">Allocate a bed</h2><span class="text-[13px] text-muted">Only wards suitable for a {{ strtolower($patient->sex->label()) }} patient are shown{{ $admission->preferred_ward_type ? '. The doctor prefers '.$admission->preferred_ward_type : '' }}.</span></div>
                @if ($wards->sum(fn ($ward) => $ward->beds->count()) === 0)
                    <x-empty title="No bed is available" icon="bed">All suitable beds are occupied or under maintenance. The patient stays on the waiting list.</x-empty>
                @else
                    <div class="space-y-4 p-5">
                        @foreach ($wards as $ward)
                            @continue($ward->beds->isEmpty())
                            <fieldset>
                                <legend class="mb-2 text-sm font-medium">{{ $ward->name }} <span class="font-normal text-muted">{{ $ward->ward_type }}, {{ $ward->beds->count() }} available</span></legend>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($ward->beds as $bed)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="bed_id" value="{{ $bed->id }}" class="peer sr-only" required>
                                            <span class="mono block border border-line px-3 py-2 text-sm peer-checked:border-brand-700 peer-checked:bg-brand-700 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600 hover:border-brand-600">{{ $bed->bed_number }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                        @error('bed_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end border-t border-line px-5 py-3"><button type="submit" class="btn-primary">Allocate bed</button></div>
                @endif
            </form>
        @endcan
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Vital signs --}}
            <section class="panel" x-data="{ adding: {{ $errors->hasAny(['temperature', 'weight']) ? 'true' : 'false' }} }">
                <div class="panel-header">
                    <h2 class="panel-title">Vital signs</h2>
                    @if ($open && $user->can('recordVitals', $admission))
                        <button type="button" class="btn-secondary btn-sm" @click="adding = !adding"><x-icon name="plus" class="h-4 w-4" /> Record vital signs</button>
                    @endif
                </div>
                @if ($open && $user->can('recordVitals', $admission))
                    <form method="POST" action="{{ route('admissions.vitals', $admission) }}" x-show="adding" x-cloak class="border-b border-line bg-paper">
                        @csrf
                        <div class="p-5">@include('partials.vitals-fields')</div>
                        <div class="flex justify-end gap-2 px-5 pb-4"><button type="button" class="btn-secondary" @click="adding = false">Close</button><button type="submit" class="btn-primary">Save vital signs</button></div>
                    </form>
                @endif
                @if ($admission->vitals->isEmpty())
                    <p class="px-5 py-6 text-center text-sm text-muted">No vital signs recorded during this admission.</p>
                @else
                    <div class="overflow-x-auto"><table class="table">
                        <thead><tr><th>Recorded</th><th class="text-right">Temperature</th><th class="text-right">Blood pressure</th><th class="text-right">Pulse</th><th class="text-right">Breathing</th><th class="text-right">Oxygen</th><th>By</th></tr></thead>
                        <tbody>
                            @foreach ($admission->vitals->sortByDesc('recorded_at') as $vital)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $vital->recorded_at->format('j M H:i') }}</td>
                                    <td class="text-right tabular-nums {{ $vital->temperature >= 38 ? 'font-medium text-red-700' : '' }}">{{ $vital->temperature }} °C</td>
                                    <td class="text-right tabular-nums">{{ $vital->bloodPressure() }}</td>
                                    <td class="text-right tabular-nums">{{ $vital->pulse_rate }}</td>
                                    <td class="text-right tabular-nums">{{ $vital->respiratory_rate }}</td>
                                    <td class="text-right tabular-nums">{{ $vital->oxygen_saturation ? $vital->oxygen_saturation.'%' : '' }}</td>
                                    <td>{{ $vital->recordedBy?->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                @endif
            </section>

            {{-- Notes and doses --}}
            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Care record</h2></div>
                @if ($open && $user->can('writeNote', $admission))
                    <form method="POST" action="{{ route('admissions.notes', $admission) }}" class="border-b border-line p-5">
                        @csrf
                        <x-field.textarea name="note" label="Add a progress note" rows="3" placeholder="Condition, response to treatment and plan" required />
                        <div class="mt-3 flex justify-end"><button type="submit" class="btn-primary btn-sm">Save note</button></div>
                    </form>
                @endif
                @forelse ($timeline as $entry)
                    <div class="border-b border-line px-5 py-3 last:border-b-0">
                        <p class="text-[13px] text-muted"><span class="font-medium text-ink">{{ $entry['type'] }}</span>, {{ $entry['at']->format('j M Y H:i') }}{{ $entry['by'] ? ', '.$entry['by'] : '' }}</p>
                        <p class="mt-1 whitespace-pre-line text-sm">{{ $entry['text'] }}</p>
                    </div>
                @empty
                    <p class="px-5 py-6 text-center text-sm text-muted">No notes or doses recorded yet.</p>
                @endforelse
            </section>
        </div>

        <aside class="space-y-6">
            {{-- Medication --}}
            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Medication</h2></div>
                @forelse ($admission->prescriptions as $prescription)
                    <div class="border-b border-line px-5 py-3">
                        <div class="flex items-center justify-between gap-2 text-[13px] text-muted"><span>{{ $prescription->created_at->format('j M H:i') }}, {{ $prescription->prescriber?->name }}</span><x-status :value="$prescription->status" /></div>
                        <ul class="mt-1 space-y-0.5 text-sm">
                            @foreach ($prescription->items as $item)<li><span class="font-medium">{{ $item->medicine_name }}</span> <span class="text-muted">{{ $item->directions() }}</span></li>@endforeach
                        </ul>
                    </div>
                @empty
                    <p class="border-b border-line px-5 py-4 text-sm text-muted">No medication prescribed during this admission.</p>
                @endforelse

                @if ($open && $user->can('recordMedication', $admission) && $activeItems->isNotEmpty())
                    <form method="POST" action="{{ route('admissions.medication', $admission) }}" class="space-y-3 p-5">
                        @csrf
                        <p class="text-sm font-medium">Record a dose given</p>
                        <x-field.select name="prescription_item_id" label="Medicine" :options="$activeItems->mapWithKeys(fn ($item) => [$item->id => $item->medicine_name.', '.$item->dosage])->all()" required />
                        <x-field.input name="given_at" label="Time given" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" required />
                        <x-field.input name="notes" label="Notes" />
                        <button type="submit" class="btn-primary btn-sm w-full">Record dose</button>
                    </form>
                @endif
            </section>

            @if ($open && $user->can('prescribe', $admission))
                <form method="POST" action="{{ route('admissions.prescriptions', $admission) }}" class="panel" x-data="{ open: {{ $errors->has('items') || $errors->has('items.*') ? 'true' : 'false' }} }">
                    @csrf
                    <div class="panel-header"><h2 class="panel-title">New prescription</h2><button type="button" class="link text-sm" @click="open = !open" x-text="open ? 'Close' : 'Prescribe'"></button></div>
                    <div x-show="open" x-cloak>
                        @include('partials.prescription-items', ['compact' => true])
                        <div class="px-5 pb-2"><x-field.input name="notes" label="Note to the pharmacy" /></div>
                        <div class="border-t border-line px-5 py-3"><button type="submit" class="btn-primary w-full">Send to pharmacy</button></div>
                    </div>
                </form>
            @endif
        </aside>
    </div>
</x-layouts.app>
