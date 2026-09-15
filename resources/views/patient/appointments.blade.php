<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Patient · Appointments</p>
            <h2 class="text-2xl font-extrabold leading-tight">Book &amp; manage appointments</h2>
        </div>
    </x-slot>
    <form method="POST" action="{{ route('patient.appointments.store') }}" class="dhp-card dhp-card-pad mb-6 max-w-xl">
        @csrf
        <label class="dhp-label" for="scheduled_at">Date &amp; time</label>
        <input id="scheduled_at" type="datetime-local" name="scheduled_at" required class="dhp-input" />
        <label class="dhp-label mt-3" for="reason">Reason</label>
        <input id="reason" name="reason" class="dhp-input" maxlength="255" />
        <button class="btn-primary mt-4">Book appointment</button>
    </form>
    <div class="dhp-table-wrap max-w-3xl">
        <table class="dhp-table">
            <thead><tr><th>When</th><th>Status</th><th>Reason</th></tr></thead>
            <tbody>
                @forelse ($appointments as $a)
                    <tr><td class="text-xs">{{ $a->scheduled_at }}</td><td>{{ ucfirst($a->status) }}</td><td class="text-xs">{{ $a->reason ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-slate-500">No appointments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $appointments->links() }}</div>
</x-app-layout>
