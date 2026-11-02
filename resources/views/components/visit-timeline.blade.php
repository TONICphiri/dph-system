@props(['visit', 'highlightNext' => false])
@php
    $stageTypes = \App\Enums\VisitStageType::cases();
    $nextStage = $visit->nextStage();
    $positionLabel = fn (?string $value) => $value ? \App\Enums\Position::tryFrom($value)?->label() : null;

    $stageDetail = fn (\App\Enums\VisitStageType $type, \App\Models\VisitStage $stage) => match ($type) {
        \App\Enums\VisitStageType::Reception => $stage->summary,
        \App\Enums\VisitStageType::Triage => $stage->summary,
        \App\Enums\VisitStageType::Consultation => $stage->summary,
        \App\Enums\VisitStageType::Dispensing => $stage->summary,
        default => null,
    };
@endphp

<div {{ $attributes }}>
    @foreach ($stageTypes as $type)
        @php $stage = $visit->stage($type); $isNext = $nextStage !== null && $nextStage === $type; @endphp
        <div class="border-b border-line px-5 py-3 last:border-b-0 {{ $isNext ? 'bg-brand-50 border-brand-200' : '' }}">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium {{ $isNext ? 'text-brand-700' : 'text-slate-900' }}">{{ $type->label() }}</p>
                    @if ($isNext)
                        <x-badge tone="info" class="text-[10px]">Next</x-badge>
                    @endif
                </div>
                @if ($stage)
                    <x-badge tone="success">Recorded</x-badge>
                @else
                    <x-badge tone="neutral">{{ $isNext ? 'Next up' : 'Waiting' }}</x-badge>
                @endif
            </div>

            @if ($stage)
                @if ($detail = $stageDetail($type, $stage))
                    <p class="mt-1 text-sm">{{ $detail }}</p>
                @endif

                @if ($type === \App\Enums\VisitStageType::Triage && ! empty($stage->payload['tests']))
                    <p class="mt-1 text-[13px] text-muted font-semibold">Tests</p>
                    <ul class="mt-1 space-y-0.5 text-sm">
                        @foreach ($stage->payload['tests'] as $test)
                            <li><span class="font-medium">{{ $test['name'] }}</span>@if (! empty($test['result'])): <span class="text-muted"> – {{ $test['result'] }}</span>@endif</li>
                        @endforeach
                    </ul>
                @endif

                @if ($type === \App\Enums\VisitStageType::Consultation && ! empty($stage->payload['treatment']))
                    <p class="mt-1 text-[13px] text-muted font-semibold">Treatment plan</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $stage->payload['treatment'] }}</p>
                @endif

                <p class="mt-1 text-[13px] text-muted">
                    Recorded by {{ $stage->recordedBy?->name ?? 'an unknown user' }}@if ($positionLabel($stage->recorded_as_position)), {{ $positionLabel($stage->recorded_as_position) }}@endif
                    on {{ $stage->recorded_at->format('j M Y, H:i') }}
                </p>

                @if ($stage->edited_at)
                    <p class="text-[13px] text-muted">Changed by {{ $stage->editedBy?->name ?? 'an unknown user' }} on {{ $stage->edited_at->format('j M Y, H:i') }}</p>
                @endif
            @elseif ($isNext)
                @php $nextPosition = \App\Enums\Position::tryFrom(config("health_passport.stage_positions.{$type->value}")[0] ?? null)?->label(); @endphp
                <p class="mt-1 text-sm text-muted">Awaiting {{ $nextPosition ?? $type->label() }} to record this stage.</p>
            @endif
        </div>
    @endforeach

    @if ($visit->observations->isNotEmpty())
        <div class="border-b border-line px-5 py-3">
            <p class="text-sm font-medium">Vital signs</p>
            <ul class="mt-1 space-y-1 text-sm">
                @foreach ($visit->latestObservations() as $observation)
                    <li>
                        {{ $observation->label() }}: <span class="font-medium">{{ $observation->value }}</span>@if ($observation->unit()) {{ $observation->unit() }}@endif
                        <span class="text-[13px] text-muted"> by {{ $observation->recordedBy?->name ?? 'an unknown user' }}@if ($positionLabel($observation->recorded_as_position)), {{ $positionLabel($observation->recorded_as_position) }}@endif, {{ $observation->recorded_at->format('j M Y, H:i') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($visit->medications->isNotEmpty())
        <div class="px-5 py-3">
            <p class="text-sm font-medium">Medicines</p>
            <ul class="mt-1 space-y-2 text-sm">
                @foreach ($visit->medications as $medication)
                    <li class="flex items-start justify-between gap-3">
                        <span><span class="font-medium">{{ $medication->name }}</span> <span class="text-muted">{{ $medication->directions() }}</span></span>
                        <span class="shrink-0 text-[13px] text-muted">
                            @if ($medication->dispensed)
                                Handed over{{ $medication->dispensedBy ? ' by '.$medication->dispensedBy->name : '' }}
                            @else
                                Waiting
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
