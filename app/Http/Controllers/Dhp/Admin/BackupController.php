<?php

namespace App\Http\Controllers\Dhp\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunBackupJob;
use App\Models\BackupRun;
use App\Services\DhpAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Backup health overview. Shows safe metadata only: no filenames, paths,
 * passwords, recipients, contents or download links, ever.
 */
class BackupController extends Controller
{
    public function index(): View
    {
        $runs = BackupRun::query()->latest('id')->limit(10)->get();
        $lastSuccess = BackupRun::query()->where('status', 'success')->latest('id')->first();

        return view('dhp.admin.backups.index', [
            'runs' => $runs,
            'lastSuccess' => $lastSuccess,
            'lastRun' => $runs->first(),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        DhpAuditLogger::log(
            user: $request->user(),
            action: 'backup_run_requested',
            entityType: 'backup',
            entityId: null,
            details: ['source' => 'admin_manual'],
            ipAddress: $request->ip(),
        );

        $lock = Cache::lock('dhp-backup-run', 10);

        if (! $lock->get()) {
            return back()->with('error', 'A backup run is already in progress. Check backup status later.');
        }

        $lock->release();
        RunBackupJob::dispatch();

        return back()->with('success', 'Backup requested. Check backup status later.');
    }
}
