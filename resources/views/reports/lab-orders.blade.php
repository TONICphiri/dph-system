<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lab Orders — Digital Health Passport</title>
<style>
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f2f7f7;color:#222}
.top{background:#0E7490;color:#fff;padding:10px 16px;font-size:14px}
.top a{color:#fff;margin-right:14px}
.wrap{max-width:960px;margin:16px auto;padding:0 12px}
.box{background:#fff;border:1px solid #ccc;padding:16px;margin-bottom:16px}
h1{font-size:22px;margin:0 0 4px}
h1::before{content:"";display:inline-block;width:12px;height:12px;background:#0E7490;clip-path:polygon(0 0,0 100%,100% 100%);margin-right:8px}
.sub{color:#555;font-size:14px;margin:0 0 12px}
label{display:block;font-size:13px;font-weight:bold;margin:10px 0 4px}
select{padding:8px;border:1px solid #999;font-size:14px;max-width:280px}
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
.filters{display:flex;gap:16px;flex-wrap:wrap}
.pager{margin-top:12px;font-size:14px}
.pager a{margin-right:12px}
</style>
</head>
<body>
<div class="top"><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('patients.index') }}">Patients</a><a href="{{ route('reports.index') }}">Reports</a></div>
<div class="wrap">
@can('view_reports')
<div class="box">
    <h1>Lab Orders</h1>
    <p class="sub">All laboratory orders across the facility.</p>

    <div class="filters">
        <div>
            <label for="statusFilter">Filter by Status</label>
            <select id="statusFilter">
                <option value="">All Statuses</option>
                <option value="requested">Requested</option>
                <option value="pending">Pending</option>
                <option value="results">Results</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <div>
            <label for="testTypeFilter">Filter by Test Type</label>
            <select id="testTypeFilter">
                <option value="">All Test Types</option>
                <option value="Malaria RDT">Malaria RDT</option>
                <option value="Blood Glucose">Blood Glucose</option>
                <option value="CBC">CBC</option>
                <option value="Urinalysis">Urinalysis</option>
                <option value="Chest X-ray">Chest X-ray</option>
                <option value="All">All</option>
            </select>
        </div>
    </div>

    <table id="labTable">
        <thead>
            <tr><th>Patient</th><th>Test</th><th>Type</th><th>Status</th><th>Requested</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse ($labOrders as $labOrder)
            <tr data-status="{{ $labOrder->status }}" data-type="{{ $labOrder->test_name }}">
                <td>{{ $labOrder->patient->full_name }}</td>
                <td><strong>{{ $labOrder->test_name }}</strong></td>
                <td>{{ ucfirst($labOrder->test_type) }}</td>
                <td><span class="badge badge-{{ $labOrder->status }}">{{ ucfirst($labOrder->status) }}</span></td>
                <td>{{ $labOrder->requested_at->format('M d, Y') }}</td>
                <td><a href="{{ route('lab.orders.show', $labOrder) }}">View</a></td>
            </tr>
            @empty
            <tr><td colspan="6">No lab orders found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pager">
        @if ($labOrders->previousPageUrl())<a href="{{ $labOrders->previousPageUrl() }}">&larr; Previous</a>@endif
        <span>Page {{ $labOrders->currentPage() }} of {{ $labOrders->lastPage() }}</span>
        @if ($labOrders->nextPageUrl())<a href="{{ $labOrders->nextPageUrl() }}">Next &rarr;</a>@endif
    </div>
</div>
@endcan

<script>
(function () {
    var statusFilter = document.getElementById('statusFilter');
    var typeFilter = document.getElementById('testTypeFilter');
    function applyFilter() {
        var s = statusFilter.value, t = typeFilter.value;
        document.querySelectorAll('#labTable tbody tr[data-status]').forEach(function (row) {
            var okStatus = !s || row.getAttribute('data-status') === s;
            var okType = !t || t === 'All' || row.getAttribute('data-type') === t;
            row.style.display = (okStatus && okType) ? '' : 'none';
        });
    }
    statusFilter.addEventListener('change', applyFilter);
    typeFilter.addEventListener('change', applyFilter);
})();
</script>
</div>
</body>
</html>
