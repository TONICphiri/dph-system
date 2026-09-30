<x-dhp.layout title="Backups" :nav="[['label' => 'Administration', 'url' => route('dhp.admin.dashboard')], ['label' => 'Users', 'url' => route('dhp.admin.users.index')], ['label' => 'Facilities', 'url' => route('dhp.admin.facilities.index')], ['label' => 'Audit Log', 'url' => route('dhp.admin.audit-logs.index')], ['label' => 'Backups', 'url' => route('dhp.admin.backups.index')]]">
    <div class="panel">
        <div class="panel-header">
            <h1 class="panel-title">Backups</h1>
            <form method="POST" action="{{ route('dhp.admin.backups.run') }}" class="no-print js-confirm" data-confirm="Start an encrypted database backup now?">
                @csrf
                <button type="submit" class="btn-primary btn-sm">Run Backup Now</button>
            </form>
        </div>
        <div class="panel-body space-y-6">
            <section class="grid gap-3 sm:grid-cols-3">
                <div class="border border-line px-4 py-3">
                    <p class="text-[12px] uppercase tracking-wide text-muted">Last successful backup</p>
                    <p class="text-sm font-semibold">{{ $lastSuccess?->completed_at?->format('j M Y H:i') ?? 'Never' }}</p>
                </div>
                <div class="border border-line px-4 py-3">
                    <p class="text-[12px] uppercase tracking-wide text-muted">Last run status</p>
                    <p class="text-sm font-semibold capitalize">{{ $lastRun?->status ?? 'Never run' }}</p>
                </div>
                <div class="border border-line px-4 py-3">
                    <p class="text-[12px] uppercase tracking-wide text-muted">Last archive size</p>
                    <p class="text-sm font-semibold">{{ $lastRun?->archive_size_bytes ? number_format($lastRun->archive_size_bytes / 1024, 1).' KB' : '—' }}</p>
                </div>
            </section>
            <section>
                <h2 class="text-sm font-semibold">Recent backup runs</h2>
                <div class="mt-2 overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Started</th><th>Completed</th><th>Status</th><th>Size</th><th>Email sent</th><th>Stage</th></tr></thead>
                        <tbody>
                            @forelse ($runs as $run)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $run->started_at?->format('j M Y H:i') ?? '—' }}</td>
                                    <td class="whitespace-nowrap">{{ $run->completed_at?->format('j M Y H:i') ?? '—' }}</td>
                                    <td><x-badge :tone="$run->status === 'success' ? 'success' : 'danger'">{{ ucfirst($run->status) }}</x-badge></td>
                                    <td>{{ $run->archive_size_bytes ? number_format($run->archive_size_bytes / 1024, 1).' KB' : '—' }}</td>
                                    <td>{{ $run->email_attached ? 'Yes' : 'No' }}</td>
                                    <td>{{ $run->failure_stage && $run->status === 'failed' && $run->failure_stage !== 'in_progress' ? $run->failure_stage : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">No backup activity has been recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="field-help">Archives are encrypted and stored on secure server storage. They are never downloadable here.</p>
            </section>
        </div>
    </div>
    <script>
        document.querySelectorAll('form.js-confirm').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!confirm(form.dataset.confirm || 'Are you sure?')) e.preventDefault();
            });
        });
    </script>
</x-dhp.layout>
