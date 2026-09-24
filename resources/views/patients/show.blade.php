@php
    use App\Enums\Permission;
    use App\Enums\VisitStatus;
    $user = auth()->user();
    $tabs = collect([
        'visits' => isset($visits) ? 'Visits' : null,
        'vitals' => isset($vitals) ? 'Vital signs' : null,
        'admissions' => isset($admissions) ? 'Admissions' : null,
        'prescriptions' => isset($prescriptions) ? 'Prescriptions' : null,
        'vaccinations' => isset($vaccinations) ? 'Vaccinations' : null,
        'reminders' => isset($reminders) ? 'Reminders' : null,
    ])->filter();
@endphp
<x-layouts.app :title="$patient->full_name">
    <x-page-header :title="$patient->full_name" :description="$patient->sex->label().', '.$patient->age_label.'. Registered at '.($patient->registeredFacility?->name ?? 'an unknown facility').'.'">
        <x-slot:breadcrumb><a href="{{ route('patients.index') }}" class="hover:text-brand-700">Patients</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Record</x-slot:breadcrumb>
        <x-slot:actions>
            @can('printCard', $patient)
                <a href="{{ route('patients.card', $patient) }}" class="btn-secondary" target="_blank"><x-icon name="printer" class="h-4 w-4" /> Print card</a>
            @endcan
            @can('update', $patient)
                <a href="{{ route('patients.edit', $patient) }}" class="btn-secondary"><x-icon name="edit" class="h-4 w-4" /> Edit details</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Current visit at this facility, or check in --}}
    @if ($openVisit)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border border-brand-200 bg-brand-50 px-5 py-3">
            <p class="text-sm"><span class="font-medium">Visit in progress:</span> {{ $openVisit->reason_for_visit }}. Checked in {{ $openVisit->checked_in_at->format('H:i') }}. <x-status :value="$openVisit->status" /></p>
            <div class="flex gap-2">
                @if ($openVisit->status === VisitStatus::WaitingForVitals && $user->can(Permission::RecordVitals->value))
                    <a href="{{ route('vitals.create', $openVisit) }}" class="btn-primary btn-sm">Record vital signs</a>
                @endif
                @if (in_array($openVisit->status, [VisitStatus::WaitingForVitals, VisitStatus::WaitingForDoctor]) && $user->can(Permission::ConductConsultations->value))
                    <a href="{{ route('consultations.create', $openVisit) }}" class="btn-primary btn-sm">Start consultation</a>
                @endif
            </div>
        </div>
    @elseif ($user->can(Permission::CheckInPatients->value) && $patient->status === \App\Enums\PatientStatus::Active)
        <form method="POST" action="{{ route('visits.store', $patient) }}" class="panel mb-6 flex flex-wrap items-end gap-3 p-4">
            @csrf
            <div class="min-w-[260px] flex-1">
                <x-field.input name="reason_for_visit" label="Check in for a visit" placeholder="Reason for visit, for example fever and headache" required />
            </div>
            <button type="submit" class="btn-primary"><x-icon name="door" class="h-4 w-4" /> Check in</button>
        </form>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Passport summary --}}
        <div class="space-y-6">
            <section class="panel">
                <div class="flex gap-4 p-5">
                    <div class="shrink-0 border border-line bg-white p-1.5">{!! $qrCode !!}</div>
                    <div class="min-w-0">
                        <p class="text-[12px] font-medium uppercase tracking-wide text-muted">Passport number</p>
                        <p class="mono text-base font-semibold text-ink">{{ $patient->passport_number }}</p>
                        <p class="mt-2 text-[12px] font-medium uppercase tracking-wide text-muted">National ID</p>
                        <p class="mono">{{ $patient->national_id ?? 'Not issued' }}</p>
                        <div class="mt-2"><x-status :value="$patient->status" /></div>
                    </div>
                </div>
                <dl class="detail-list border-t border-line p-5">
                    <div><dt>Date of birth</dt><dd>{{ $patient->date_of_birth->format('j F Y') }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $patient->phone ?? 'Not recorded' }}</dd></div>
                    <div><dt>District</dt><dd>{{ $patient->district?->name }}</dd></div>
                    <div><dt>Village</dt><dd>{{ collect([$patient->village, $patient->traditional_authority ? 'T/A '.$patient->traditional_authority : null])->filter()->join(', ') ?: 'Not recorded' }}</dd></div>
                    @if ($patient->occupation)<div><dt>Occupation</dt><dd>{{ $patient->occupation }}</dd></div>@endif
                    @if ($patient->email)<div><dt>Email</dt><dd class="break-all">{{ $patient->email }}</dd></div>@endif
                </dl>
            </section>

            @isset($vitals)
                <section class="panel">
                    <div class="panel-header"><h2 class="panel-title">Health summary</h2></div>
                    <dl class="detail-list p-5">
                        <div><dt>Blood group</dt><dd>{{ $patient->blood_group ?? 'Not known' }}</dd></div>
                        <div><dt>Allergies</dt><dd class="{{ $patient->allergies ? 'font-medium text-red-700' : '' }}">{{ $patient->allergies ?? 'None recorded' }}</dd></div>
                        <div class="sm:col-span-2"><dt>Long term conditions</dt><dd>{{ $patient->chronic_conditions ?? 'None recorded' }}</dd></div>
                        @if ($patient->disabilities)<div class="sm:col-span-2"><dt>Disabilities</dt><dd>{{ $patient->disabilities }}</dd></div>@endif
                        @if ($patient->health_notes)<div class="sm:col-span-2"><dt>Notes</dt><dd>{{ $patient->health_notes }}</dd></div>@endif
                    </dl>
                </section>
            @endisset

            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Emergency contacts</h2></div>
                @forelse ($patient->emergencyContacts as $contact)
                    <div class="border-b border-line px-5 py-3 last:border-b-0">
                        <p class="font-medium">{{ $contact->full_name }} <span class="font-normal text-muted">{{ $contact->relationship }}</span></p>
                        <p class="text-sm">{{ $contact->phone }}</p>
                    </div>
                @empty
                    <p class="px-5 py-4 text-sm text-muted">No emergency contact recorded.</p>
                @endforelse
            </section>

            @if ($patient->mother || $patient->children->isNotEmpty())
                <section class="panel">
                    <div class="panel-header"><h2 class="panel-title">Family</h2></div>
                    @if ($patient->mother)
                        <div class="px-5 py-3"><p class="text-[12px] font-medium uppercase tracking-wide text-muted">Mother</p><x-patient-cell :patient="$patient->mother" /></div>
                    @endif
                    @foreach ($patient->children as $child)
                        <div class="border-t border-line px-5 py-3"><p class="text-[12px] font-medium uppercase tracking-wide text-muted">Child, {{ $child->age_label }}</p><x-patient-cell :patient="$child" /></div>
                    @endforeach
                    @can(Permission::RegisterPatients->value)
                        @if ($patient->sex === \App\Enums\Sex::Female && ! $patient->isChild())
                            <div class="border-t border-line px-5 py-3"><a href="{{ route('patients.create', ['mother' => $patient->id]) }}" class="link text-sm">Register a child of this patient</a></div>
                        @endif
                    @endcan
                </section>
            @endif

            @can(Permission::RegisterPatients->value)
                @if (! $patient->portalAccount)
                    <section class="panel p-5">
                        <p class="font-medium">No patient portal account</p>
                        <p class="mt-1 text-sm text-muted">With an account the patient can book appointments and view records online.</p>
                        @if ($patient->email)
                            <form method="POST" action="{{ route('patients.portal-account', $patient) }}" class="mt-3">@csrf<button type="submit" class="btn-secondary btn-sm">Create portal account</button></form>
                        @else
                            <p class="mt-2 text-sm text-gold-700">Add an email address to the patient details first.</p>
                        @endif
                    </section>
                @endif
            @endcan
        </div>

        {{-- Medical history, shown only to roles allowed to see it --}}
        <div class="xl:col-span-2">
            @if ($tabs->isEmpty())
                <section class="panel">
                    <x-empty title="Medical history is not available to your role" icon="shield">Your role can see registration and personal details only.</x-empty>
                </section>
            @else
                <section class="panel" x-data="{ tab: '{{ $tabs->keys()->first() }}' }">
                    <div class="flex overflow-x-auto border-b border-line" role="tablist">
                        @foreach ($tabs as $key => $label)
                            <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                                class="-mb-px whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium"
                                :class="tab === '{{ $key }}' ? 'border-brand-700 text-brand-800' : 'border-transparent text-muted hover:text-ink'">{{ $label }}</button>
                        @endforeach
                    </div>

                    @isset($visits)
                        <div x-show="tab === 'visits'">
                            @if ($visits->isEmpty()) <x-empty title="No visits recorded" icon="clipboard" />
                            @else
                                <div class="overflow-x-auto"><table class="table">
                                    <thead><tr><th>Date</th><th>Facility</th><th>Reason</th><th>Diagnosis</th><th>Status</th><th></th></tr></thead>
                                    <tbody>
                                        @foreach ($visits as $visit)
                                            <tr>
                                                <td class="whitespace-nowrap">{{ $visit->checked_in_at->format('j M Y') }}</td>
                                                <td>{{ $visit->facility->name }}</td>
                                                <td>{{ $visit->reason_for_visit }}</td>
                                                <td>{{ isset($prescriptions) ? ($visit->diagnosis ?? '') : 'Restricted' }}</td>
                                                <td><x-status :value="$visit->status" /></td>
                                                <td class="text-right">@if (isset($prescriptions) && $visit->consulted_at)<a href="{{ route('visits.report', $visit) }}" class="link text-sm">Report</a>@endif</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table></div>
                            @endif
                        </div>
                    @endisset

                    @isset($vitals)
                        <div x-show="tab === 'vitals'" x-cloak>
                            @if ($vitals->isEmpty()) <x-empty title="No vital signs recorded" icon="activity" />
                            @else
                                <div class="overflow-x-auto"><table class="table">
                                    <thead><tr><th>Recorded</th><th class="text-right">Temperature</th><th class="text-right">Weight</th><th class="text-right">Blood pressure</th><th class="text-right">Pulse</th><th class="text-right">Oxygen</th><th>By</th></tr></thead>
                                    <tbody>
                                        @foreach ($vitals as $vital)
                                            <tr>
                                                <td class="whitespace-nowrap">{{ $vital->recorded_at->format('j M Y H:i') }}</td>
                                                <td class="text-right tabular-nums">{{ $vital->temperature }} °C</td>
                                                <td class="text-right tabular-nums">{{ $vital->weight }} kg</td>
                                                <td class="text-right tabular-nums">{{ $vital->bloodPressure() ?? '' }}</td>
                                                <td class="text-right tabular-nums">{{ $vital->pulse_rate ? $vital->pulse_rate.' per minute' : '' }}</td>
                                                <td class="text-right tabular-nums">{{ $vital->oxygen_saturation ? $vital->oxygen_saturation.'%' : '' }}</td>
                                                <td>{{ $vital->recordedBy?->name }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table></div>
                            @endif
                        </div>
                    @endisset

                    @isset($admissions)
                        <div x-show="tab === 'admissions'" x-cloak>
                            @if ($admissions->isEmpty()) <x-empty title="No admissions recorded" icon="bed" />
                            @else
                                <div class="overflow-x-auto"><table class="table">
                                    <thead><tr><th>Admitted</th><th>Facility</th><th>Ward and bed</th><th>Discharged</th><th>Status</th><th></th></tr></thead>
                                    <tbody>
                                        @foreach ($admissions as $admission)
                                            <tr>
                                                <td class="whitespace-nowrap">{{ $admission->admitted_at->format('j M Y') }}</td>
                                                <td>{{ $admission->facility->name }}</td>
                                                <td>{{ $admission->ward ? $admission->ward->name.', bed '.$admission->bed?->bed_number : 'Awaiting bed' }}</td>
                                                <td class="whitespace-nowrap">{{ $admission->discharged_at?->format('j M Y') }}</td>
                                                <td><x-status :value="$admission->status" /></td>
                                                <td class="text-right">
                                                    @if ($admission->facility_id === $user->facility_id)<a href="{{ route('admissions.show', $admission) }}" class="link text-sm">Open</a>@endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table></div>
                            @endif
                        </div>
                    @endisset

                    @isset($prescriptions)
                        <div x-show="tab === 'prescriptions'" x-cloak>
                            @forelse ($prescriptions as $prescription)
                                <div class="border-b border-line px-5 py-4 last:border-b-0">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-sm"><span class="font-medium">{{ $prescription->created_at->format('j M Y') }}</span> <span class="text-muted">by {{ $prescription->prescriber?->name }}, {{ $prescription->facility->name }}</span></p>
                                        <x-status :value="$prescription->status" />
                                    </div>
                                    <ul class="mt-2 space-y-1 text-sm">
                                        @foreach ($prescription->items as $item)
                                            <li><span class="font-medium">{{ $item->medicine_name }}</span> <span class="text-muted">{{ $item->directions() }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @empty
                                <x-empty title="No prescriptions recorded" icon="pill" />
                            @endforelse
                        </div>
                    @endisset

                    @isset($vaccinations)
                        <div x-show="tab === 'vaccinations'" x-cloak>
                            @can(Permission::RecordVaccinations->value)
                                <div class="flex justify-end border-b border-line px-5 py-3"><a href="{{ route('vaccinations.create', $patient) }}" class="btn-primary btn-sm"><x-icon name="syringe" class="h-4 w-4" /> Record vaccination</a></div>
                            @endcan
                            @if ($vaccinations->isEmpty()) <x-empty title="No vaccinations recorded" icon="syringe" />
                            @else
                                <div class="overflow-x-auto"><table class="table">
                                    <thead><tr><th>Vaccine</th><th>Dose</th><th>Given on</th><th>Facility</th><th>Next dose due</th></tr></thead>
                                    <tbody>
                                        @foreach ($vaccinations as $vaccination)
                                            <tr>
                                                <td class="font-medium">{{ $vaccination->vaccine->name }}</td>
                                                <td>{{ $vaccination->dose_number }} of {{ $vaccination->vaccine->total_doses }}</td>
                                                <td class="whitespace-nowrap">{{ $vaccination->administered_on->format('j M Y') }}</td>
                                                <td>{{ $vaccination->facility?->name }}</td>
                                                <td class="whitespace-nowrap">{{ $vaccination->next_dose_due_on?->format('j M Y') ?? 'Course complete' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table></div>
                            @endif
                        </div>
                    @endisset

                    @isset($reminders)
                        <div x-show="tab === 'reminders'" x-cloak>
                            @can(Permission::ManageReminders->value)
                                <div class="flex justify-end border-b border-line px-5 py-3"><a href="{{ route('reminders.create', $patient) }}" class="btn-primary btn-sm"><x-icon name="bell" class="h-4 w-4" /> Add reminder</a></div>
                            @endcan
                            @forelse ($reminders as $reminder)
                                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0">
                                    <div>
                                        <p class="font-medium">{{ $reminder->title }} @if ($reminder->is_confidential)<span class="badge-neutral ml-1">Confidential</span>@endif</p>
                                        <p class="text-sm text-muted">{{ $reminder->category->label() }}. Due {{ $reminder->due_on->format('j M Y') }}{{ $reminder->repeat_every_days ? ', repeats every '.$reminder->repeat_every_days.' days' : '' }}.</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <x-status :value="$reminder->status" />
                                        @if ($reminder->status === \App\Enums\ReminderStatus::Active && $user->can(Permission::ManageReminders->value))
                                            <form method="POST" action="{{ route('reminders.stop', $reminder) }}">@csrf @method('PATCH')<button type="submit" class="link text-sm">Stop</button></form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <x-empty title="No reminders set" icon="bell" />
                            @endforelse
                        </div>
                    @endisset
                </section>
            @endif
        </div>
    </div>
</x-layouts.app>
