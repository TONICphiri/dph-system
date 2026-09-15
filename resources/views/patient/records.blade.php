<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Patient · My records</p>
            <h2 class="text-2xl font-extrabold leading-tight">My medical details</h2>
            <p class="text-sm text-dhp-100">{{ Auth::user()->masked_nin }} · Protected by two-factor authentication.</p>
        </div>
    </x-slot>

    @if (!$patient)
        <div class="dhp-card dhp-card-pad max-w-xl">
            <h3 class="dhp-section-title">No file linked yet</h3>
            <p class="mt-2 text-sm text-slate-600">Your account is not linked to a clinical file. Visit your registered facility with your national ID and ask the staff to link your health passport file to this account.</p>
            <a href="{{ route('patient.credential') }}" class="btn-secondary mt-4">Back to My Passport</a>
        </div>
    @else
        <section class="dhp-card dhp-card-pad mb-6 max-w-3xl">
            <h3 class="dhp-section-title">{{ $patient->full_name }}</h3>
            <p class="dhp-section-sub">DHP ID {{ $patient->dhp_id }} · Age {{ $patient->age ?? 'N/A' }} · {{ $patient->gender ?? '—' }}</p>
        </section>

        <section class="dhp-card dhp-card-pad mb-6 max-w-3xl">
            <h3 class="dhp-section-title">Visits</h3>
            @forelse ($encounters as $encounter)
                <div class="border-b border-[#E7F0F0] py-3 last:border-0">
                    <p class="font-semibold text-dhp-900">{{ ucfirst($encounter->encounter_type) }} · {{ $encounter->encounter_date?->format('d M Y') }}</p>
                    <p class="text-sm text-slate-600">{{ $encounter->facility?->name ?? '—' }}</p>
                    @if ($encounter->chief_complaint)<p class="mt-1 text-sm"><strong>Complaint:</strong> {{ $encounter->chief_complaint }}</p>@endif
                    @if ($encounter->diagnosis)<p class="text-sm"><strong>Diagnosis:</strong> {{ $encounter->diagnosis }}</p>@endif
                    @if ($encounter->vitals->count())
                        <p class="mt-1 text-xs text-slate-500">Vitals:
                            @foreach ($encounter->vitals as $vital)
                                T:{{ $vital->temperature ?? '—' }}°C · BP:{{ $vital->systolic_bp ?? '—' }}/{{ $vital->diastolic_bp ?? '—' }} · HR:{{ $vital->heart_rate ?? '—' }}{{ !$loop->last ? ' | ' : '' }}
                            @endforeach
                        </p>
                    @endif
                    @if ($encounter->prescriptions->count())
                        <ul class="mt-1 list-disc pl-5 text-sm">
                            @foreach ($encounter->prescriptions as $rx)
                                <li>{{ $rx->medication_name }} — {{ $rx->dose ?? '' }} {{ $rx->frequency ?? '' }} ({{ ucfirst($rx->status) }})</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No visits recorded yet.</p>
            @endforelse
        </section>

        <div class="grid gap-6 lg:grid-cols-2 max-w-3xl">
            <section class="dhp-card dhp-card-pad">
                <h3 class="dhp-section-title">Admissions</h3>
                @forelse ($admissions as $admission)
                    <div class="border-b border-[#E7F0F0] py-2 text-sm last:border-0">
                        <p class="font-semibold">{{ $admission->ward_name ?? 'Ward' }} · Bed {{ $admission->bed_number ?? '—' }}</p>
                        <p class="text-xs text-slate-500">{{ $admission->admitted_at?->format('d M Y') }} · {{ ucfirst($admission->status) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No admissions.</p>
                @endforelse
            </section>

            <section class="dhp-card dhp-card-pad">
                <h3 class="dhp-section-title">Lab orders</h3>
                @forelse ($labOrders as $order)
                    <div class="border-b border-[#E7F0F0] py-2 text-sm last:border-0">
                        <p class="font-semibold">{{ $order->test_name }}</p>
                        <p class="text-xs text-slate-500">{{ ucfirst($order->status) }} · {{ $order->requested_at?->format('d M Y') }}</p>
                        @if ($order->status === 'results' && $order->result_value)
                            <p class="text-sm">Result: <strong>{{ $order->result_value }}</strong> {{ $order->result_units ?? '' }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No lab orders.</p>
                @endforelse
            </section>
        </div>
    @endif
</x-app-layout>
