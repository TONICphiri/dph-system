<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index()
    {
        $this->authorize('view_audit_logs');

        $me = auth()->user();
        $query = AuditLog::with('user')->latest();

        // Facility Admin reviews local access logs only: entries made
        // by staff at their own facility. National Admin sees the
        // global trail (security compliance, nationwide access).
        if (!$me->isNationalAdmin()) {
            $facilityId = $me->facility_id;
            $query->whereHas('user', fn ($q) => $q->where('facility_id', $facilityId));
        }

        $auditLogs = $query->paginate(25);

        return view('admin.audit-logs.index', compact('auditLogs'));
    }
}
