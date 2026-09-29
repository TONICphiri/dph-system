<x-dhp.layout title="Registration Slip" :nav="[['label' => 'Search Citizen', 'url' => route('dhp.issuer.citizens.search')]]">
    <div class="panel mx-auto max-w-xl">
        <div class="panel-header">
            <h1 class="panel-title">Registration Slip</h1>
            <button type="button" onclick="window.print()" class="btn-secondary btn-sm no-print">Print Slip</button>
        </div>
        <div class="panel-body space-y-3 text-sm">
            <p class="text-[15px] font-semibold">Digital Health Passport</p>
            <dl class="detail-list">
                <div><dt>Full name</dt><dd>{{ $citizen->full_name }}</dd></div>
                <div><dt>Passport ID</dt><dd class="mono">{{ $citizen->passport_id }}</dd></div>
                <div><dt>National ID</dt><dd class="mono">{{ $citizen->maskedNationalId() ?? 'Not recorded' }}</dd></div>
                <div><dt>Date of registration</dt><dd>{{ $citizen->created_at?->format('j M Y') }}</dd></div>
                <div><dt>Registered at</dt><dd>{{ $citizen->creator?->facility?->name ?? 'Health facility' }}</dd></div>
            </dl>
            <div class="border border-line bg-paper px-4 py-3 text-[13px]">
                <p class="font-semibold">Smartphone users</p>
                <p>If you have a portal account, use your email and password to sign in.</p>
                <p class="mt-2 font-semibold">Without a smartphone</p>
                <p>Present your National ID or Passport ID at a participating health facility. A health worker will confirm your identity and access your digital health passport.</p>
            </div>
            <p class="text-[13px] text-muted">This slip only helps you find your passport. It is not a health credential.</p>
        </div>
    </div>
</x-dhp.layout>
