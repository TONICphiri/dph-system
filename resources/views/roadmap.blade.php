@php
    $items = [
        ['icon' => 'box', 'title' => 'Payments', 'text' => 'Mobile money and card payments for services at private and mission facilities, with receipts stored in the patient record.', 'needs' => 'Agreements with mobile money and bank payment providers, and a finance module for facilities.'],
        ['icon' => 'info', 'title' => 'Artificial intelligence assistant', 'text' => 'A chat assistant in the patient portal that answers common questions about appointments, vaccination schedules and reminders in plain language.', 'needs' => 'Clinical review of every answer and clear limits so the service never replaces a health worker.'],
        ['icon' => 'shield', 'title' => 'Medical insurance', 'text' => 'Verification of insurance membership at check in and electronic claims to medical aid schemes.', 'needs' => 'Data sharing agreements with insurers and a claims format agreed with the Ministry of Health.'],
    ];
@endphp
<x-layouts.app title="Future development">
    <x-page-header title="Future development" description="Features planned after the current release. They are not active in this system." />
    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($items as $item)
            <section class="panel flex flex-col">
                <div class="flex items-center gap-3 border-b border-line px-5 py-4">
                    <span class="flex h-9 w-9 items-center justify-center border border-line bg-paper text-brand-700"><x-icon :name="$item['icon']" class="h-5 w-5" /></span>
                    <h2 class="font-semibold">{{ $item['title'] }}</h2>
                    <span class="badge-neutral ml-auto">Planned</span>
                </div>
                <div class="flex-1 space-y-3 p-5 text-sm">
                    <p>{{ $item['text'] }}</p>
                    <p class="text-muted"><span class="font-medium text-ink">Required first:</span> {{ $item['needs'] }}</p>
                </div>
            </section>
        @endforeach
    </div>
</x-layouts.app>
