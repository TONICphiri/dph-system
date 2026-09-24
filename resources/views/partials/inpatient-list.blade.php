@forelse ($admissions as $admission)
    <a href="{{ route('admissions.show', $admission) }}" class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-b-0 hover:bg-brand-50">
        <div>
            <p class="font-medium">{{ $admission->patient->full_name }}</p>
            <p class="text-[13px] text-muted">{{ $admission->ward ? $admission->ward->name.', bed '.$admission->bed?->bed_number : 'No bed allocated yet' }}</p>
        </div>
        <div class="text-right">
            <x-status :value="$admission->status" />
            <p class="mt-1 text-[13px] text-muted">Day {{ $admission->lengthOfStayInDays() }}</p>
        </div>
    </a>
@empty
    <x-empty title="No patients are admitted" icon="bed" />
@endforelse
