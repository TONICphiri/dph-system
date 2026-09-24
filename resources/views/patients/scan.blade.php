<x-layouts.app title="Scan passport card">
    @push('head') @vite('resources/js/scanner.js') @endpush
    <x-page-header title="Scan a health passport card" description="Point the camera at the QR code on the patient's card, or type the passport number or National ID." />

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="panel">
            <div class="panel-header"><h2 class="panel-title">Camera</h2></div>
            <div class="panel-body">
                <div id="qr-reader" class="aspect-square w-full max-w-sm border border-line bg-paper"></div>
                <p id="scanner-status" class="mt-3 text-sm text-muted" aria-live="polite">The camera starts when you press the button.</p>
                <button type="button" id="start-scan" class="btn-primary mt-3"><x-icon name="qr" class="h-4 w-4" /> Start camera</button>
            </div>
        </section>

        <form method="POST" action="{{ route('patients.lookup') }}" id="lookup-form" class="panel self-start">
            @csrf
            <div class="panel-header"><h2 class="panel-title">Enter a number</h2></div>
            <div class="panel-body">
                <x-field.input name="code" id="code" label="Passport number or National ID" placeholder="For example, MW-2026-000123-4" autocomplete="off" required />
            </div>
            <div class="border-t border-line px-5 py-3"><button type="submit" class="btn-primary">Open record</button></div>
        </form>
    </div>
</x-layouts.app>
