<x-layouts.app title="Discharge patient">
    <x-page-header :title="'Discharge '.$admission->patient->full_name" :description="'Admitted '.$admission->admitted_at->format('j F Y').' to '.($admission->ward?->name ?? 'no ward').'. On saving, the bed is released and the inpatient report is produced.'">
        <x-slot:breadcrumb><a href="{{ route('admissions.show', $admission) }}" class="hover:text-brand-700">Inpatient chart</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Discharge</x-slot:breadcrumb>
    </x-page-header>
    <form method="POST" action="{{ route('admissions.discharge.store', $admission) }}" class="panel max-w-3xl">
        @csrf
        <div class="panel-body grid gap-5">
            <x-field.select name="discharge_outcome" label="Outcome" :options="$outcomes" required />
            <x-field.textarea name="discharge_summary" label="Discharge summary" rows="5" placeholder="Diagnosis, treatment given and condition at discharge" required />
            <x-field.textarea name="follow_up_instructions" label="Follow up instructions" rows="3" placeholder="Medicines to continue, review date and warning signs" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
            <a href="{{ route('admissions.show', $admission) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Discharge and release bed</button>
        </div>
    </form>
</x-layouts.app>
