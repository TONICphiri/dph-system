<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Patient Lab Orders — Digital Health Passport</title>
<style>
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f2f7f7;color:#222}
.top{background:#0E7490;color:#fff;padding:10px 16px;font-size:14px}
.top a{color:#fff;margin-right:14px}
.wrap{max-width:960px;margin:16px auto;padding:0 12px}
.box{background:#fff;border:1px solid #ccc;padding:16px;margin-bottom:16px}
h1{font-size:22px;margin:0 0 4px}
h1::before{content:"";display:inline-block;width:12px;height:12px;background:#0E7490;clip-path:polygon(0 0,0 100%,100% 100%);margin-right:8px}
.sub{color:#555;font-size:14px;margin:0 0 12px}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{border:1px solid #ccc;padding:8px;text-align:left;font-size:14px}
th{background:#0E7490;color:#fff}
tr:nth-child(even) td{background:#f7f7f7}
a{color:#0E7490}
.badge{display:inline-block;padding:2px 8px;font-size:12px;font-weight:bold;border:1px solid #999;background:#eee}
.badge-requested{background:#fff8e1;border-color:#D97706;color:#92400e}
.badge-pending{background:#fff7ed;border-color:#D97706;color:#9a3412}
.badge-results{background:#ecfdf5;border-color:#16A34A;color:#065f46}
.badge-cancelled{background:#f3f4f6;border-color:#999;color:#555}
.pager{margin-top:12px;font-size:14px}
.pager a{margin-right:12px}
</style>
</head>
<body>
<div class="top"><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('patients.show', $patient) }}">Back to patient</a><a href="{{ route('lab.orders.index') }}">All lab orders</a></div>
<div class="wrap">
<div class="box">
    <h1>Lab Orders for {{ $patient->full_name }}</h1>
    <p class="sub">DHP ID: {{ $patient->dhp_id }}</p>

    @if ($labOrders->count() > 0)
        <table>
            <thead>
                <tr><th>Test</th><th>Type</th><th>Status</th><th>Requested</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach ($labOrders as $labOrder)
                <tr>
                    <td><strong>{{ $labOrder->test_name }}</strong></td>
                    <td>{{ ucfirst($labOrder->test_type) }}</td>
                    <td><span class="badge badge-{{ $labOrder->status }}">{{ ucfirst($labOrder->status) }}</span></td>
                    <td>{{ $labOrder->requested_at?->format('M d, Y') ?? '—' }}</td>
                    <td>
                        @can('view_reports')
                        <a href="{{ route('lab.orders.show', $labOrder) }}">View</a>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pager">
            @if ($labOrders->previousPageUrl())<a href="{{ $labOrders->previousPageUrl() }}">&larr; Previous</a>@endif
            <span>Page {{ $labOrders->currentPage() }} of {{ $labOrders->lastPage() }}</span>
            @if ($labOrders->nextPageUrl())<a href="{{ $labOrders->nextPageUrl() }}">Next &rarr;</a>@endif
        </div>
    @else
        <p>No lab orders found for this patient.</p>
    @endif
</div>
</div>
</body>
</html>
