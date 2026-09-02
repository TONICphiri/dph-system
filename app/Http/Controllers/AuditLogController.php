<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index()
    {
        $this->authorize('view_audit_logs');

        $auditLogs = AuditLog::with('user')
            ->latest()
            ->paginate(25);

        return view('admin.audit-logs.index', compact('auditLogs'));
    }
}
