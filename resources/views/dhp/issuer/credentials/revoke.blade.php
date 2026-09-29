<x-dhp.layout title="Revoke Credential" :nav="[['label' => 'Search Citizen', 'url' => route('dhp.issuer.citizens.search')]]">
    <div class="panel mx-auto max-w-xl">
        <div class="panel-header">
            <h1 class="panel-title">Revoke Credential <span class="mono">{{ $credential->credential_number }}</span></h1>
        </div>
        <div class="panel-body">
            <p class="text-sm text-muted">{{ $credential->citizen->full_name }} · {{ $credential->type->label() }} · issued {{ $credential->issue_date?->format('j M Y') }}</p>
            <form id="revoke-form" method="POST" action="{{ route('dhp.issuer.credentials.revoke', $credential) }}" class="mt-4 grid gap-4">
                @csrf
                <div><label class="label" for="reason">Reason *</label>
                    <select id="reason" name="reason" required class="input">
                        <option value="">—</option>
                        @foreach ($reasons as $reason)
                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ ucfirst(str_replace('_', ' ', $reason)) }}</option>
                        @endforeach
                    </select>
                    @error('reason')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="reason_note">Explanation (only for “other”)</label><textarea id="reason_note" name="reason_note" rows="2" class="input">{{ old('reason_note') }}</textarea>@error('reason_note')<p class="field-error">{{ $message }}</p>@enderror</div>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="replace" value="1" @checked(old('replace')) class="mt-1 h-4 w-4 border-line text-brand-700"> Revoke and create a replacement (you will review it before submitting)</label>
                <div><button type="submit" class="btn-danger">Revoke credential</button></div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('revoke-form').addEventListener('submit', function (e) {
            if (!confirm('Revoke this credential? Its QR code will stop validating.')) e.preventDefault();
        });
    </script>
</x-dhp.layout>
