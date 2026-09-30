<?php

namespace App\Http\Controllers\Dhp\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only audit viewer. Only whitelisted detail keys are ever rendered;
 * anything else shows a generic protected-metadata notice. Raw IPs are
 * masked in the web UI.
 */
class AuditLogController extends Controller
{
    /**
     * Detail keys safe to summarize on screen.
     *
     * @var array<int, string>
     */
    public const SAFE_KEYS = [
        'method', 'result', 'authenticated_verifier', 'credential_type',
        'credential_status', 'facility_id', 'facility_type', 'has_expiry',
        'has_national_id', 'has_email', 'has_phone', 'portal_account_created',
        'matched_field_count', 'reason_category', 'is_active', 'role',
        'expiry_changed', 'changed_fields', 'source', 'route', 'reason',
        'expired_count', 'replaced',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'entity_type' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        if (! empty($data['date_from']) && ! empty($data['date_to'])
            && now()->parse($data['date_from'])->diffInDays($data['date_to']) > 90) {
            return back()->withInput()->with('error', 'Choose a date range of 90 days or less.');
        }

        $logs = AuditLog::query()
            ->with('user')
            ->when(! empty($data['action']), fn ($q) => $q->where('action', $data['action']))
            ->when(! empty($data['user_id']), fn ($q) => $q->where('user_id', $data['user_id']))
            ->when(! empty($data['entity_type']), fn ($q) => $q->where('entity_type', $data['entity_type']))
            ->when(! empty($data['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $data['date_from']))
            ->when(! empty($data['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $data['date_to']))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('dhp.admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => $data,
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Short safe summary from whitelisted keys only.
     */
    public static function summary(?array $details): string
    {
        if (empty($details)) {
            return '—';
        }

        $parts = [];
        foreach ($details as $key => $value) {
            if (! in_array($key, self::SAFE_KEYS, true) || is_array($value) || is_object($value)) {
                continue;
            }

            $parts[] = $key.': '.(is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value);
        }

        return $parts === [] ? 'Additional protected metadata recorded.' : implode('; ', array_slice($parts, 0, 4));
    }

    public static function hasHiddenKeys(?array $details): bool
    {
        if (empty($details)) {
            return false;
        }

        foreach (array_keys($details) as $key) {
            if (! in_array($key, self::SAFE_KEYS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 192.168.1.20 -> 192.168.*.*
     */
    public static function maskIp(?string $ip): string
    {
        if ($ip === null || $ip === '') {
            return '—';
        }

        $parts = explode('.', $ip);

        if (count($parts) === 4) {
            return $parts[0].'.'.$parts[1].'.*.*';
        }

        return '***';
    }
}
