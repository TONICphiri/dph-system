<x-layouts.app title="Facility reports">
    <x-page-header :title="'Reports for '.$facility->name" :description="'From '.$from->format('j F Y').' to '.$to->format('j F Y').'.'">
        <x-slot:actions><button type="button" onclick="window.print()" class="btn-secondary no-print"><x-icon name="printer" class="h-4 w-4" /> Print</button></x-slot:actions>
    </x-page-header>

    <form method="GET" class="panel no-print mb-6 flex flex-wrap items-end gap-3 p-4">
        <div><label for="from" class="label">From</label><input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input"></div>
        <div><label for="to" class="label">To</label><input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input"></div>
        <button type="submit" class="btn-primary">Show report</button>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Visits" :value="$report['total_visits']" icon="clipboard" :hint="$report['outpatient_visits'].' outpatient, '.$report['completed_visits'].' completed'" />
        <x-stat label="Admissions" :value="$report['admissions']" icon="bed" :hint="$report['discharges'].' discharged'" />
        <x-stat label="Vaccinations" :value="$report['vaccinations']" icon="syringe" />
        <x-stat label="Bed occupancy today" :value="$report['occupancy_rate'].'%'" icon="chart" :hint="$report['occupied_beds'].' of '.$report['total_beds'].' beds'" />
    </div>

    <section class="panel mt-6">
        <div class="panel-header"><h2 class="panel-title">Visits per day</h2></div>
        @php $max = max(1, $report['daily_visits']->max() ?? 1); @endphp
        @if ($report['daily_visits']->isEmpty())
            <x-empty title="No visits in this period" icon="chart" />
        @else
            <div class="flex h-48 items-end gap-1 overflow-x-auto px-5 pb-2 pt-4" role="img" aria-label="Visits per day">
                @foreach ($report['daily_visits'] as $day => $total)
                    <div class="flex min-w-[18px] flex-1 flex-col items-center justify-end gap-1" title="{{ \Illuminate\Support\Carbon::parse($day)->format('j M') }}: {{ $total }} visits">
                        <span class="text-[11px] tabular-nums text-muted">{{ $total }}</span>
                        <div class="w-full bg-brand-600" style="height: {{ max(4, round($total / $max * 140)) }}px"></div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between border-t border-line px-5 py-2 text-[12px] text-muted">
                <span>{{ \Illuminate\Support\Carbon::parse($report['daily_visits']->keys()->first())->format('j M') }}</span>
                <span>{{ \Illuminate\Support\Carbon::parse($report['daily_visits']->keys()->last())->format('j M') }}</span>
            </div>
        @endif
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Most common diagnoses</h2></div>
            @include('partials.ranked-list', ['rows' => $report['top_diagnoses'], 'labelKey' => 'diagnosis', 'unit' => 'visits'])
        </section>
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Most dispensed medicines</h2></div>
            @include('partials.ranked-list', ['rows' => $report['top_medicines'], 'labelKey' => 'medicine_name', 'unit' => 'units'])
        </section>
    </div>
</x-layouts.app>
